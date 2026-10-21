<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Data layer

`theme/datalayer` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

A site-agnostic window.dataLayer helper loaded in the head, configured from theme settings through a JSON block, with declarative data-datalayer-event attributes instead of inline scripts.

## Use it when

- The site uses a tag manager (GTM, a self-hosted TMS) that reads window.dataLayer.
- Templates need to report events such as "map_view" or "weather_view".

## Requires

- the component-library plugin (or copy the files; see the main README)

## Apply

**Blueprint**

```yaml
scripts_section:
  type: section
  title: Tracking & Scripts
  underline: true
  import@1:
    type: theme/datalayer/blueprint
    context: 'plugin://component-library/patterns'
  import@2:
    type: theme/head-scripts/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{% block javascripts %}
    {% include 'patterns/theme/datalayer/datalayer.html.twig' %}
    ...
{% endblock %}

{# Emit an event when an element is on the page: #}
<div id="map" data-datalayer-event="map_view"></div>
```

**Config**

```yaml
# user/env/staging.example.com/config/themes/<theme>.yaml
datalayer:
  env: staging
```

## Stored data

Scope: `theme`

| Key | Type | Notes |
|---|---|---|
| `datalayer.enabled` | bool |  |
| `datalayer.app` | string | customData.app; default site.title |
| `datalayer.env` | production \| staging \| development |  |
| `datalayer.collection_method` | string |  |

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The data layer script hard-coded the app name and "PROD", so staging and local traffic was reported as production and the file had to be edited per site. | App name and environment come from theme settings (overridable per environment folder) through a JSON config block. | community-guide theme |
| The script loaded at the bottom of the page, so tags in the head pushed events before dataLayer helpers existed. | Registered in the head asset group at high priority. | community-guide theme, "move dataLayer.js to the head" |
| Each template added its own inline snippet to fire an event, and those had to be rewritten twice to wait for DOMContentLoaded. | Templates add data-datalayer-event="name"; the script emits each name once when the DOM is ready. No inline JavaScript, which also keeps a strict Content-Security-Policy possible. | community-guide theme, weather_view and map_view tracking |

## Verify

- [ ] The page head contains datalayer.js and a
- [ ] In the browser console, window.dataLayer contains a "metadata" event after load, and one event per distinct data-datalayer-event name.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`datalayer.html.twig`](datalayer.html.twig) (template)
- [`datalayer.js`](datalayer.js) (assets)
