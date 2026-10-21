<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Modular page with a general-purpose section

`page-type/modular-page` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

A modular parent template that renders its intro and every module with a linkable id, and a general-purpose "section" module built from page/tiles and page/cta-rows.

## Use it when

- Landing pages are assembled from stacked sections.
- A theme ships no module templates yet and modular pages should work immediately.

## Requires

- [page/tiles](../../page/tiles/README.md)
- [page/cta-rows](../../page/cta-rows/README.md)
- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
# Copy section-blueprint.yaml -> user/themes/<theme>/blueprints/modular/section.yaml
```

**Template**

```twig
# Copy modular.html.twig -> user/themes/<theme>/templates/modular.html.twig
# Copy section.html.twig -> user/themes/<theme>/templates/modular/section.html.twig
# Parent page (modular.md):
#   content:
#     items: '@self.modular'
# Modules: child folders starting with _ (for example _intro/section.md).
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `content.items` | string | '@self.modular' on the parent page |
| `section_heading` | string | On a modular/section module |
| `hide_heading` | bool |  |

## Markup classes

`cl-modular` `cl-modular__intro` `cl-modular__module` `cl-section` `cl-section__heading` `cl-section__body`

Extra classes can be passed for these slots: `root`, `intro`, `module`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The modular template's content block had been commented out, so modular pages rendered an empty body. | A tested modular partial; the scaffold template only includes it. | community-guide theme, v0.2.0 |
| The theme shipped no module templates, so every new modular page needed template work first. | A general-purpose section module (heading, content, tiles, CTA rows). | community-guide theme, v0.2.0 |
| Sections had no ids, so a menu could not link to one. | Each module's wrapper gets its folder slug as the id. |  |

## Verify

- [ ] A modular page with two modules renders two &lt;section&gt; elements whose ids are the module slugs without underscores.
- [ ] Tiles and CTA rows in a module render with h3 headings under the section's h2.

## Files

- [`section-blueprint.yaml`](section-blueprint.yaml) (blueprint)
- [`modular-page.html.twig`](modular-page.html.twig) (template)
- [`section.html.twig`](section.html.twig) (templates)
- [`modular.html.twig`](modular.html.twig) (templates)
- [`example.md`](example.md) (example)
