<?php

namespace Grav\Plugin;

use Grav\Common\Data\Blueprint;
use Grav\Common\Grav;
use Grav\Common\Plugin;

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
 */
class ComponentLibraryPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        // Every listener is subscribed here, never from onPluginsInitialized behind
        // isAdmin(): under Grav 2's Admin the work happens in API requests, where
        // isAdmin() is false, so a gated listener would never run there.
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onTwigTemplatePaths'  => ['onTwigTemplatePaths', 0],
            'onTwigSiteVariables'  => ['onTwigSiteVariables', 0],
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
