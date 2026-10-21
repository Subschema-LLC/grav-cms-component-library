<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Page heading and title tag

`page/page-heading` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

Separate SEO title, visible H1, subtitle and byline fields, rendered as an &lt;hgroup&gt; and a &lt;title&gt; element with sensible fallbacks.

## Use it when

- Editors need a search title that differs from the visible heading, or a hero heading with a subtitle.

## Requires

- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
page_info:
  type: tab
  title: Page Info
  import@:
    type: page/page-heading/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{# <head> #}
{% include 'patterns/page/page-heading/title-tag.html.twig' %}
{# page header area #}
{% include 'patterns/page/page-heading/page-heading.html.twig' %}
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `seo_page_title` | string |  |
| `page_name` | string |  |
| `page_name_subtitle` | string |  |
| `page_byline` | string |  |

## Markup classes

`cl-page-heading` `cl-page-heading__title` `cl-page-heading__subtitle` `cl-page-heading__byline`

Extra classes can be passed for these slots: `root`, `title`, `subtitle`, `byline`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The page title was printed into the H1 with no escaping filter (flagged by code scanning in two templates), relying on Grav's autoescape setting. | Every value is escaped explicitly with \|e, so the partial stays safe on sites that turn autoescape off. | community-guide theme, code-scanning fixes |
| The subtitle was an h2, so screen readers listed it as a section heading with no section under it, ahead of the page's real h2 sections. | The subtitle is a &lt;p&gt; inside an &lt;hgroup&gt; (subtitle_tag can keep h2 where CSS depends on it). | community-guide theme home hero |
| The heading fields only appeared when a Gantry particle referenced them, so the same front matter rendered differently on pages with different layouts. | One partial that any template includes. | Gantry page-title particles |
| The home page title read "Home \| Site Name". | title-tag.html.twig shows only the site title on the home page unless an SEO title is set. |  |

## Verify

- [ ] Exactly one &lt;h1&gt; per page, containing page_name or the page title.
- [ ] &lt;title&gt; uses seo_page_title when set.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`page-heading.html.twig`](page-heading.html.twig) (template)
- [`title-tag.html.twig`](title-tag.html.twig) (templates)
- [`example.md`](example.md) (example)
