<?php

namespace Grav\Plugin\Console;

use Grav\Common\Yaml;
use Grav\Console\ConsoleCommand;
use Grav\Plugin\ComponentLibrary\Library;
use Symfony\Component\Console\Input\InputOption;

/**
 * bin/plugin component-library catalog [--check]
 *
 * Regenerates, from the pattern.yaml manifests:
 *   catalog.json               machine-readable index for agents and tools
 *   llms.txt                   llmstxt.org index pointing at raw files
 *   docs/CATALOG.md            human index
 *   patterns/<id>/README.md    each pattern's guide
 *
 * --check exits 1 when any of those files is out of date (for CI).
 */
class CatalogCommand extends ConsoleCommand
{
    private const REPO = 'Subschema-LLC/grav-plugin-component-library';
    private const BRANCH = 'main';

    protected function configure(): void
    {
        $this
            ->setName('catalog')
            ->setDescription('Regenerate catalog.json, llms.txt, docs/CATALOG.md and every pattern README from the manifests')
            ->addOption('check', null, InputOption::VALUE_NONE, 'Only report files that are out of date');
    }

    protected function serve(): int
    {
        require_once __DIR__ . '/../classes/Library.php';

        $lib = new Library();
        $root = $lib->root();
        $patterns = $lib->patterns();
        $plugin = (array) Yaml::parse((string) file_get_contents("{$root}/blueprints.yaml"));

        $files = [];
        $entries = [];
        foreach ($patterns as $id => ['dir' => $dir, 'manifest' => $m]) {
            $entries[] = $lib->entry($id, $m);
            $files["{$dir}/README.md"] = $this->readme($id, $m, $patterns);
        }

        $catalog = [
            'name' => 'Grav CMS Component Library',
            'plugin' => ['slug' => $plugin['slug'], 'version' => $plugin['version'], 'compatibility' => $plugin['compatibility'] ?? null],
            'repository' => 'https://github.com/' . self::REPO,
            'spec' => 'docs/PATTERN_SPEC.md',
            'agents' => 'AGENTS.md',
            'pattern_count' => \count($entries),
            'patterns' => $entries,
        ];
        $files["{$root}/catalog.json"] = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $files["{$root}/docs/CATALOG.md"] = $this->catalogMd($patterns);
        $files["{$root}/llms.txt"] = $this->llms($patterns);

        $stale = [];
        foreach ($files as $path => $content) {
            $current = is_file($path) ? (string) file_get_contents($path) : null;
            if ($current === $content) {
                continue;
            }
            $stale[] = str_replace($root . '/', '', $path);
            if (!$this->input->getOption('check')) {
                file_put_contents($path, $content);
            }
        }

        if ($this->input->getOption('check')) {
            if ($stale) {
                $this->output->writeln('<red>Out of date</red> (run bin/plugin component-library catalog):');
                foreach ($stale as $s) {
                    $this->output->writeln("  {$s}");
                }
                return 1;
            }
            $this->output->writeln('<green>Catalog, llms.txt and READMEs are up to date.</green>');
            return 0;
        }

        $this->output->writeln(\count($entries) . ' patterns; ' . ($stale ? 'updated ' . implode(', ', $stale) : 'nothing changed') . '.');

        return 0;
    }

    private function readme(string $id, array $m, array $all): string
    {
        $md = [];
        $md[] = "<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->";
        $md[] = "# {$m['name']}";
        $md[] = '';
        $md[] = "`{$id}` · v{$m['version']} · {$m['status']} · Grav " . implode(', ', $m['compatibility']['grav']) . ' (tested on ' . implode(', ', $m['compatibility']['tested']) . ')';
        $md[] = '';
        $md[] = $this->esc($m['summary']);
        $md[] = '';
        $md[] = '## Use it when';
        $md[] = '';
        foreach ($m['use_when'] as $u) {
            $md[] = '- ' . $this->esc($u);
        }
        if (!empty($m['avoid_when'])) {
            $md[] = '';
            $md[] = '## Skip it when';
            $md[] = '';
            foreach ($m['avoid_when'] as $u) {
                $md[] = '- ' . $this->linkIds($this->esc($u), $all);
            }
        }
        $req = $m['requires'] ?? [];
        if ($req) {
            $md[] = '';
            $md[] = '## Requires';
            $md[] = '';
            foreach ((array) ($req['patterns'] ?? []) as $p) {
                $md[] = $p === '_shared/macros' ? '- `patterns/_shared/macros.html.twig`' : "- [{$p}](../../{$p}/README.md)";
            }
            foreach ((array) ($req['plugins'] ?? []) as $p) {
                $md[] = $p === 'component-library' ? '- the component-library plugin (or copy the files; see the main README)' : "- the `{$p}` plugin";
            }
            if (!empty($req['php'])) {
                $md[] = '- PHP in your theme or plugin class';
            }
        }

        $md[] = '';
        $md[] = '## Apply';
        foreach ($m['apply'] as $kind => $snippet) {
            $lang = ['blueprint' => 'yaml', 'config' => 'yaml', 'template' => 'twig', 'php' => 'php'][$kind] ?? '';
            if ($kind === 'php' && preg_match('/^\s*(#|bin\/|GRAV_)/', $snippet)) {
                $lang = 'bash';
            }
            $md[] = '';
            $md[] = '**' . (['php' => 'PHP or shell'][$kind] ?? ucfirst($kind)) . '**';
            $md[] = '';
            $md[] = "```{$lang}";
            $md[] = rtrim($snippet);
            $md[] = '```';
        }

        if (!empty($m['data']['keys'])) {
            $md[] = '';
            $md[] = '## Stored data';
            $md[] = '';
            $md[] = 'Scope: `' . $m['data']['scope'] . '`';
            $md[] = '';
            $md[] = '| Key | Type | Notes |';
            $md[] = '|---|---|---|';
            foreach ($m['data']['keys'] as $key => $def) {
                $notes = $def['description'] ?? '';
                if (!empty($def['item'])) {
                    $parts = [];
                    foreach ($def['item'] as $k => $t) {
                        $parts[] = "`{$k}` {$t}";
                    }
                    $notes = trim($notes . ' ' . implode('; ', $parts));
                }
                $md[] = "| `{$key}` | " . $this->cell($def['type']) . ' | ' . $this->cell($notes) . ' |';
            }
        }

        if (!empty($m['legacy_keys'])) {
            $md[] = '';
            $md[] = '## Older keys';
            $md[] = '';
            foreach ($m['legacy_keys'] as $l) {
                $md[] = "- `{$l['key']}`: " . $this->esc($l['handling']);
            }
        }

        if (!empty($m['classes']['markup'])) {
            $md[] = '';
            $md[] = '## Markup classes';
            $md[] = '';
            $md[] = implode(' ', array_map(fn ($c) => "`{$c}`", $m['classes']['markup']));
            if (!empty($m['classes']['slots'])) {
                $md[] = '';
                $md[] = 'Extra classes can be passed for these slots: ' . implode(', ', array_map(fn ($c) => "`{$c}`", $m['classes']['slots'])) . '.';
            }
        }

        $md[] = '';
        $md[] = '## Problems this solves';
        $md[] = '';
        $md[] = '| Problem | Fix | Seen in |';
        $md[] = '|---|---|---|';
        foreach ($m['solves'] as $s) {
            $md[] = '| ' . $this->cell($s['problem']) . ' | ' . $this->cell($s['fix']) . ' | ' . $this->cell($s['seen_in'] ?? '') . ' |';
        }

        $md[] = '';
        $md[] = '## Verify';
        $md[] = '';
        foreach ($m['verify'] as $v) {
            $md[] = '- [ ] ' . $this->esc($v);
        }

        $md[] = '';
        $md[] = '## Files';
        $md[] = '';
        foreach ((array) $m['files'] as $kind => $value) {
            foreach ((array) $value as $f) {
                $md[] = "- [`{$f}`]({$f}) ({$kind})";
            }
        }
        $md[] = '';

        return implode("\n", $md);
    }

    private function catalogMd(array $patterns): string
    {
        $titles = ['theme' => 'Theme level', 'page' => 'Page level', 'page-type' => 'Page types', 'system' => 'System level'];
        $md = ['<!-- Generated by `bin/plugin component-library catalog`. -->', '# Catalog', ''];
        $md[] = \count($patterns) . ' patterns. Each links to its guide; `catalog.json` has the same data for tools and agents.';
        foreach ($titles as $level => $title) {
            $md[] = '';
            $md[] = "## {$title}";
            $md[] = '';
            $md[] = '| Pattern | Summary | Problems solved |';
            $md[] = '|---|---|---|';
            foreach ($patterns as $id => ['manifest' => $m]) {
                if ($m['level'] !== $level) {
                    continue;
                }
                $md[] = "| [{$m['name']}](../patterns/{$id}/README.md)<br>`{$id}` | " . $this->cell($m['summary']) . ' | ' . \count($m['solves']) . ' |';
            }
        }
        $md[] = '';

        return implode("\n", $md);
    }

    private function llms(array $patterns): string
    {
        $raw = 'https://raw.githubusercontent.com/' . self::REPO . '/' . self::BRANCH;
        $out = [];
        $out[] = '# Grav CMS Component Library';
        $out[] = '';
        $out[] = '> Tested, reusable blueprint fragments, Twig partials, page types and system practices for Grav CMS 1.7 and 2.x. Each pattern is a folder with a machine-readable pattern.yaml (when to use it, the exact import@ and include snippets, the data it stores, the problems it solves and how to verify it).';
        $out[] = '';
        $out[] = 'Start with AGENTS.md for how to choose and apply patterns. catalog.json lists every pattern with its import type, include path and raw file URLs. Grav-specific rule that breaks most generated blueprints: import@ goes on the receiving field (a sibling of type:), never inside its fields: list.';
        $out[] = '';
        $out[] = '## Guides';
        $out[] = '';
        $out[] = "- [AGENTS.md]({$raw}/AGENTS.md): how an AI agent should select, apply and verify patterns";
        $out[] = "- [Pattern spec]({$raw}/docs/PATTERN_SPEC.md): folder layout, manifest keys, blueprint and template rules";
        $out[] = "- [catalog.json]({$raw}/catalog.json): every pattern, machine-readable";
        $out[] = "- [README]({$raw}/README.md): install, use with or without the plugin";
        foreach (['theme' => 'Theme patterns', 'page' => 'Page patterns', 'page-type' => 'Page types', 'system' => 'System patterns'] as $level => $title) {
            $out[] = '';
            $out[] = "## {$title}";
            $out[] = '';
            foreach ($patterns as $id => ['manifest' => $m]) {
                if ($m['level'] === $level) {
                    $out[] = "- [{$m['name']}]({$raw}/patterns/{$id}/pattern.yaml): {$m['summary']}";
                }
            }
        }
        $out[] = '';
        $out[] = '## Optional';
        $out[] = '';
        $out[] = "- [Template rules]({$raw}/patterns/system/template-hygiene/rules.md): what `bin/plugin component-library lint` checks";
        $out[] = "- [Manifest JSON Schema]({$raw}/schema/pattern.schema.json)";
        $out[] = '';

        return implode("\n", $out);
    }

    private function linkIds(string $text, array $all): string
    {
        return preg_replace_callback('/\b((?:theme|page|page-type|system)\/[a-z0-9-]+)\b/', function ($m) use ($all) {
            return isset($all[$m[1]]) ? "[{$m[1]}](../../{$m[1]}/README.md)" : $m[1];
        }, $text);
    }

    private function cell($text): string
    {
        return str_replace(["|", "\n"], ['\\|', ' '], $this->esc((string) $text));
    }

    /** Escape < and > outside `code` spans so GitHub shows tags as text. */
    private function esc($text): string
    {
        $parts = preg_split('/(`[^`]*`)/', (string) $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {
                $parts[$i] = str_replace(['<', '>'], ['&lt;', '&gt;'], $part);
            }
        }
        return implode('', $parts);
    }
}
