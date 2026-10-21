<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Header image

`page/header-image` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

A per-page header image with alt text and a show/hide switch, rendered as a responsive, eagerly loaded, high-priority image.

## Use it when

- Pages open with a large photo chosen per page.

## Requires

- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
page_info:
  type: tab
  title: Page Info
  import@:
    type: page/header-image/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{% include 'patterns/page/header-image/header-image.html.twig' with { sizes: '100vw' } %}
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `header_image` | bool | Show the image. The file still feeds structured data when hidden. |
| `header_image_file` | page media filename |  |
| `header_image_alt` | string | Empty means decorative. |

## Markup classes

`cl-header-image` `cl-header-image__img`

Extra classes can be passed for these slots: `root`, `image`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The header image was lazy-loaded like every other image, which delays the page's largest above-the-fold element (Largest Contentful Paint). | loading="eager", fetchpriority="high" and decoding="async". |  |
| One full-size file was sent to phones and desktops alike. | Grav's derivatives() builds a srcset (640–1920 px by default) with a sizes attribute. |  |
| A header image whose file was deleted left an empty wrapper or a broken image. | The partial renders only when the file exists in page.media. | community-guide theme, v0.2.0 |
| The header-image fields only rendered when a Gantry particle was placed in the page's layout. | A plain partial any template includes. | Gantry header-image particle |

## Verify

- [ ] The rendered &lt;img&gt; has loading="eager", fetchpriority="high" and a srcset (for raster images).
- [ ] With header_image false, nothing renders, and theme/schema-graph still uses the file as the page image.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`header-image.html.twig`](header-image.html.twig) (template)
- [`example.md`](example.md) (example)
