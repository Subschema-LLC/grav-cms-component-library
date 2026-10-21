<?php
/**
 * Pattern: system/twig-in-content — let page content call a plugin's own safe Twig function
 *
 * Only for functions that are safe for every page editor to call: no file,
 * network, config or user access driven by arguments.
 */

namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class ExampleGalleryPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onBuildTwigSandboxPolicy' => ['onBuildTwigSandboxPolicy', 0],
        ];
    }

    public function onBuildTwigSandboxPolicy(Event $event): void
    {
        // The event's arguments are returned by value: read, modify, write back.
        $functions = $event['functions'];
        $functions[] = 'example_gallery';
        $event['functions'] = $functions;

        $methods = $event['methods'];
        $methods[] = ['class' => \Grav\Plugin\ExampleGallery\Gallery::class, 'methods' => 'render'];
        $event['methods'] = $methods;
    }
}
