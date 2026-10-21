<?php

namespace Grav\Plugin\Console;

use Grav\Common\Data\Blueprint;
use Grav\Common\Grav;
use Grav\Common\Yaml;
use Grav\Console\ConsoleCommand;
use Grav\Plugin\ComponentLibrary\Library;

/**
 * bin/plugin component-library validate
 *
 * Checks every pattern inside a real Grav install:
 *   1. pattern.yaml matches schema/pattern.schema.json, its id matches its
 *      folder, and every file it lists exists
 *   2. every YAML file parses
 *   3. every blueprint fragment resolves through Grav's own import@ (with no
 *      phantom "fields" field, every field typed) and provides the header or
 *      config keys the manifest declares
 *   4. every Twig template compiles with this Grav's Twig
 *   5. every template passes `lint` with no errors
 *   6. every PHP file passes php -l
 */
class ValidateCommand extends ConsoleCommand
{
    /** @var int */
    private $errors = 0;
    /** @var int */
    private $warnings = 0;

    protected function configure(): void
    {
        $this->setName('validate')->setDescription('Validate every pattern against the spec, Grav\'s blueprint loader and Twig');
    }

    protected function serve(): int
    {
        require_once __DIR__ . '/../classes/Library.php';

        $this->initializeGrav();
        $this->initializePlugins();
        $this->initializeThemes();

        $grav = Grav::instance();
        $lib = new Library();
        $schema = $lib->schema();
        $patterns = $lib->patterns();

        $twig = $grav['twig'];
        $twig->init();
        $env = $twig->twig();

        $this->output->writeln("Validating " . \count($patterns) . " patterns with Grav " . GRAV_VERSION . "\n");

        foreach ($patterns as $id => ['dir' => $dir, 'manifest' => $m]) {
            $this->output->writeln("<cyan>{$id}</cyan>");

            // 1. Manifest
            foreach ($lib->validateSchema($m, $schema) as $e) {
                $this->fail("pattern.yaml {$e}");
            }
            if (($m['id'] ?? null) !== $id) {
                $this->fail("pattern.yaml id is '" . ($m['id'] ?? '') . "', expected '{$id}'");
            }
            if (($m['level'] ?? null) !== explode('/', $id)[0]) {
                $this->fail("pattern.yaml level does not match its folder");
            }
            $listed = [];
            foreach ((array) ($m['files'] ?? []) as $key => $value) {
                foreach ((array) $value as $rel) {
                    $listed[] = $rel;
                    if (!is_file("{$dir}/{$rel}")) {
                        $this->fail("files.{$key}: {$rel} does not exist");
                    }
                }
            }
            foreach (glob("{$dir}/*") ?: [] as $file) {
                $name = basename($file);
                if (!\in_array($name, array_merge($listed, ['pattern.yaml', 'README.md']), true)) {
                    $this->warn("{$name} is in the folder but not listed under files");
                }
            }

            // Pattern ids mentioned anywhere in the manifest must exist.
            $text = json_encode($m, JSON_UNESCAPED_SLASHES);
            if (preg_match_all('#(?<![\w/.:-])((?:theme|page|page-type|system)/[a-z0-9][a-z0-9-]*)(?![\w/-]|\.\w)#', $text, $refs)) {
                foreach (array_unique($refs[1]) as $ref) {
                    if (!isset($patterns[$ref])) {
                        $this->fail("mentions {$ref}, which is not a pattern");
                    }
                }
            }

            // 2 + 3. YAML and blueprints
            $fieldNames = [];
            foreach (glob("{$dir}/*.yaml") ?: [] as $file) {
                $name = basename($file);
                try {
                    $data = (array) Yaml::parse((string) file_get_contents($file));
                } catch (\Throwable $e) {
                    $this->fail("{$name} does not parse: " . $e->getMessage());
                    continue;
                }
                if ($name === 'pattern.yaml' || !isset($data['form'])) {
                    continue;
                }
                $fieldNames = array_merge($fieldNames, $this->checkBlueprint($file, $data, $id));
            }

            $scope = $m['data']['scope'] ?? null;
            foreach ((array) ($m['data']['keys'] ?? []) as $key => $keyDef) {
                if ($scope === null || $scope === 'system' || !empty($keyDef['frontmatter_only'])) {
                    continue;
                }
                $field = $scope === 'page.header' ? 'header.' . $key : $key;
                if (strpos($key, 'page.header.') === 0) {
                    $field = 'header.' . substr($key, 12);
                }
                $found = false;
                foreach ($fieldNames as $f) {
                    if ($f === $field || strpos($f, $field . '.') === 0) {
                        $found = true;
                        break;
                    }
                }
                if (!$found && $fieldNames) {
                    $this->warn("data key '{$key}' has no matching blueprint field ({$field})");
                }
            }

            // 4 + 5. Twig
            foreach (glob("{$dir}/*.twig") ?: [] as $file) {
                $name = 'patterns/' . $id . '/' . basename($file);
                try {
                    $env->load($name);
                } catch (\Throwable $e) {
                    $this->fail(basename($file) . ' does not compile: ' . $e->getMessage());
                }
                foreach ($lib->lintSource((string) file_get_contents($file), $file) as $issue) {
                    $msg = basename($file) . ":{$issue['line']} {$issue['rule']}: {$issue['message']}";
                    $issue['level'] === 'error' ? $this->fail($msg) : $this->warn($msg);
                }
            }

            // 6. PHP
            foreach (glob("{$dir}/*.php") ?: [] as $file) {
                $this->phpLint($file);
            }
        }

        // Shared templates, plugin PHP and the CLI itself.
        $this->output->writeln('<cyan>_shared, plugin and CLI</cyan>');
        foreach (glob($lib->root() . '/patterns/_shared/*.twig') ?: [] as $file) {
            try {
                $env->load('patterns/_shared/' . basename($file));
            } catch (\Throwable $e) {
                $this->fail(basename($file) . ' does not compile: ' . $e->getMessage());
            }
            foreach ($lib->lintSource((string) file_get_contents($file), $file) as $issue) {
                $this->fail(basename($file) . ":{$issue['line']} {$issue['rule']}: {$issue['message']}");
            }
        }
        foreach (array_merge(glob($lib->root() . '/*.php') ?: [], glob($lib->root() . '/classes/*.php') ?: [], glob($lib->root() . '/cli/*.php') ?: []) as $file) {
            $this->phpLint($file);
        }

        $this->output->writeln('');
        if ($this->errors) {
            $this->output->writeln("<red>{$this->errors} error(s)</red>, {$this->warnings} warning(s).");
            return 1;
        }
        $this->output->writeln("<green>All patterns valid.</green> {$this->warnings} warning(s).");

        return 0;
    }

    /**
     * Resolve a fragment the way a theme would use it: as import@ on a field.
     * Full page blueprints (extends@) are loaded directly with the pages context.
     *
     * @return string[] resolved field names
     */
    private function checkBlueprint(string $file, array $data, string $id): array
    {
        $name = basename($file);
        $tmp = null;

        if (isset($data['extends@'])) {
            $bp = new Blueprint($file);
            $bp->setContext('blueprints://pages');
        } else {
            $tmp = sys_get_temp_dir() . '/cl-probe-' . md5($file) . '.yaml';
            $type = $id . '/' . preg_replace('/\.yaml$/', '', $name);
            file_put_contents($tmp, Yaml::dump(['form' => ['fields' => ['probe' => [
                'type' => 'section',
                'import@' => ['type' => $type, 'context' => 'plugin://component-library/patterns'],
            ]]]]));
            $bp = new Blueprint($tmp);
        }

        try {
            $bp->load()->init();
            $items = $bp->schema()->getState()['items'] ?? [];
        } catch (\Throwable $e) {
            $this->fail("{$name} does not resolve: " . $e->getMessage());
            return [];
        } finally {
            if ($tmp) {
                @unlink($tmp);
            }
        }

        $names = [];
        foreach ($items as $field => $def) {
            if ($field === '' || $field === 'probe') {
                continue;
            }
            $names[] = $field;
            if ($field === 'fields' || preg_match('/(^|\.)fields$/', $field)) {
                $this->fail("{$name}: phantom field '{$field}' (an import@ placed inside a fields: list)");
            }
            if (\is_array($def) && empty($def['type'])) {
                $this->fail("{$name}: field '{$field}' has no type");
            }
        }
        if (!$names) {
            $this->fail("{$name} resolved to no fields");
        }

        return $names;
    }

    private function phpLint(string $file): void
    {
        $out = [];
        $code = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
        if ($code !== 0) {
            $this->fail(basename($file) . ': ' . implode(' ', $out));
        }
    }

    private function fail(string $msg): void
    {
        $this->errors++;
        $this->output->writeln("  <red>✗</red> {$msg}");
    }

    private function warn(string $msg): void
    {
        $this->warnings++;
        $this->output->writeln("  <yellow>!</yellow> {$msg}");
    }
}
