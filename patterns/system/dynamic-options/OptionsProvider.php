<?php
/**
 * Pattern: system/dynamic-options
 *
 * Fill a select's options from data at the moment the form opens:
 *
 *   header.category:
 *     type: select
 *     data-options@: '\Grav\Theme\Mytheme::categoryOptions'
 *
 * The provider is a public static method on your theme (or plugin) class, so
 * it autoloads with no composer.json. Register it for Grav 2.0.11+, which only
 * runs Class::method providers on its allowlist when a blueprint is not a
 * trusted file on disk (some API paths).
 */

namespace Grav\Theme;

use Grav\Common\Data\Blueprint;
use Grav\Common\Grav;
use Grav\Common\Theme;

class Mytheme extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onThemeInitialized' => ['onThemeInitialized', 0],
        ];
    }

    /**
     * Not behind isAdmin(): Admin2 resolves blueprints through the API, where
     * isAdmin() is false. method_exists keeps Grav 1.7 (no allowlist) working.
     */
    public function onThemeInitialized(): void
    {
        if (method_exists(Blueprint::class, 'addAllowedDynamicCallable')) {
            Blueprint::addAllowedDynamicCallable(self::class . '::categoryOptions');
        }
    }

    /**
     * Return value => label. Include an explicit empty choice when the field is
     * optional, and make the blueprint's `default` one of the returned keys.
     *
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        $config = Grav::instance()['config'];
        $theme = (string) $config->get('system.pages.theme');
        $items = (array) ($config->get("themes.{$theme}.categories") ?: []);

        $options = ['' => 'None'];
        foreach ($items as $item) {
            $id = trim((string) ($item['id'] ?? ''));
            if ($id !== '') {
                $options[$id] = (string) ($item['label'] ?? $id);
            }
        }

        return $options;
    }
}
