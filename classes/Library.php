<?php

namespace Grav\Plugin\ComponentLibrary;

use Grav\Common\Yaml;

/**
 * Reads the patterns/ folder and checks manifests. Used by the CLI commands;
 * nothing here runs on page requests.
 */
class Library
{
    public const LEVELS = ['theme', 'page', 'page-type', 'system'];

    /** @var string */
    private $root;

    public function __construct(?string $root = null)
    {
        $this->root = rtrim($root ?? \dirname(__DIR__), '/');
    }

    /** array_is_list() for PHP < 8.1 (Grav 1.7 sites). */
    private static function isList(array $a): bool
    {
        return $a === [] || array_keys($a) === range(0, \count($a) - 1);
    }

    public function root(): string
    {
        return $this->root;
    }

    /**
     * @return array<string, array{dir: string, manifest: array}>  keyed by pattern id
     */
    public function patterns(): array
    {
        $out = [];
        foreach (self::LEVELS as $level) {
            foreach (glob("{$this->root}/patterns/{$level}/*/pattern.yaml") ?: [] as $file) {
                $dir = \dirname($file);
                $id = $level . '/' . basename($dir);
                $out[$id] = ['dir' => $dir, 'manifest' => (array) Yaml::parse((string) file_get_contents($file))];
            }
        }
        ksort($out);

        return $out;
    }

    public function schema(): array
    {
        return json_decode((string) file_get_contents("{$this->root}/schema/pattern.schema.json"), true);
    }

    /**
     * Validate a value against the subset of JSON Schema used by
     * schema/pattern.schema.json: type, required, properties,
     * additionalProperties, enum, pattern, minLength, items, minItems,
     * uniqueItems, minProperties.
     *
     * @return string[] error messages
     */
    public function validateSchema($value, array $schema, string $path = ''): array
    {
        $errors = [];
        $at = $path === '' ? '(root)' : $path;

        if (isset($schema['enum']) && !\in_array($value, $schema['enum'], true)) {
            return ["{$at}: must be one of " . implode(', ', array_map('json_encode', $schema['enum']))];
        }

        $type = $schema['type'] ?? null;
        if ($type === 'object') {
            if (!\is_array($value) || ($value !== [] && self::isList($value))) {
                return ["{$at}: must be a map"];
            }
            foreach ($schema['required'] ?? [] as $key) {
                if (!array_key_exists($key, $value)) {
                    $errors[] = "{$at}: missing required key '{$key}'";
                }
            }
            if (isset($schema['minProperties']) && \count($value) < $schema['minProperties']) {
                $errors[] = "{$at}: needs at least {$schema['minProperties']} entries";
            }
            foreach ($value as $key => $item) {
                $child = $path === '' ? (string) $key : "{$path}.{$key}";
                if (isset($schema['properties'][$key])) {
                    $errors = array_merge($errors, $this->validateSchema($item, $schema['properties'][$key], $child));
                } elseif (($schema['additionalProperties'] ?? true) === false) {
                    $errors[] = "{$at}: unknown key '{$key}'";
                } elseif (\is_array($schema['additionalProperties'] ?? null)) {
                    $errors = array_merge($errors, $this->validateSchema($item, $schema['additionalProperties'], $child));
                }
            }
        } elseif ($type === 'array') {
            if (!\is_array($value) || ($value !== [] && !self::isList($value))) {
                return ["{$at}: must be a list"];
            }
            if (isset($schema['minItems']) && \count($value) < $schema['minItems']) {
                $errors[] = "{$at}: needs at least {$schema['minItems']} items";
            }
            if (!empty($schema['uniqueItems']) && \count($value) !== \count(array_unique(array_map('serialize', $value)))) {
                $errors[] = "{$at}: items must be unique";
            }
            foreach ($value as $i => $item) {
                if (isset($schema['items'])) {
                    $errors = array_merge($errors, $this->validateSchema($item, $schema['items'], "{$path}[{$i}]"));
                }
            }
        } elseif ($type === 'string') {
            if (!\is_string($value)) {
                return ["{$at}: must be a string"];
            }
            if (isset($schema['minLength']) && mb_strlen($value) < $schema['minLength']) {
                $errors[] = "{$at}: is too short";
            }
            if (isset($schema['pattern']) && !preg_match('/' . str_replace('/', '\/', $schema['pattern']) . '/u', $value)) {
                $errors[] = "{$at}: '{$value}' does not match {$schema['pattern']}";
            }
        } elseif ($type === 'boolean' && !\is_bool($value)) {
            $errors[] = "{$at}: must be true or false";
        }

        return $errors;
    }

    /**
     * Line-based template checks (see patterns/system/template-hygiene/rules.md).
     *
     * @return array<int, array{file: string, line: int, rule: string, level: string, message: string}>
     */
    public function lintPath(string $path): array
    {
        $files = [];
        if (is_file($path)) {
            $files = [$path];
        } elseif (is_dir($path)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                $name = $f->getPathname();
                if (preg_match('/\.twig$/', $name) && !preg_match('#/(vendor|node_modules|\.git)/#', $name)) {
                    $files[] = $name;
                }
            }
            sort($files);
        }

        $issues = [];
        foreach ($files as $file) {
            $issues = array_merge($issues, $this->lintSource((string) file_get_contents($file), $file));
        }

        return $issues;
    }

    public function lintSource(string $src, string $file): array
    {
        $issues = [];
        $add = function (int $offset, string $rule, string $level, string $message) use (&$issues, $src, $file) {
            $line = substr_count(substr($src, 0, $offset), "\n") + 1;
            $lineText = explode("\n", $src)[$line - 1] ?? '';
            if (preg_match('/lint-ignore:\s*([a-z0-9-, ]+)/', $lineText, $m) && \in_array($rule, array_map('trim', explode(',', $m[1])), true)) {
                return;
            }
            $issues[] = ['file' => $file, 'line' => $line, 'rule' => $rule, 'level' => $level, 'message' => $message];
        };

        // nested-comment: a {# inside a {# ... #} comment ends nothing and leaks the rest.
        $offset = 0;
        while (($start = strpos($src, '{#', $offset)) !== false) {
            $end = strpos($src, '#}', $start + 2);
            if ($end === false) {
                break;
            }
            $inner = strpos(substr($src, $start + 2, $end - $start - 2), '{#');
            if ($inner !== false) {
                $add($start + 2 + $inner, 'nested-comment', 'error', 'Twig comments do not nest; this {# ends the outer comment early.');
            }
            $offset = $end + 2;
        }

        // Strip Twig comments for the remaining rules.
        $code = preg_replace_callback('/\{#.*?#\}/s', fn ($m) => preg_replace('/[^\n]/', ' ', $m[0]), $src);

        // twig-in-html-comment
        if (preg_match_all('/<!--(.*?)-->/s', $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[1] as [$body, $pos]) {
                if (preg_match('/\{\{|\{%/', $body)) {
                    $add($pos, 'twig-in-html-comment', 'error', 'Twig runs inside <!-- -->; use {# #} to comment Twig out.');
                }
            }
        }

        // multiple-extends
        if (preg_match_all('/\{%-?\s*extends\b/', $code, $mm, PREG_OFFSET_CAPTURE) && \count($mm[0]) > 1) {
            $add($mm[0][1][1], 'multiple-extends', 'error', 'More than one {% extends %} ("Multiple extends tags are forbidden").');
        }

        // unescaped-header: {{ page.header.x }} / {{ header.x }} with no filter at all
        if (preg_match_all('/\{\{-?\s*((?:page\.)?header\.[A-Za-z0-9_.\[\]\'"]+)\s*-?\}\}/', $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[1] as [$expr, $pos]) {
                $add($pos, 'unescaped-header', 'warning', "{$expr} is printed without |e; it is safe only while Twig autoescape stays on.");
            }
        }

        // theme-var-raw
        if (preg_match_all('/theme_var\([^)]*\)[^}]*\|\s*raw/', $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[0] as [, $pos]) {
                $add($pos, 'theme-var-raw', 'error', "theme_var() is printed with |raw, but page front matter can override theme_var(); read trusted HTML with grav.config.get('theme.<key>').");
            }
        }
        // ...and a variable set from theme_var() later printed raw
        if (preg_match_all('/\{%-?\s*set\s+(\w+)\s*=\s*theme_var\(/', $code, $mm)) {
            foreach (array_unique($mm[1]) as $var) {
                if (preg_match('/\{\{-?\s*' . preg_quote($var, '/') . '\s*\|\s*raw/', $code, $m2, PREG_OFFSET_CAPTURE)) {
                    $add($m2[0][1], 'theme-var-raw', 'error', "'{$var}' comes from theme_var() and is printed with |raw; page front matter can override it.");
                }
            }
        }

        // blank-without-rel
        if (preg_match_all('/<a\b[^>]*target="_blank"[^>]*>/', $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[0] as [$tag, $pos]) {
                if (strpos($tag, 'noopener') === false) {
                    $add($pos, 'blank-without-rel', 'warning', 'target="_blank" without rel="noopener noreferrer".');
                }
            }
        }

        // json-without-hex-tag: json_encode inside a <script> element without flags that include JSON_HEX_TAG (1)
        if (preg_match_all('/<script\b[^>]*>(.*?)<\/script>/s', $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[1] as [$body, $pos]) {
                if (preg_match_all('/json_encode(?:\((\d*)\))?/', $body, $jm, PREG_OFFSET_CAPTURE)) {
                    foreach ($jm[1] as $i => $flag) {
                        $flags = (int) ($flag[0] ?? 0);
                        if (($flags & 1) === 0) {
                            $add($pos + $jm[0][$i][1], 'json-without-hex-tag', 'warning', 'json_encode inside <script> without JSON_HEX_TAG; use json_encode(15) or json_encode(321).');
                        }
                    }
                }
            }
        }

        // twig1-syntax
        foreach (['/\{%-?\s*filter\s+\w/' => '{% filter %} was removed in Twig 3; use {% apply %}.',
                  '/\{%-?\s*spaceless\b/' => '{% spaceless %} was removed in Twig 3; use the spaceless filter with {% apply %}.',
                  '/\{%-?\s*for\s+[^%]+\s+in\s+[^%]+\s+if\s+/' => '"for ... if" was removed in Twig 3; put an {% if %} inside the loop.'] as $re => $msg) {
            if (preg_match_all($re, $code, $mm, PREG_OFFSET_CAPTURE)) {
                foreach ($mm[0] as [, $pos]) {
                    $add($pos, 'twig1-syntax', 'error', $msg);
                }
            }
        }

        // media-url-true: media.url(true) looks absolute but is not (the argument is $reset)
        if (preg_match_all('/media\[[^\]]+\]\.url\(\s*true\s*\)|media\.url\(\s*true\s*\)/', $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[0] as [, $pos]) {
                $add($pos, 'media-url-true', 'warning', "A media object's url(true) is not an absolute URL (the argument means reset); use url(media.url, true).");
            }
        }

        // lazy-header-image
        if (preg_match_all('/header_image[^}\n]*\.loading\(\s*[\'"]lazy[\'"]\s*\)/', $code, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[0] as [, $pos]) {
                $add($pos, 'lazy-header-image', 'warning', "The header image is usually the page's Largest Contentful Paint; load it eagerly with fetchpriority('high').");
            }
        }

        usort($issues, fn ($a, $b) => $a['line'] <=> $b['line']);

        return $issues;
    }
}
