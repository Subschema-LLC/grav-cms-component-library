<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Head and body-end scripts

`theme/head-scripts` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

Two trusted-HTML theme settings for vendor snippets (analytics, tag managers, chat widgets), rendered in the right place, with a per-page off switch.

## Use it when

- Non-developers need to add or change tracking or widget snippets without editing templates.

## Skip it when

- A dedicated plugin exists for the service (it can respect consent, Do Not Track and Admin users; see [system/frontend-output-guards](../../system/frontend-output-guards/README.md)).
- The snippet only belongs on one page; use [page/embed-block](../../page/embed-block/README.md).

## Apply

**Blueprint**

```yaml
scripts_section:
  type: section
  title: Tracking & Scripts
  underline: true
  import@:
    type: theme/head-scripts/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{# in <head>, after assets.css() and assets.js() #}
{% include 'patterns/theme/head-scripts/head-scripts.html.twig' with { slot: 'head' } %}
{# just before </body> #}
{% include 'patterns/theme/head-scripts/head-scripts.html.twig' with { slot: 'body_end' } %}
```

## Stored data

Scope: `theme`

| Key | Type | Notes |
|---|---|---|
| `tracking_tags` | trusted html |  |
| `body_end_scripts` | trusted html |  |
| `page.header.head_scripts` | bool | Set false on a page to skip both snippets there. |

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| Tracking code was pasted into templates per site, so changing a tag ID meant a theme release. | Theme settings fields, rendered by one partial. | community-guide and Gantry-based themes |
| Tag-manager events fired before the data layer existed because the data layer script loaded at the bottom of the page. | Render the head slot after assets.js() in the head, and load theme/datalayer in the head group so it is defined first. | community-guide theme, data layer fixes |
| Reading the snippets with theme_var() let any page's front matter override them (theme_var checks page.header first), so a page editor could inject a script into the raw-output slot. | Trusted fields are read with grav.config.get('theme.&lt;key&gt;') only. | library review of the community-guide footer and head templates |
| There was no way to keep tracking off a single page. | head_scripts false in a page's front matter. |  |

## Verify

- [ ] The head snippet appears once, inside &lt;head&gt;, after the asset tags.
- [ ] A page with head_scripts false has neither snippet.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`head-scripts.html.twig`](head-scripts.html.twig) (template)
