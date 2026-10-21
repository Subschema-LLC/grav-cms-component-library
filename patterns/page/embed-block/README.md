<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Embeds and page scripts

`page/embed-block` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

Structured fields for third-party embeds (an iframe by URL with a required title and a shape, or a trusted HTML snippet) and page-only scripts, so embed code and Twig stay out of page content.

## Use it when

- A page needs a booking calendar, video, map from another provider, form or widget.
- Migrating content that relied on Twig or raw embed code inside Markdown.

## Skip it when

- The embed appears on every page; use [theme/head-scripts](../../theme/head-scripts/README.md) or a plugin.

## Requires

- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
embeds_tab:
  type: tab
  title: Embeds
  import@:
    type: page/embed-block/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{% include 'patterns/page/embed-block/embed-block.html.twig' %}
{# before </body>: #}
{% include 'patterns/page/embed-block/embed-block.html.twig' with { slot: 'scripts' } %}
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `embeds` | list | `title` string (iframe accessible name); `kind` iframe \| html; `src` https URL; `aspect` 16:9 \| 4:3 \| 1:1 \| fixed; `height` int px (fixed); `html` trusted html; `caption` string |
| `page_scripts` | trusted html |  |

## Older keys

- `rezdyembed (and other per-provider "paste the iframe" fields)`: Not read. Copy the iframe's src into an embed of kind iframe and give it a title.

## Markup classes

`cl-embeds` `cl-embeds__item` `cl-embeds__item--iframe` `cl-embeds__item--html` `cl-embeds__frame` `cl-embeds__frame--16x9` `cl-embeds__frame--4x3` `cl-embeds__frame--1x1` `cl-embeds__frame--fixed` `cl-embeds__html` `cl-embeds__caption`

Extra classes can be passed for these slots: `root`, `item`, `caption`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| Grav 2 runs Twig in page content inside a sandbox (and before 2.0.19 did not run it at all by default), so content that built embeds with Twig printed raw {{ }} or was blocked after migration. | Embeds are header fields rendered by the template, so no Twig runs in content. | Grav 2 migration |
| Editors pasted whole iframe tags from providers, which Grav's save-time XSS check rejects for anyone but super admins, and which usually lacked a title (an unnamed frame for screen readers) and lazy loading. | Kind "Embed by URL" stores only an https URL; the template builds the iframe with a required title, loading="lazy" and a referrer policy. Any editor can use it. | Gantry tour pages (booking-calendar iframe field) |
| Embedded videos and calendars had fixed pixel sizes and overflowed on phones. | Shape classes using aspect-ratio, or a fixed height for calendars and forms. No style attributes, so a strict CSP stays possible. |  |
| Page-specific scripts were pasted into the content, mixing code with prose. | page_scripts, printed before &lt;/body&gt; through slot 'scripts'. | Gantry default page blueprint (page_scripts) |

## Verify

- [ ] An embed of kind iframe without an https src is skipped.
- [ ] Every rendered iframe has a non-empty title and loading="lazy".
- [ ] A non-super-admin saving &lt;script&gt; in the HTML snippet gets Grav's XSS error; saving an iframe URL succeeds.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`embed-block.html.twig`](embed-block.html.twig) (template)
- [`example.md`](example.md) (example)
