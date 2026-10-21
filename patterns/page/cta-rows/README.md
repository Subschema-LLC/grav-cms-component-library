<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Call-to-action rows

`page/cta-rows` · v1.1.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

Editor-managed full-width rows, each with a heading, markdown text, an optional image and one button.

## Use it when

- A page needs a few promotional or "next step" sections the editor can add, reorder and remove.
- Each section has at most one primary action.

## Skip it when

- Content is a grid of equal cards; use [page/tiles](../../page/tiles/README.md).
- The page needs a list of many plain links; use [page/quick-actions](../../page/quick-actions/README.md).

## Requires

- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
# In a page blueprint, on the tab (or section) that should hold the rows:
content_blocks:
  type: tab
  title: Content Blocks
  import@:
    type: page/cta-rows/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{% include 'patterns/page/cta-rows/cta-rows.html.twig' with {
    heading_level: 2,
    classes: { button: 'button is-primary is-large' }
} %}
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `info_cta_section` | list | `title` string; `content` markdown; `cta_section_image` bool (default on): show the image; `cta_section_image_file` page media filename; `cta_section_image_alt` string (empty = decorative); `button_text` string; `button_link` path or URL; `button_new_tab` _self \| _blank |

## Older keys

- `info_cta_section.*.contentid`: Ignored. Rows render in list order; the cid0–cid10 position select is gone.

## Markup classes

`cl-cta-rows` `cl-cta-rows__row` `cl-cta-rows__row--media` `cl-cta-rows__row--single` `cl-cta-rows__text` `cl-cta-rows__heading` `cl-cta-rows__body` `cl-cta-rows__action` `cl-cta-rows__button` `cl-cta-rows__media`

Extra classes can be passed for these slots: `root`, `row`, `heading`, `body`, `button`, `media`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| A row whose image file was renamed or deleted threw an error or left an empty image wrapper. | The template looks the file up in page.media and renders the media column only when it exists. | community-guide theme, v0.2.0 |
| An editor who cleared the alt field for a decorative image still got the row title read out as alt text. | Alt falls back to the title only when the field is absent, not when it is empty (`is defined`, not `\|default`). | community-guide theme, code-scanning fix |
| Alt text also appeared as a hover tooltip, so screen readers announced it twice. | media.html('', alt) passes an empty title. | community-guide theme, v0.2.0 |
| Buttons opening a new tab had no rel attribute, leaving window.opener reachable from the linked site. | cl.link_attrs adds rel="noopener noreferrer" whenever target is _blank. | community-guide theme, v0.2.0 |
| Rows were tied to numbered slots in the layout (a "content section ID" select, cid1–cid10), so adding or reordering rows meant editing two places. | Rows render in list order; drag to reorder. | Gantry 5 info-CTA particle |
| Headings were hard-coded as h2, which broke the outline when rows were placed under another h2. | heading_level parameter. | community-guide theme, v0.2.0 |
| Body text used the code editor field, so editors had no formatting toolbar in Grav 2's Admin. | type markdown, which Admin2 renders with its markdown editor (and Editor Pro when installed). | Admin2 migration |

## Verify

- [ ] The page blueprint shows a "Call-to-Action Rows" section with a Rows list (Admin2 or GET /api/v1/blueprints/pages/&lt;template&gt;).
- [ ] Rendering example.md outputs one .cl-cta-rows__row per non-empty row and no empty .cl-cta-rows__media.
- [ ] Every &lt;a target="_blank"&gt; inside .cl-cta-rows has rel="noopener noreferrer".

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`cta-rows.html.twig`](cta-rows.html.twig) (template)
- [`example.md`](example.md) (example)
