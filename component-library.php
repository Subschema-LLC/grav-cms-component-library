<?php

namespace Grav\Plugin;

use Grav\Common\Data\Blueprint;
use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Plugin\ComponentLibrary\Library;
use Grav\Plugin\ComponentLibrary\Usage;
use RocketTheme\Toolbox\Event\Event;

// Classes under Grav\Plugin\ComponentLibrary\ load from classes/. The API
// controller is only ever loaded by the API plugin's dispatcher, so it can
// extend the API's base class without breaking sites that don't have it.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Grav\\Plugin\\ComponentLibrary\\';
    if (strpos($class, $prefix) === 0) {
        $file = __DIR__ . '/classes/' . str_replace('\\', '/', substr($class, \strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

/**
 * Component Library
 *
 * Makes the patterns in ./patterns usable from any theme:
 *   - Blueprint fragments:  import@: { type: page/cta-rows/blueprint, context: 'plugin://component-library/patterns' }
 *   - Twig partials:        {% include 'patterns/page/cta-rows/cta-rows.html.twig' %}
 *
 * The plugin folder is appended to the Twig paths, after the theme, so a theme
 * overrides any pattern template by placing a file at the same path under its
 * own templates/ folder (templates/patterns/page/cta-rows/cta-rows.html.twig).
 *
 * In Grav 2's Admin it also shows where components are used: a line under
 * each component's fields in the editor, a "Components" panel in the page
 * editor, and a usage report under Tools → Reports (classes/Usage.php).
 */
class ComponentLibraryPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        // Every listener is subscribed here, never from onPluginsInitialized behind
        // isAdmin(): under Grav 2's Admin the work happens in API requests, where
        // isAdmin() is false, so a gated listener would never run there.
        return [
            'onPluginsInitialized'   => ['onPluginsInitialized', 0],
            'onTwigTemplatePaths'    => ['onTwigTemplatePaths', 0],
            'onTwigSiteVariables'    => ['onTwigSiteVariables', 0],
            // Grav 2's Admin (fired by the API plugin; never on Grav 1.7).
            'onApiRegisterRoutes'    => ['onApiRegisterRoutes', 0],
            'onApiBlueprintResolved' => ['onApiBlueprintResolved', -10],
            'onApiContextPanels'     => ['onApiContextPanels', 0],
            'onApiGenerateReports'   => ['onApiGenerateReports', 0],
        ];
    }

    /**
     * Register the option providers used by `data-options@` in pattern
     * blueprints. Grav 2.0.11+ refuses unregistered Class::method providers in
     * some code paths, and Grav 1.7 has no allowlist at all, hence method_exists.
     * Not gated on isAdmin(): Admin2 resolves blueprints through the API.
     */
    public function onPluginsInitialized(): void
    {
        if (method_exists(Blueprint::class, 'addAllowedDynamicCallable')) {
            Blueprint::addAllowedDynamicCallable(self::class . '::authorOptions');
        }
    }

    public function onTwigTemplatePaths(): void
    {
        // Appended (not prepended) so theme templates win.
        $this->grav['twig']->twig_paths[] = __DIR__;
    }

    public function onTwigSiteVariables(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        // A theme that styles the patterns itself can opt out in its own config
        // (component_library: { load_css: false }), overriding the plugin setting.
        $themeChoice = $this->config->get('theme.component_library.load_css');
        $load = $themeChoice !== null ? (bool) $themeChoice : (bool) $this->config->get('plugins.component-library.load_css', true);
        if ($load) {
            $this->grav['assets']->addCss('plugin://component-library/assets/component-library.css', ['priority' => 5]);
        }
    }

    public function onApiRegisterRoutes(Event $event): void
    {
        $controller = \Grav\Plugin\ComponentLibrary\Api\UsageController::class;
        $event['routes']->get('/component-library/page', [$controller, 'page']);
        $event['routes']->get('/component-library/page-badge', [$controller, 'badge']);
        $event['routes']->get('/component-library/usage', [$controller, 'usage']);
    }

    /**
     * A "Components" button in the page editor's toolbar, with the number of
     * components that have content on the page, opening admin-next/panels/.
     */
    public function onApiContextPanels(Event $event): void
    {
        if (!$this->adminFeature('page_panel')) {
            return;
        }
        $panels = (array) $event['panels'];
        $panels[] = [
            'id'            => 'component-library',
            'plugin'        => 'component-library',
            'label'         => 'Components',
            'icon'          => 'blocks',
            'contexts'      => ['pages'],
            'priority'      => 5,
            'width'         => 440,
            'badgeEndpoint' => '/component-library/page-badge',
            'authorize'     => 'api.pages.read',
        ];
        $event['panels'] = $panels;
    }

    /**
     * "Component Library" under Tools → Reports, drawn by admin-next/reports/usage.js.
     */
    public function onApiGenerateReports(Event $event): void
    {
        if (!$this->adminFeature('report')) {
            return;
        }
        $report = (new Usage($this->grav))->report();
        $theme = $this->grav['locator']->findResource('theme://');
        $lint = $theme ? (new Library())->lintPath($theme) : [];
        $base = $theme ? rtrim((string) realpath($theme), '/') . '/' : '';
        foreach ($lint as &$issue) {
            $issue['file'] = str_replace($base, '', (string) (realpath($issue['file']) ?: $issue['file']));
        }
        unset($issue);
        $errors = \count(array_filter($lint, function ($i) { return $i['level'] === 'error'; }));
        $inUse = \count($report['components']);
        $withContent = \count(array_filter($report['components'], function ($c) { return $c['pages'] !== []; }));

        $reports = (array) $event['reports'];
        $reports[] = [
            'id'        => 'component-library',
            'title'     => 'Component Library',
            'provider'  => 'component-library',
            'component' => 'usage',
            'status'    => $errors ? 'warning' : 'success',
            'message'   => "{$inUse} components in use by theme {$report['theme']}; {$withContent} have content on pages."
                . ($errors ? " The template checks found {$errors} problem(s)." : ''),
            'items'     => $report['components'],
            'unused'    => $report['unused'],
            'theme'     => $report['theme'],
            'pages_scanned' => $report['pages_scanned'],
            'lint'      => array_slice($lint, 0, 100),
            'lint_total' => \count($lint),
        ];
        $event['reports'] = $reports;
    }

    /**
     * Mark the fields that come from the library: a line under each
     * component's heading naming it, its version and its docs, so editors
     * and developers can tell shared components from the theme's own fields.
     */
    public function onApiBlueprintResolved(Event $event): void
    {
        if (!$this->adminFeature('field_labels')) {
            return;
        }
        $usage = new Usage($this->grav);
        $context = $event['context'] ?? null;
        if ($context === 'page' && \is_string($event['template'] ?? null)) {
            $components = $usage->templateComponents($event['template']);
        } elseif ($context === 'theme' && ($event['theme'] ?? null) === $this->config->get('system.pages.theme')) {
            $components = $usage->themeComponents();
        } else {
            return;
        }
        if (!$components) {
            return;
        }

        $owner = [];
        foreach ($components as $id => $c) {
            foreach (array_keys($c['fields']) as $name) {
                $owner[$name] = $id;
            }
        }
        $index = $usage->index();
        $copies = $usage->themeTemplates()['own_copies'];
        $seen = [];
        $event['fields'] = $this->labelFields((array) $event['fields'], $owner, $index, $copies, $seen);
    }

    /**
     * @param list<array> $fields  serialized fields as Admin2 receives them
     */
    private function labelFields(array $fields, array $owner, array $index, array $copies, array &$seen): array
    {
        $out = [];
        foreach ($fields as $field) {
            if (!\is_array($field)) {
                $out[] = $field;
                continue;
            }
            $id = $owner[$field['name'] ?? ''] ?? null;
            $label = null;
            if ($id !== null && !isset($seen[$id])) {
                $seen[$id] = true;
                $label = $this->labelField($id, $index[$id] ?? null, $copies[$id] ?? []);
            }
            $type = $field['type'] ?? '';
            if (isset($field['fields']) && \is_array($field['fields']) && \in_array($type, ['tabs', 'tab', 'section', 'fieldset', 'columns', 'column'], true)) {
                $field['fields'] = $this->labelFields($field['fields'], $owner, $index, $copies, $seen);
            }
            if ($label && $type === 'section') {
                // Under the section's heading: inside it when it holds fields, right after it otherwise.
                if (!empty($field['fields'])) {
                    array_unshift($field['fields'], $label);
                    $out[] = $field;
                } else {
                    $out[] = $field;
                    $out[] = $label;
                }
                continue;
            }
            if ($label) {
                $out[] = $label;
            }
            $out[] = $field;
        }

        return $out;
    }

    private function labelField(string $id, ?array $meta, array $copies): array
    {
        $name = $meta['name'] ?? $id;
        $version = isset($meta['version']) && $meta['version'] !== '' ? ' ' . $meta['version'] : '';
        // No docs link here: Admin2 strips target="_blank" from form text, so a
        // link would take the editor away from unsaved changes. The panel and
        // the report carry the links.
        $text = "Shared component: **{$name}** · `{$id}`{$version}";
        foreach ($copies as $copy) {
            if (!$copy['identical']) {
                $text .= ' · The theme has its own template for it';
                break;
            }
        }

        // A spacer: Admin2 draws its text small and muted, and stores nothing for it.
        return [
            'name'      => 'cl_component_' . str_replace(['/', '-'], '_', $id),
            'type'      => 'spacer',
            'text'      => $text,
            'markdown'  => true,
            'underline' => false,
        ];
    }

    private function adminFeature(string $name): bool
    {
        return (bool) $this->config->get("plugins.component-library.admin2.{$name}", true);
    }

    /**
     * Options for a page's "Author" select, built from the active theme's
     * `authors` registry (patterns/theme/author-registry). Keeps page blueprints
     * free of hard-coded names that drift from the registry.
     *
     * @return array<string, string>
     */
    public static function authorOptions(): array
    {
        $config = Grav::instance()['config'];
        $theme = (string) $config->get('system.pages.theme');
        $authors = $config->get("themes.{$theme}.authors") ?: [];

        $options = ['' => 'None'];
        foreach ((array) $authors as $author) {
            $id = trim((string) ($author['authorid'] ?? ''));
            if ($id === '') {
                continue;
            }
            $name = trim((string) ($author['authorname'] ?? '')) ?: $id;
            $options[$id] = "{$name} ({$id})";
        }

        return $options;
    }
}
