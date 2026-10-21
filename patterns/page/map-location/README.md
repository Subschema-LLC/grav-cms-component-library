<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Map location

`page/map-location` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

Coordinates, a pin title and a summary on any page, so the page appears on Map pages and gets a Place entity in structured data, with no extra markup of its own.

## Use it when

- Pages describe physical places (businesses, parks, landmarks) that should appear on a map.

## Apply

**Blueprint**

```yaml
page_info:
  type: tab
  title: Page Info
  import@:
    type: page/map-location/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{# No template of its own. Read by: #}
{#   page-type/map-page    (a pin per page with a location) #}
{#   theme/schema-graph    (a Place with GeoCoordinates)   #}
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `map_marker` | map | `latitude` number; `longitude` number; `title` string; `summary` string |

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| Each place had to be entered twice, once on its own page and again as a marker on the map page, and the two copies drifted. | The coordinates live on the place's page; every Map page collects them. | community-guide theme, Map page |
| Coordinates were typed as free text, and a swapped or out-of-range value silently dropped the pin. | Number validation with latitude ±90 and longitude ±180. |  |

## Verify

- [ ] A page with both coordinates appears as a pin on a Map page and as a Place (#place) in its JSON-LD.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`example.md`](example.md) (example)
