<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Hero actions

`page/hero-actions` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

A primary and an optional secondary button under the page heading, each rendered only when complete.

## Use it when

- A home or landing page hero needs one or two clear next steps.

## Requires

- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
home:
  type: tab
  title: Home Page
  import@1:
    type: page/page-heading/blueprint
    context: 'plugin://component-library/patterns'
  import@2:
    type: page/hero-actions/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{% include 'patterns/page/page-heading/page-heading.html.twig' %}
{% include 'patterns/page/hero-actions/hero-actions.html.twig' with {
    classes: { primary: 'button is-primary', secondary: 'button is-light' }
} %}
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `primary_cta_text` | string |  |
| `primary_cta_link` | path or URL |  |
| `secondary_cta_text` | string |  |
| `secondary_cta_link` | path or URL |  |

## Markup classes

`cl-hero-actions` `cl-hero-actions__button` `cl-hero-actions__button--primary` `cl-hero-actions__button--secondary`

Extra classes can be passed for these slots: `root`, `primary`, `secondary`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| A button with a label but no link (or the reverse) rendered as a dead control. | Each button renders only with both values. | community-guide theme home page |
| Hero buttons were not distinguishable in analytics. | data-tracking-info="hero-primary_&lt;label&gt;" for theme/datalayer or any tag manager. |  |

## Verify

- [ ] With only the primary pair set, exactly one link renders.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`hero-actions.html.twig`](hero-actions.html.twig) (template)
- [`example.md`](example.md) (example)
