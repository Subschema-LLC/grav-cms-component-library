<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Map page

`page-type/map-page` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

A page type that pins every page with a Map Location plus optional extra pins, lists the same places under the map with "Show on map" buttons, and outputs them as a Schema.org ItemList.

## Use it when

- A site has several pages about places and wants one map that links to all of them.

## Skip it when

- The site has hundreds of pages and only a few locations; scanning every page on each request adds up. Turn include_pages off and use Additional Pins, or cache the page.

## Requires

- [theme/map-here](../../theme/map-here/README.md)
- [page/map-location](../../page/map-location/README.md)
- [page/page-heading](../../page/page-heading/README.md)
- `patterns/_shared/macros.html.twig`
- the component-library plugin (or copy the files; see the main README)

## Apply

**Blueprint**

```yaml
# 1. Copy page-blueprint.yaml to user/themes/<theme>/blueprints/map.yaml
# 2. Add page/map-location to the default page blueprint so places can be pinned.
```

**Template**

```twig
# 3. Copy map.html.twig to user/themes/<theme>/templates/map.html.twig and
#    adjust its extends/blocks to your base template.
# 4. In the base template's javascripts block (theme/map-here):
{% include 'patterns/theme/map-here/map-here.html.twig' with { map_templates: ['map'] } %}
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `map_page` | map | `include_pages` bool (default true); `list_title` string |
| `map_markers` | list | `link` page route; `title` string; `latitude` number; `longitude` number; `summary` string |

## Markup classes

`cl-map-page` `cl-map-page__map` `cl-map-page__places` `cl-map-page__heading` `cl-map-page__list` `cl-map-page__place` `cl-map-page__place-text` `cl-map-page__place-link` `cl-map-page__place-name` `cl-map-page__place-summary` `cl-map-page__locate`

Extra classes can be passed for these slots: `root`, `map`, `heading`, `list`, `item`, `button`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The pin list and the "Show on map" buttons got out of step, because Twig's sort keeps the original array keys and the JSON became an object instead of an array. | merge([]) after sort renumbers the list, so button n always opens pin n. | community-guide theme, Map page |
| Visitors without the map (keyboard, screen reader, blocked script) had no way to reach the places. | A real list of links under the map; the locate buttons stay hidden until their pin exists. | community-guide theme, Map page |
| The structured data listed only the extra pins, not the pages with a Map Location. | The ItemList is built from the same list the page renders. | community-guide theme, Schema.org work |
| Each map page fired its analytics event through an inline script. | data-datalayer-event="map_view" on the map element (theme/datalayer). | community-guide theme, map_view tracking |
| A pin for a page that was later unpublished or made non-routable still appeared. | Only published, routable pages are pinned. |  |

## Verify

- [ ] The
- [ ] Each button's data-marker-index equals its list position (0-based).
- [ ] The page has one ItemList JSON-LD with numberOfItems equal to the list length.

## Files

- [`fields.yaml`](fields.yaml) (blueprint)
- [`map-page.html.twig`](map-page.html.twig) (template)
- [`map.html.twig`](map.html.twig) (templates)
- [`page-blueprint.yaml`](page-blueprint.yaml) (snippets)
- [`example.md`](example.md) (example)
