<?php
namespace Grav\Theme;

use Grav\Common\Data\Blueprint;
use Grav\Common\Grav;
use Grav\Common\Theme;

/**
 * Fixture theme class. categoryOptions() exercises patterns/system/dynamic-options
 * (a theme-level data-options@ provider, registered outside isAdmin()).
 */
class ClTest extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return ['onThemeInitialized' => ['onThemeInitialized', 0]];
    }

    public function onThemeInitialized(): void
    {
        if (method_exists(Blueprint::class, 'addAllowedDynamicCallable')) {
            Blueprint::addAllowedDynamicCallable(self::class . '::categoryOptions');
        }
    }

    public static function categoryOptions(): array
    {
        $config = Grav::instance()['config'];
        $theme = (string) $config->get('system.pages.theme');
        $options = ['' => 'None'];
        foreach ((array) ($config->get("themes.{$theme}.categories") ?: []) as $item) {
            $id = trim((string) ($item['id'] ?? ''));
            if ($id !== '') {
                $options[$id] = (string) ($item['label'] ?? $id);
            }
        }
        return $options;
    }
}
