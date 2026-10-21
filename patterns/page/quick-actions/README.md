<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Quick actions (link blocks)

`page/quick-actions` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

A navigation block of shortcut cards (label, icon, one-line description) in two to four columns.

## Use it when

- A home or landing page points visitors to their most common tasks.
- Replacing a Gantry "link blocks" particle.

## Skip it when

- The cards need images or long text; use [page/tiles](../../page/tiles/README.md).

## Requires

- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
home:
  type: tab
  title: Home Page
  import@:
    type: page/quick-actions/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{% include 'patterns/page/quick-actions/quick-actions.html.twig' with { heading_level: 2 } %}
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `quick_actions_title` | string |  |
| `quick_actions_columns` | 2 \| 3 \| 4 |  |
| `quick_actions` | list | `label` string; `link` path or URL; `icon` icon class; `description` string |

## Markup classes

`cl-quick-actions` `cl-quick-actions__heading` `cl-quick-actions__grid` `cl-quick-actions__grid--2` `cl-quick-actions__grid--3` `cl-quick-actions__grid--4` `cl-quick-actions__item` `cl-quick-actions__icon` `cl-quick-actions__text` `cl-quick-actions__label` `cl-quick-actions__description`

Extra classes can be passed for these slots: `root`, `heading`, `grid`, `item`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The block's default heading named one specific town, so any other site showed the wrong heading until an editor noticed. | No default heading; without one the nav gets aria-label="Quick links" instead. | community-guide theme |
| The heading id was fixed, so two blocks on one page produced duplicate ids and a broken aria-labelledby. | id parameter. |  |
| Shortcuts saved with only a label (or only a link) rendered as dead cards. | Label and link are required in the blueprint and filtered in the template. |  |
| The Gantry link-blocks particle stored its links in the layout, not the page, so editors had to use the Gantry admin to change them. | The links are page fields, edited with the page. | Gantry link-blocks particle |

## Verify

- [ ] The block is a &lt;nav&gt; with an accessible name (aria-labelledby or aria-label).
- [ ] Items lacking a label or link do not render.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`quick-actions.html.twig`](quick-actions.html.twig) (template)
- [`example.md`](example.md) (example)
