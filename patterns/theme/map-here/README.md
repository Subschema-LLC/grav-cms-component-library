<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# HERE map with conditional loading

`theme/map-here` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

HERE Maps settings in the theme, a loader that adds the map scripts only on pages that show a map, and a map script that reads its settings from JSON and supports linked markers.

## Use it when

- Some pages show an interactive map and most do not.
- Maps should be editable by putting &lt;div id="map"&gt;&lt;/div&gt; in content, without Twig.

## Skip it when

- Every page shows a map (load the scripts unconditionally).
- A static map image is enough.

## Requires

- the component-library plugin (or copy the files; see the main README)

## Apply

**Blueprint**

```yaml
# theme blueprints.yaml
map_section:
  type: section
  title: Map
  underline: true
  import@:
    type: theme/map-here/blueprint
    context: 'plugin://component-library/patterns'

# page blueprint
page_options:
  type: tab
  title: Page Options
  import@:
    type: theme/map-here/page
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{% block javascripts %}
    {% include 'patterns/theme/map-here/map-here.html.twig' with { map_templates: ['map'] } %}
{% endblock %}

{# In page content (Markdown), no Twig needed: #}
<div id="map" data-lat="44.98" data-lng="-93.27" data-zoom="14"></div>
```

## Stored data

Scope: `theme`

| Key | Type | Notes |
|---|---|---|
| `here_map.api_key` | string | Public by design; restrict it to the site's domains in the HERE console. |
| `here_map.latitude` | number |  |
| `here_map.longitude` | number |  |
| `here_map.zoom` | int |  |
| `here_map.label` | string |  |
| `here_map.legacy_globals` | bool |  |
| `page.header.map_enabled` | bool |  |
| `page.header.here_map` | map | Per-page overrides: latitude, longitude, zoom |

## Markup classes

`cl-map-bubble` `cl-map-bubble__title` `cl-map-bubble__summary`

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| Maps centered on Europe because the HERE default center was used when no center was passed. | The script uses the theme's default center; with none it fits the markers, or shows the world with a console warning instead of a misleading region. | community-guide theme, v0.2.0 |
| The four HERE scripts and stylesheet loaded on every page. | The loader adds them only for map templates, map_enabled pages, or pages whose content, tiles, CTA rows or modular sections contain id="map". | community-guide theme |
| The API key, center and zoom were hard-coded in JavaScript. | Theme settings, passed through a JSON block; per-page here_map overrides and per-element data-lat, data-lng and data-zoom. | community-guide theme |
| After the map script was rewritten, older inline scripts that called map.addObject() failed with "map.addObject is not a function", because the global map no longer existed and "map" resolved to the &lt;div id="map"&gt; element. | An opt-in Legacy Map Globals setting restores window.map, platform, ui, behavior, defaultLayers and moveMap. The default center pin is skipped when another script added objects. | community-guide theme, fix/map-globals |
| The map had no accessible name. | role="region" plus a configurable aria-label. |  |
| Animated panning ignored the visitor's reduced-motion setting. | prefers-reduced-motion turns animation off. |  |

## Verify

- [ ] A page without a map has no js.api.here.com requests.
- [ ] A page with &lt;div id="map"&gt;&lt;/div&gt; in its content loads mapsjs-core.js and has a
- [ ] With no API key, the browser console says so and the page has no script errors.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`map-here.html.twig`](map-here.html.twig) (template)
- [`page.yaml`](page.yaml) (snippets)
- [`map-here.js`](map-here.js) (assets)
