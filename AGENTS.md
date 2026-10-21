# AGENTS.md — using this library as an AI agent

This repository is a library of tested patterns for Grav CMS 1.7 and 2.x:
blueprint fragments, Twig partials, page types and system practices. It is
also a Grav plugin (`component-library`) that makes the patterns importable.
This file tells an agent how to choose, apply and verify them.

## 1. Find the right pattern

1. Read `catalog.json` (or fetch it raw from
   `https://raw.githubusercontent.com/Subschema-LLC/grav-cms-component-library/main/catalog.json`).
   Each entry has `level`, `summary`, `use_when`, `avoid_when`, the exact
   `import` (`type` + `context`) and `include` path, the `data` it stores, and
   the `solves` and `verify` lists.
2. Match the request against `use_when`, then rule out with `avoid_when`.
   Prefer composing several small patterns over writing a new one.
3. Read the pattern's `pattern.yaml` (authoritative) and its blueprint and
   template files before applying it. `README.md` is generated from the
   manifest and says the same thing in prose.
4. Follow `requires`: other pattern ids, plugins, and `php: true` when a theme
   or plugin class must change.

| Need | Start with |
|---|---|
| Site-wide setting edited in theme options | `theme/*` |
| A content block an editor fills in per page | `page/*` |
| A whole page template (map, jobs, tour, modular) | `page-type/*` |
| Plugin, security, environment or template practice | `system/*` |

## 2. Decide how it will be installed

- **Plugin installed** (`user/plugins/component-library` exists): import with
  `context: 'plugin://component-library/patterns'` and include
  `patterns/<level>/<name>/<file>.html.twig`. Theme files at the same path
  under `templates/` override the library's.
- **No plugin** (copy mode): copy the fragment to
  `user/themes/<theme>/blueprints/partials/<name>.yaml` and use
  `context: 'theme://blueprints'` with `type: partials/<name>`; copy templates
  under the theme's `templates/patterns/...` keeping their paths (so the
  includes still resolve), and copy `patterns/_shared/macros.html.twig` too.
  Replace `plugin://component-library/...` asset paths with `theme://...`.
  Patterns whose `requires.plugins` lists `component-library` need their PHP
  pieces moved into the theme class (the pattern's `apply.php` shows how).

## 3. Apply it

### Rules that break generated Grav code most often

1. **`import@` goes on the receiving field, as a sibling of `type:`**, never
   inside that field's `fields:` list. Inside `fields:` Grav silently creates a
   phantom text field named `fields` and none of the imported fields.
   ```yaml
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
   Several imports on one field use `import@1`, `import@2`. Imported fields
   come before the field's own `fields:`.
2. **Keep existing header keys.** Sites already store `info_cta_section`,
   `tiles`, `quick_actions`, `map_marker`, `schema.*`. Never rename stored keys
   while applying a pattern; `legacy_keys` lists what the templates still read.
3. **No Twig in page content.** Put dynamic parts in header fields and render
   them from templates (`page/embed-block`, `page/map-location`). Grav 2 runs
   content Twig in a sandbox, and the content is editor-owned.
4. **Trusted HTML** (scripts, embeds) is read with
   `grav.config.get('theme.<key>')`, never `theme_var()`, which page front
   matter can override.
5. **Escape** every stored value with `|e`; `|markdown` for markdown fields;
   `|raw` only where the pattern documents trusted HTML.
6. **Absolute media URLs** are `url(page.media[file].url, true)`. A media
   object's `url(true)` is still relative (its argument means "reset").
7. **JSON in `<script>`** uses `json_encode(15)` or `json_encode(321)` (both
   include `JSON_HEX_TAG`).
8. **`data-options@` providers** are public static methods registered with
   `Blueprint::addAllowedDynamicCallable()` in an init event that is **not**
   gated on `isAdmin()` (Admin2 works through the API, where it is false).
9. **Admin2 reads saved YAML**, not runtime config. A value set with
   `$config->set()` for a form shows in Grav 1.7's Admin only; see
   `system/admin2-plugin-forms`.
10. **Declare compatibility** in `blueprints.yaml`
    (`compatibility: { grav: ['1.7', '2.0'] }`) only for versions you tested.

### Template parameters every rendering pattern accepts

- `heading_level`: keep the outline in order (2 by default; 3 under an h2).
- `classes`: map of slot to extra classes, for the theme's CSS framework, e.g.
  `{ button: 'button is-primary' }`. Slots are listed in `classes.slots`.
- Data overrides where useful (`rows`, `tiles`, `items`), defaulting to
  `page.header`.

## 4. Verify

Run these from the Grav root after applying anything:

```bash
bin/plugin component-library lint user/themes/<theme>   # template rules
bin/grav clearcache
```

Then check each item in the pattern's `verify` list. Useful probes:

- Blueprint resolved as Admin2 sees it: `GET /api/v1/blueprints/pages/<template>`
  (or the `get_blueprint` tool of the Grav MCP server). Confirm the expected
  `header.*` fields exist and no field is named `fields`.
- Rendered page: fetch it and check the `cl-*` markup, the absence of raw
  `{{ }}`, and that every JSON-LD block parses.

If you changed this repository itself:

```bash
bin/plugin component-library validate    # manifests, blueprints, Twig, lint, php -l
bin/plugin component-library catalog     # regenerate catalog.json, llms.txt, READMEs
tests/run.sh /path/to/grav               # full render suite
```

## 5. Working with the Grav MCP server (grav-mcp)

- Read the target site's shape first: `list_page_templates`, `get_blueprint`.
- Apply patterns by editing theme files (blueprints, templates); then use
  `get_blueprint` again to confirm the fields resolved.
- Write page content as front matter values for the pattern's `data.keys`,
  not as Twig or raw HTML in the Markdown body.

## 6. Adding or changing a pattern

1. Create `patterns/<level>/<name>/` with `pattern.yaml` (see
   `docs/PATTERN_SPEC.md` and `schema/pattern.schema.json`), the blueprint
   fragment, the template, and `example.md` (or `example.yaml`).
2. Record real problems in `solves` with `seen_in`. Do not invent them.
3. Add the example to `tests/build-site.sh` and assertions to `tests/check.py`.
4. Run `validate`, `catalog`, and `tests/run.sh`. Commit the regenerated files.
5. Never put client names, real addresses, keys or salts in this public repo.
