<?php

namespace Grav\Plugin\ComponentLibrary;

use Grav\Common\Grav;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\Page\Pages;
use Grav\Common\Utils;

/**
 * Where the library's components are used on a site.
 *
 * Every field in a pattern's blueprint fragment carries `cl_pattern: <id>`.
 * Grav keeps that property when it resolves import@ and extends@, so the
 * resolved blueprint of a page template (or of the theme's settings) says
 * which components its form contains, and on which tab. From there:
 *
 *   - a page "uses" a component when one of that component's fields holds a
 *     value other than its default in the page's front matter;
 *   - the theme settings "use" one when the theme's blueprint imports it;
 *   - the theme "renders" one when a template includes its partial, and has
 *     "its own copy" when templates/patterns/<id>/... exists in the theme.
 *
 * Fields a theme defines itself, without importing a fragment, carry no tag
 * and are never counted, even when they store the same keys.
 */
class Usage
{
    public const DOCS_BASE = 'https://github.com/Subschema-LLC/grav-cms-component-library/blob/main/patterns/';

    /** Field types that only group or decorate other fields. */
    private const LAYOUT = ['tabs', 'tab', 'section', 'fieldset', 'columns', 'column', 'spacer', 'display', 'conditional'];

    /** @var Grav */
    private $grav;
    /** @var Library */
    private $library;
    /** @var array<string, array>|null */
    private $index;
    /** @var array<string, array> */
    private $templates = [];
    /** @var array<string, array>|null */
    private $theme;

    public function __construct(Grav $grav, ?Library $library = null)
    {
        $this->grav = $grav;
        $this->library = $library ?? new Library();
    }

    /**
     * Every pattern: id, name, version, level, summary and a docs link.
     *
     * @return array<string, array{id: string, name: string, version: string, level: string, summary: string, docs: string, has_template: bool}>
     */
    public function index(): array
    {
        if ($this->index === null) {
            $this->index = [];
            foreach ($this->library->patterns() as $id => $p) {
                $m = $p['manifest'];
                $this->index[$id] = [
                    'id' => $id,
                    'name' => (string) ($m['name'] ?? $id),
                    'version' => (string) ($m['version'] ?? ''),
                    'level' => (string) ($m['level'] ?? ''),
                    'summary' => (string) ($m['summary'] ?? ''),
                    'docs' => self::DOCS_BASE . $id . '/README.md',
                    'has_template' => (bool) glob($p['dir'] . '/*.twig'),
                ];
            }
        }

        return $this->index;
    }

    /**
     * Components in a list of raw blueprint fields, in form order.
     *
     * @param array<string, mixed> $fields  raw `form.fields` of a resolved blueprint
     * @return array<string, array{tab: ?string, fields: array<string, array>}>  id => tab title and tagged fields (name => definition)
     */
    public static function componentsIn(array $fields, ?string $tab = null, array &$out = []): array
    {
        foreach ($fields as $name => $field) {
            if (!\is_array($field)) {
                continue;
            }
            $type = (string) ($field['type'] ?? '');
            $here = $type === 'tab' ? (string) ($field['title'] ?? $field['label'] ?? $name) : $tab;
            $id = $field['cl_pattern'] ?? null;
            if (\is_string($id) && $id !== '') {
                $out[$id] = $out[$id] ?? ['tab' => $here, 'fields' => []];
                $out[$id]['fields'][(string) $name] = $field;
            }
            // Only containers: list and array children belong to their parent.
            if (\in_array($type, self::LAYOUT, true) && isset($field['fields']) && \is_array($field['fields'])) {
                self::componentsIn($field['fields'], $here, $out);
            }
        }

        return $out;
    }

    /**
     * Components in a page template's form.
     *
     * @return array<string, array{tab: ?string, fields: array<string, array>}>
     */
    public function templateComponents(string $template): array
    {
        if (!isset($this->templates[$template])) {
            $fields = [];
            try {
                /** @var Pages $pages */
                $pages = $this->grav['pages'];
                $fields = self::formFields($pages->blueprints($template));
            } catch (\Throwable $e) {
                // A template without a usable blueprint has no components.
            }
            $this->templates[$template] = $this->translateTabs(self::componentsIn($fields));
        }

        return $this->templates[$template];
    }

    /**
     * Components in the active theme's settings form.
     *
     * @return array<string, array{tab: ?string, fields: array<string, array>}>
     */
    public function themeComponents(): array
    {
        if ($this->theme === null) {
            $this->theme = [];
            $file = $this->grav['locator']->findResource('theme://blueprints.yaml');
            if ($file) {
                try {
                    $bp = new \Grav\Common\Data\Blueprint($file);
                    $bp->load()->init();
                    $this->theme = $this->translateTabs(self::componentsIn(self::formFields($bp)));
                } catch (\Throwable $e) {
                    $this->theme = [];
                }
            }
        }

        return $this->theme;
    }

    /**
     * The components a page's template offers, and whether this page has
     * content in each.
     *
     * @return list<array{id: string, name: string, version: string, docs: string, has_template: bool, tab: ?string, has_content: bool, filled: list<string>}>
     */
    public function pageComponents(PageInterface $page): array
    {
        $index = $this->index();
        $header = json_decode((string) json_encode($page->header()), true) ?: [];
        $out = [];
        foreach ($this->templateComponents((string) $page->template()) as $id => $c) {
            $filled = [];
            foreach ($c['fields'] as $name => $field) {
                if (strpos($name, 'header.') !== 0) {
                    continue;
                }
                $value = Utils::getDotNotation($header, substr($name, 7));
                if (self::hasValue($value, $field)) {
                    $filled[] = $name;
                }
            }
            $meta = $index[$id] ?? ['name' => $id, 'version' => '', 'docs' => self::DOCS_BASE . $id . '/README.md', 'has_template' => true];
            $out[] = [
                'id' => $id,
                'name' => $meta['name'],
                'version' => $meta['version'],
                'docs' => $meta['docs'],
                'has_template' => $meta['has_template'] ?? true,
                'tab' => $c['tab'],
                'has_content' => $filled !== [],
                'filled' => $filled,
            ];
        }

        return $out;
    }

    /**
     * Theme templates that include each pattern's partials, and the pattern
     * templates the theme has its own copy of.
     *
     * @return array{rendered_by: array<string, list<string>>, own_copies: array<string, list<array{file: string, identical: bool}>>}
     */
    public function themeTemplates(): array
    {
        $rendered = [];
        $copies = [];
        $patterns = $this->library->patterns();
        $locator = $this->grav['locator'];
        $dirs = $locator->findResources('theme://templates', false) ?: [];

        foreach ($dirs as $dir) {
            $dir = rtrim((string) $dir, '/');
            if (!is_dir($dir)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (!$file->isFile() || substr($file->getFilename(), -5) !== '.twig') {
                    continue;
                }
                $rel = substr($file->getPathname(), \strlen($dir) + 1);
                if (strpos($rel, 'patterns/') === 0) {
                    // templates/patterns/<level>/<name>/<file>: the theme's own copy.
                    if (preg_match('#^patterns/((?:theme|page|page-type|system)/[a-z0-9-]+)/(.+)$#', $rel, $m) && isset($patterns[$m[1]])) {
                        $original = $patterns[$m[1]]['dir'] . '/' . $m[2];
                        $copies[$m[1]][] = [
                            'file' => 'templates/' . $rel,
                            'identical' => is_file($original) && md5_file($original) === md5_file($file->getPathname()),
                        ];
                    }
                    continue;
                }
                $src = (string) file_get_contents($file->getPathname());
                if (preg_match_all('#[\'"]patterns/((?:theme|page|page-type|system)/[a-z0-9-]+)/#', $src, $m)) {
                    foreach (array_unique($m[1]) as $id) {
                        if (isset($patterns[$id])) {
                            $rendered[$id][] = 'templates/' . $rel;
                        }
                    }
                }
            }
        }
        foreach ($rendered as &$list) {
            $list = array_values(array_unique($list));
            sort($list);
        }
        unset($list);

        return ['rendered_by' => $rendered, 'own_copies' => $copies];
    }

    /**
     * The whole-site picture: for every component, where its fields appear,
     * which pages have content in it, and how the theme renders it.
     *
     * @return array{theme: string, components: list<array>, unused: list<array>, pages_scanned: int}
     */
    public function report(): array
    {
        $index = $this->index();
        $theme = (string) $this->grav['config']->get('system.pages.theme');
        $templates = $this->themeTemplates();
        $themeFields = $this->themeComponents();
        $themeConfig = (array) $this->grav['config']->get('theme', []);

        $rows = [];
        foreach ($index as $id => $meta) {
            $rows[$id] = $meta + [
                'templates' => [],
                'theme_settings' => null,
                'pages' => [],
                'rendered_by' => $templates['rendered_by'][$id] ?? [],
                'own_copies' => $templates['own_copies'][$id] ?? [],
            ];
        }

        foreach ($themeFields as $id => $c) {
            if (!isset($rows[$id])) {
                continue;
            }
            $configured = false;
            foreach ($c['fields'] as $name => $field) {
                if (self::hasValue(Utils::getDotNotation($themeConfig, $name), $field)) {
                    $configured = true;
                    break;
                }
            }
            $rows[$id]['theme_settings'] = ['tab' => $c['tab'], 'configured' => $configured];
        }

        $types = [];
        try {
            $types = array_merge(array_keys(Pages::types()), array_keys(Pages::modularTypes()));
        } catch (\Throwable $e) {
        }
        foreach ($types as $template) {
            foreach ($this->templateComponents((string) $template) as $id => $c) {
                if (isset($rows[$id])) {
                    $rows[$id]['templates'][] = ['template' => (string) $template, 'tab' => $c['tab']];
                }
            }
        }

        $scanned = 0;
        /** @var Pages $pages */
        $pages = $this->grav['pages'];
        if (method_exists($pages, 'enablePages')) {
            $pages->enablePages();
        }
        foreach ($pages->instances() as $page) {
            if (!$page instanceof PageInterface || $page->root() || !$page->exists()) {
                continue;
            }
            $scanned++;
            foreach ($this->pageComponents($page) as $c) {
                if ($c['has_content'] && isset($rows[$c['id']])) {
                    $rows[$c['id']]['pages'][] = [
                        'route' => (string) $page->rawRoute(),
                        'title' => (string) $page->title(),
                        'template' => (string) $page->template(),
                    ];
                }
            }
        }

        $used = [];
        $unused = [];
        foreach ($rows as $row) {
            usort($row['pages'], function ($a, $b) { return strcmp($a['route'], $b['route']); });
            $inUse = $row['templates'] || $row['theme_settings'] || $row['rendered_by'] || $row['own_copies'];
            if ($inUse) {
                $used[] = $row;
            } elseif ($row['level'] !== 'system') {
                $unused[] = ['id' => $row['id'], 'name' => $row['name'], 'docs' => $row['docs']];
            }
        }

        return ['theme' => $theme, 'components' => $used, 'unused' => $unused, 'pages_scanned' => $scanned];
    }

    /**
     * Whether a stored value counts as content: not empty, and not just the
     * field's default (a toggle left at its default is not "using" it).
     *
     * @param mixed $value
     */
    public static function hasValue($value, array $field): bool
    {
        if ($value === null || $value === '' || $value === [] || (\is_string($value) && trim($value) === '')) {
            return false;
        }
        if (\array_key_exists('default', $field) && \is_scalar($value) && \is_scalar($field['default'])) {
            $norm = static function ($v): string {
                if (\is_bool($v)) {
                    return $v ? '1' : '0';
                }
                return strtolower(trim((string) $v));
            };
            if ($norm($value) === $norm($field['default'])) {
                return false;
            }
        }
        if (\in_array($field['type'] ?? '', ['toggle', 'checkbox'], true) && \in_array($value, [0, '0', false], true) && !\array_key_exists('default', $field)) {
            return false;
        }

        return true;
    }

    /**
     * Tab titles can be language keys (PLUGIN_ADMIN.CONTENT). Translate them,
     * or make a readable title from the key when no translation is loaded.
     */
    private function translateTabs(array $components): array
    {
        foreach ($components as &$c) {
            $tab = $c['tab'];
            if (\is_string($tab) && preg_match('/^[A-Z0-9_]+(\.[A-Z0-9_]+)+$/', $tab)) {
                $text = $tab;
                try {
                    $text = (string) $this->grav['language']->translate([$tab]);
                } catch (\Throwable $e) {
                }
                if ($text === '' || $text === $tab) {
                    $parts = explode('.', $tab);
                    $text = ucfirst(strtolower(str_replace('_', ' ', (string) end($parts))));
                }
                $c['tab'] = $text;
            }
        }
        unset($c);

        return $components;
    }

    /**
     * @param object $blueprint  a Grav Blueprint
     * @return array<string, mixed>
     */
    private static function formFields($blueprint): array
    {
        if (method_exists($blueprint, 'fields')) {
            $fields = $blueprint->fields();
            if (\is_array($fields)) {
                return $fields;
            }
        }
        $form = method_exists($blueprint, 'form') ? (array) $blueprint->form() : [];

        return (array) ($form['fields'] ?? []);
    }
}
