<?php
/**
 * Pattern: system/frontend-output-guards
 *
 * For plugins that inject markup into every page (analytics, consent banners,
 * chat widgets). Copy the methods into your plugin class.
 */

namespace Grav\Plugin;

use Grav\Common\Plugin;

class ExampleTrackerPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return ['onPluginsInitialized' => ['onPluginsInitialized', 0]];
    }

    public function onPluginsInitialized(): void
    {
        // Never inject into classic Admin. (Admin2 is a separate app served
        // from its own route and never renders theme pages.)
        if ($this->isAdmin()) {
            return;
        }
        $this->enable(['onOutputGenerated' => ['onOutputGenerated', 0]]);
    }

    public function onOutputGenerated(): void
    {
        if (!$this->shouldInject()) {
            return;
        }

        $output = $this->grav->output;
        $pos = strripos($output, '</body>');
        if ($pos === false) {
            return;
        }
        $tag = '<script defer src="https://analytics.example.com/script.js" referrerpolicy="no-referrer"></script>';
        $this->grav->output = substr_replace($output, $tag . "\n", $pos, 0);
    }

    private function shouldInject(): bool
    {
        if (!$this->config->get('plugins.example-tracker.enabled')) {
            return false;
        }

        // 1. HTML pages only. A JSON, XML, RSS or Markdown response can contain
        //    "</body>" inside its data and would be corrupted by injection.
        $page = $this->grav['page'] ?? null;
        if (!$page || !method_exists($page, 'templateFormat') || $page->templateFormat() !== 'html') {
            return false;
        }

        // 2. Visitor privacy signals: Do Not Track and Global Privacy Control.
        //    Sec-GPC is "1"; some clients send the structured-field form "?1".
        if ($this->config->get('plugins.example-tracker.respect_dnt', true)) {
            $dnt = $_SERVER['HTTP_DNT'] ?? '';
            $gpc = $_SERVER['HTTP_SEC_GPC'] ?? '';
            if ($dnt === '1' || $gpc === '1' || $gpc === '?1') {
                return false;
            }
        }

        // 3. Site staff browsing the public site. Grav 2 accounts may hold only
        //    API permissions (api.access / api.super) and no admin.* ones, so
        //    check both families.
        if ($this->config->get('plugins.example-tracker.exclude_admins', true)) {
            $user = $this->grav['user'] ?? null;
            if ($user && $user->authenticated
                && ($user->authorize('admin.login') || $user->authorize('admin.super')
                    || $user->authorize('api.access') || $user->authorize('api.super'))) {
                return false;
            }
        }

        return true;
    }
}
