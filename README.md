# Grav CMS Component Library

[![Test](https://github.com/Subschema-LLC/grav-cms-component-library/actions/workflows/test.yml/badge.svg)](https://github.com/Subschema-LLC/grav-cms-component-library/actions/workflows/test.yml)
[![Grav 1.7 | 2.x](https://img.shields.io/badge/Grav-1.7%20%7C%202.x-2b6cb0)](TESTING.md)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Reusable, tested patterns for [Grav CMS](https://getgrav.org) themes, pages and
plugins: blueprint fragments, Twig partials, page types, and the Grav 2
practices behind them. Each pattern records the real problems it solves, and
the whole library is machine-readable, so people and AI agents can pick and
apply patterns the same way.

- **26 patterns** across four levels: [theme](docs/CATALOG.md#theme-level),
  [page](docs/CATALOG.md#page-level), [page types](docs/CATALOG.md#page-types)
  and [system](docs/CATALOG.md#system-level).
- **Tested on Grav 2.2.11 and 1.7.53**: every blueprint resolves through
  Grav's own loader and through the API that Admin2 uses, every template
  compiles and renders, and 450+ render checks run in CI on every pull request. See [TESTING.md](TESTING.md).
- **Framework-neutral markup** (`cl-*` BEM classes) with a small optional
  stylesheet, and a `classes` parameter on every partial to add Bulma,
  Bootstrap or Tailwind classes without overriding templates.
- **Drop-in for existing content**: header keys match the ones sites already
  store; where a structure changed, templates still read the older keys.

## Install

As a plugin (recommended), from your Grav root:

```bash
git clone https://github.com/Subschema-LLC/grav-cms-component-library user/plugins/component-library
bin/grav clearcache
```

The folder must be named `component-library`. The plugin adds the library to
Twig's template paths (after your theme, so your theme can override any
partial), registers the option providers some blueprints use, and loads an
optional stylesheet you can switch off in its settings.

## Use a pattern

Every pattern is a folder under `patterns/<level>/<name>/` with a
`pattern.yaml`, a generated `README.md`, and the files to use.

**1. Import its fields** into a page or theme blueprint. `import@` goes on the
field that receives them, as a sibling of `type:`. Several imports on one field
are placed in reverse order (here CTA rows appear above tiles):

```yaml
# user/themes/mytheme/blueprints/default.yaml
extends@: default
form:
  fields:
    tabs:
      fields:
        content_blocks:
          type: tab
          title: Content Blocks
          import@1:
            type: page/tiles/blueprint
            context: 'plugin://component-library/patterns'
          import@2:
            type: page/cta-rows/blueprint
            context: 'plugin://component-library/patterns'
```

**2. Include its partial** in a template, with your framework's classes if you want them:

```twig
{% include 'patterns/page/tiles/tiles.html.twig' with {
    heading_level: 2,
    classes: { button: 'button is-primary' }
} %}
{% include 'patterns/page/cta-rows/cta-rows.html.twig' %}
```

**3. Check it** with the pattern's `verify` list, and lint your theme:

```bash
bin/plugin component-library lint user/themes/mytheme
```

Page types (map page, job listing, bookable tour, modular page) also ship a
page blueprint and a template scaffold to copy into your theme; their READMEs
list the steps.

### Without the plugin

Copy what you need into your theme: fragments to
`blueprints/partials/<name>.yaml` (then `context: 'theme://blueprints'`,
`type: partials/<name>`), templates under `templates/patterns/` with the same
paths, and `patterns/_shared/macros.html.twig`. Patterns that list the plugin
under **Requires** explain what to move into your theme class instead.

## What's inside

| Level | Patterns |
|---|---|
| Theme | site logo, author registry, footer columns, head and body-end scripts, data layer, HERE map with conditional loading, Schema.org graph |
| Page | page heading and title tag, header image, hero actions, quick actions, tiles, CTA rows, map location, embeds and page scripts |
| Page types | map page, job listing and postings, bookable tour, modular page |
| System | compatibility flag, Admin2-safe plugin forms, dynamic select options, front-end output guards, Twig in content, template hygiene, environments and secrets |

The full list with summaries is in [docs/CATALOG.md](docs/CATALOG.md).

## For AI agents

- [`AGENTS.md`](AGENTS.md): how to choose, apply and verify patterns, and the
  Grav rules generated code most often gets wrong.
- [`catalog.json`](catalog.json): every pattern with its import type, include
  path, stored data, problems solved and verification steps.
- [`llms.txt`](llms.txt): an index of raw file links.
- Works alongside the [Grav MCP server](https://learn.getgrav.org/20/advanced/mcp-server):
  read a site's blueprints with `get_blueprint`, apply patterns, and confirm
  the fields resolved.

## Contributing

Bug reports, fixes and new patterns are welcome. [CONTRIBUTING.md](CONTRIBUTING.md)
covers setting up throwaway Grav installs (`tests/setup-grav.sh`), running the
tests, and what a new pattern needs: real problems in `solves`, a fixture page
and checks, and passing CI on Grav 1.7 and 2.x. The pattern format is in
[docs/PATTERN_SPEC.md](docs/PATTERN_SPEC.md).

Please follow the [code of conduct](CODE_OF_CONDUCT.md), and report security
problems privately as described in [SECURITY.md](SECURITY.md).

## License

MIT. See [LICENSE](LICENSE).
