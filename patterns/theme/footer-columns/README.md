<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Footer columns

`theme/footer-columns` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

Footer link columns where each column owns its heading and links, plus an optional trusted-HTML embed column, social links and a note line with {year}.

## Use it when

- The site footer has grouped links that editors maintain.

## Skip it when

- The footer mirrors the main menu exactly; render the page tree instead.

## Requires

- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
footer_section:
  type: section
  title: Footer
  underline: true
  import@:
    type: theme/footer-columns/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
<footer class="site-footer">
  {% include 'patterns/theme/footer-columns/footer-columns.html.twig' with { heading_level: 2 } %}
</footer>
```

## Stored data

Scope: `theme`

| Key | Type | Notes |
|---|---|---|
| `footer_columns` | list | `title` string; `links` list of { label, url, target: _self \| _blank } |
| `footer_embed` | trusted html |  |
| `footer_note` | string | {year} and {site} are replaced |

## Older keys

- `footerlinks + footer_column_1_title..footer_column_4_title`: When footer_columns is empty, links are grouped by their cid1–cid4 column value under the matching footer_column_N_title.
- `footerembed`: Used when footer_embed is empty.
- `site.social[].title as an icon name`: Without an icon key, the title is shown as link text. Add icon (for example fa-brands fa-facebook) to each site.social entry for an icon.

## Markup classes

`cl-footer` `cl-footer__columns` `cl-footer__column` `cl-footer__heading` `cl-footer__links` `cl-footer__embed` `cl-footer__social` `cl-footer__note`

Extra classes can be passed for these slots: `root`, `columns`, `column`, `heading`, `links`, `embed`, `social`, `note`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| Each link chose its column from a select whose labels were hard-coded ("Column 1 — Explore"), while the column headings were separate settings, so renaming a heading left the select describing the old one. | Columns are a list; each column holds its own heading and links. | community-guide theme |
| New links defaulted to a "Please select a footer column" value (cid0) and silently never appeared. | A link can only exist inside a column. | community-guide theme |
| The copyright line hard-coded the domain name, so it was wrong on every other site using the theme. | footer_note with {year} and {site} tokens. | community-guide theme |
| The embed column was read with theme_var(), which a page's front matter can override, so a page editor could place raw HTML in every footer. | footer_embed is read from theme config only. | library review |
| New-tab links lacked rel="noopener noreferrer". | cl.link_attrs. |  |
| Adding a fourth link column meant editing the template. | Any number of columns; the theme's grid classes go in through the classes slots. |  |

## Verify

- [ ] Theme settings show Footer Columns, Embed Column and Footer Note.
- [ ] A site with only the old footerlinks keys renders the same columns as before.
- [ ] Columns with no heading and no valid links are skipped.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`footer-columns.html.twig`](footer-columns.html.twig) (template)
- [`example.yaml`](example.yaml) (example)
