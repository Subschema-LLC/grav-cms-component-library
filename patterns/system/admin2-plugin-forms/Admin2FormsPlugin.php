<?php
/**
 * Pattern: system/admin2-plugin-forms
 *
 * A plugin settings form that works in both Admins:
 *   - classic Admin (Grav 1.7), which renders the form from runtime config, and
 *   - Admin2 (Grav 2), a browser app that loads the form definition and the
 *     saved YAML values through the API plugin.
 *
 * The example shows a read-only field (site_id) whose value is derived from a
 * secret (api_token). Rename "example" to your plugin's slug.
 */

namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class ExamplePlugin extends Plugin
{
    /**
     * Subscribe the form events HERE, not from onPluginsInitialized behind
     * isAdmin(): Admin2's requests go through the API, where isAdmin() is
     * false while plugins initialize, so a gated listener never runs there.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized'   => ['onPluginsInitialized', 0],
            'onApiBlueprintResolved' => ['onApiBlueprintResolved', 0],
            'onAdminSave'            => ['onAdminSave', 0],
        ];
    }

    public function onPluginsInitialized(): void
    {
        if ($this->isAdmin()) {
            // Classic Admin only: it reads the form's values from runtime config.
            $this->config->set('plugins.example.site_id', self::deriveSiteId($this->config->get('plugins.example.api_token')));
            return;
        }

        $this->enable([
            // frontend listeners ...
        ]);
    }

    /**
     * Admin2: values come from the saved YAML, not runtime config, and secret
     * fields reach the browser masked, so the browser cannot derive anything
     * from them. Put the derived value in the field's `default` instead; the
     * form shows the default while no value has been saved.
     */
    public function onApiBlueprintResolved(Event $event): void
    {
        if (($event['context'] ?? null) !== 'plugin' || ($event['plugin'] ?? null) !== 'example') {
            return;
        }
        $fields = $event['fields'] ?? null;
        if (!\is_array($fields)) {
            return;
        }
        $event['fields'] = self::withDefault($fields, 'site_id', self::deriveSiteId($this->config->get('plugins.example.api_token')));
    }

    /**
     * Both Admins: refresh the derived value from the value being saved, so the
     * form shows it straight after saving (Admin2 reloads the saved values, not
     * the form definition).
     */
    public function onAdminSave(Event $event): void
    {
        $object = $event['object'] ?? null;
        if (!\is_object($object) || !method_exists($object, 'get') || !method_exists($object, 'set') || !$this->isOwnConfiguration($object)) {
            return;
        }
        $object->set('site_id', self::deriveSiteId($object->get('api_token')));
    }

    /**
     * onAdminSave fires for every saved object (pages, users, other plugins).
     * Both Admins attach the storage file; fall back to the classic Admin route.
     */
    private function isOwnConfiguration(object $object): bool
    {
        $file = method_exists($object, 'file') ? $object->file() : null;
        if (\is_object($file) && method_exists($file, 'filename')) {
            $filename = str_replace('\\', '/', (string) $file->filename());
            $suffix = '/plugins/example.yaml';
            return substr($filename, -\strlen($suffix)) === $suffix; // PHP 7.x-safe (Grav 1.7)
        }
        $admin = $this->grav['admin'] ?? null;
        $route = \is_object($admin) && isset($admin->route) ? (string) $admin->route : '';
        return trim($route, '/') === 'plugins/example';
    }

    /** Set `default` on the named field wherever it sits in the field tree. */
    private static function withDefault(array $fields, string $name, $value): array
    {
        foreach ($fields as $key => $field) {
            if (!\is_array($field)) {
                continue;
            }
            if (($field['name'] ?? null) === $name) {
                $field['default'] = $value;
            }
            if (isset($field['fields']) && \is_array($field['fields'])) {
                $field['fields'] = self::withDefault($field['fields'], $name, $value);
            }
            $fields[$key] = $field;
        }
        return $fields;
    }

    private static function deriveSiteId($token): string
    {
        $token = \is_string($token) ? trim($token) : '';
        return $token === '' ? '' : substr(hash('sha256', $token), 0, 24);
    }
}
