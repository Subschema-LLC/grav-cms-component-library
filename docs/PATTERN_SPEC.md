# Pattern specification

Every pattern is one folder under `patterns/<level>/<name>/`. The folder is
self-contained: a person or an AI agent can read it top to bottom and apply the
pattern without opening anything else (except `patterns/_shared/`).

```
patterns/page/cta-rows/
├── pattern.yaml          # manifest (required) — validated against schema/pattern.schema.json
├── README.md             # generated from pattern.yaml by `bin/plugin component-library catalog`
├── blueprint.yaml        # blueprint fragment to import@ (page, theme and plugin patterns)
├── cta-rows.html.twig    # Twig partial (patterns that render output)
├── example.md            # page front matter that exercises every field
└── *.js / *.php / *.yaml # pattern assets, PHP snippets or config fragments, when needed
```

## Levels

| Level       | What it is                                                                 | Where its blueprint goes                     |
|-------------|----------------------------------------------------------------------------|----------------------------------------------|
| `theme`     | Site-wide settings a theme owns: logo, authors, footer, scripts, schema    | the theme's `blueprints.yaml` `form.fields`  |
| `page`      | A content block an editor fills in per page                                | a page blueprint, inside a tab               |
| `page-type` | A whole page template (map page, job listing) built from page patterns     | a theme page blueprint (`blueprints/<type>.yaml`) plus a thin theme template |
| `system`    | Configuration, PHP and plugin practices for Grav 2 (Admin2, security, environments) | `user/config/*.yaml`, plugin PHP, `blueprints.yaml` |

A pattern can touch more than one level (the author registry has a theme
fragment and a page fragment). `level` is where it starts; `touches` lists the rest.

## Manifest (`pattern.yaml`)

| Key              | Required | Meaning |
|------------------|----------|---------|
| `id`             | yes | `<level>/<name>`, equal to the folder path under `patterns/` |
| `name`           | yes | Human title |
| `version`        | yes | SemVer of this pattern. Bump minor for new fields, major when keys or markup change |
| `level`          | yes | `theme`, `page`, `page-type` or `system` |
| `touches`        | no  | Other levels the pattern writes to |
| `status`         | yes | `stable` or `beta` |
| `summary`        | yes | One sentence |
| `use_when`       | yes | List of situations that call for it |
| `avoid_when`     | no  | Situations where another pattern or nothing is better |
| `compatibility`  | yes | `grav: ['1.7', '2.0']` and `tested: [exact versions]` |
| `requires`       | no  | `patterns:` ids, `plugins:` slugs, `php: true` when PHP is involved |
| `data`           | for theme/page/page-type | `scope` (`page.header`, `theme`, `plugin`, `system`) and `keys`: each stored key with its `type` and, for lists, `item` fields |
| `legacy_keys`    | no  | Older keys the template still reads, and how |
| `files`          | yes | `blueprint`, `template`, `example`, `assets`, `snippets` — paths relative to the folder |
| `apply`          | yes | Copy-ready snippets: `blueprint`, `template`, `config`, `php` as needed |
| `classes`        | for rendering patterns | The `cl-*` classes the markup uses, and the `classes` slots a theme can extend |
| `solves`         | yes | Problems this pattern fixes: `problem`, `fix`, and `seen_in` (the kind of site or the release where it was found) |
| `verify`         | yes | Checks that prove the pattern is applied correctly |

## Rules every pattern follows

**Blueprints**

1. `import@` sits on the receiving field, as a sibling of `type:`. Never inside
   its `fields:` list (that yields a phantom text field named `fields`).
2. Several imports on one field (`import@1`, `import@2`, ...) are placed in
   reverse key order, highest number first, before the field's own
   `fields:`. Same-named fields in `fields:` override imported properties in
   place; `unset@: true` removes an imported field.
3. Use `context: 'plugin://component-library/patterns'` when the plugin is
   installed. When copying a fragment into a theme, move it to
   `theme://blueprints/partials/` and change `context` to `theme://blueprints`.
4. Header keys keep their established names. A rename needs a `legacy_keys`
   entry and a template fallback.
5. Prose fields use `type: markdown` (Admin2 gives it the full editor, and
   Editor Pro when installed). `editor` is for code.
6. Images on a page use `filepicker` (works in both Admins). Site-wide uploads
   use `file` with `destination: 'user://assets/<pattern>'`, never `theme://`,
   which a theme update replaces.
7. Toggles store booleans: `validate: { type: bool }` with `1`/`0` options.
8. Option lists that come from data (authors, pages) use `data-options@` with a
   registered provider, never a hard-coded copy of the data.
9. Every coordinate, count and length field has `validate` limits.
10. Every top-level field of a fragment carries `cl_pattern: <id>` (its
    pattern id). Grav keeps the property through `import@` and `extends@`, so
    the plugin can label the fields in Admin2 and report where the pattern is
    used. `validate` fails a fragment field without it. Keep the tags when
    copying a fragment into a theme.

**Templates**

1. Escape every stored value: `{{ value|e }}`. Use `|markdown` for markdown
   fields and `|raw` only for fields the blueprint marks as trusted HTML
   (scripts and embeds editable only by administrators).
2. Accept `heading_level` so the outline stays in order wherever the partial is
   included.
3. Accept `classes`, a map of slot → extra classes, so a theme can add its
   framework's classes (`button is-primary`, `btn btn-primary`) without
   overriding the template.
4. Guard media: `page.media[file]` can be null; render no wrapper when it is.
5. Alt text: `alt is defined and alt is not null ? alt : fallback`, passed raw
   to `media.html('', alt)` (which escapes it). Empty title argument so alt
   text never repeats as a tooltip.
6. `target="_blank"` always comes with `rel="noopener noreferrer"` (use
   `cl.link_attrs`).
7. No inline JavaScript. Data goes into `<script type="application/json">`
   blocks or `data-*` attributes, and a deferred pattern script reads it.
8. JSON in a `<script>` tag uses `json_encode` flags that include
   `JSON_HEX_TAG` (value 1), so a `</script>` in content cannot end the tag.
9. Classes follow BEM with a `cl-` prefix: `cl-<pattern>`, `cl-<pattern>__<element>`,
   `cl-<pattern>--<modifier>`.
10. Templates compile with Twig 3 and `twig3_compat: false`.

## Generated files

`README.md` in each pattern folder, `catalog.json`, `llms.txt` and
`docs/CATALOG.md` are generated from the manifests by
`bin/plugin component-library catalog`. Edit `pattern.yaml`, regenerate, and
commit both. CI fails when they are out of date (`catalog --check`).

## Versioning

The library is tagged as a whole (`v1.2.0`); each pattern also carries its own
`version`, so `catalog.json` tells an agent exactly which pattern changed.
