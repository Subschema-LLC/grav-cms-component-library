<?php

namespace Grav\Plugin\ComponentLibrary\Api;

use Grav\Plugin\Api\Controllers\AbstractApiController;
use Grav\Plugin\Api\Exceptions\NotFoundException;
use Grav\Plugin\Api\Response\ApiResponse;
use Grav\Plugin\ComponentLibrary\Library;
use Grav\Plugin\ComponentLibrary\Usage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Component catalog and usage for Grav 2's Admin and MCP tools (only loaded
 * when the API plugin is).
 *
 *   GET /component-library/patterns            list or search patterns (?level=…&search=…)
 *   GET /component-library/pattern?id=…        full specification and snippets for one pattern
 *   GET /component-library/page?route=/about   components on one page
 *   GET /component-library/page-badge?route=…  { count } for the editor's panel button
 *   GET /component-library/usage               the whole-site report
 */
class UsageController extends AbstractApiController
{
    public function patterns(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.pages.read');
        $params = $request->getQueryParams();
        $level = isset($params['level']) && \is_string($params['level']) ? $params['level'] : null;
        $search = isset($params['search']) && \is_string($params['search']) ? $params['search'] : null;
        $items = (new Library())->search($level, $search);

        return ApiResponse::create([
            'count' => \count($items),
            'patterns' => $items,
        ]);
    }

    public function pattern(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.pages.read');
        $id = (string) ($request->getQueryParams()['id'] ?? '');
        $lib = new Library();
        $entry = $lib->find($id);
        if (!$entry) {
            throw new NotFoundException("Unknown pattern '{$id}'.");
        }
        $entry['copy_mode'] = $lib->copyMode($entry);

        return ApiResponse::create($entry);
    }
    public function page(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.pages.read');
        $page = $this->pageFromQuery($request);
        $usage = new Usage($this->grav);
        $templates = $usage->themeTemplates();

        $rows = [];
        foreach ($usage->pageComponents($page) as $c) {
            $c['rendered_by'] = $templates['rendered_by'][$c['id']] ?? [];
            $c['own_copies'] = $templates['own_copies'][$c['id']] ?? [];
            $rows[] = $c;
        }

        return ApiResponse::create([
            'route' => (string) $page->rawRoute(),
            'title' => (string) $page->title(),
            'template' => (string) $page->template(),
            'components' => $rows,
        ]);
    }

    public function badge(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.pages.read');
        $count = 0;
        try {
            $page = $this->pageFromQuery($request);
            foreach ((new Usage($this->grav))->pageComponents($page) as $c) {
                $count += $c['has_content'] ? 1 : 0;
            }
        } catch (NotFoundException $e) {
            // A page that isn't saved yet has nothing to count.
        }

        return ApiResponse::create(['count' => $count]);
    }

    public function usage(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, 'api.pages.read');

        return ApiResponse::create((new Usage($this->grav))->report());
    }

    private function pageFromQuery(ServerRequestInterface $request)
    {
        $route = (string) ($request->getQueryParams()['route'] ?? '');
        $page = $route !== '' ? $this->resolvePageByRoute($route) : null;
        if (!$page) {
            throw new NotFoundException("No page at '{$route}'.");
        }

        return $page;
    }
}
