# v1.3.0
## 10/21/2026

1. [](#new)
    * See where components are used in Grav 2's Admin: a line under each component's fields in page and theme forms names it, its version, and whether the theme renders it with its own template
    * A Components button in the page editor's toolbar shows how many components have content on the page and opens a panel listing every component the page's template offers: filled in or not, which tab, how the theme renders it, and its docs
    * A Component Library report under Tools → Reports: for every component in use, the page types and theme settings that include its fields, the pages with content in it (linked to their editor), the theme templates that include it and the copies the theme keeps of its templates, plus the template checks on the active theme
    * `bin/plugin component-library usage` (`--page=/route`, `--json`) prints the same data, on Grav 1.7 too, and `mcp.yaml` adds `component_library_usage` and `component_library_page_components` tools for the Grav MCP server
    * Settings to turn off the labels, the panel or the report
2. [](#improved)
    * Every fragment field carries `cl_pattern: <id>`, so usage is traced through Grav's import@ and extends@; `validate` requires it. Fragments copied into a theme before this release need the tags added to be counted
    * `tests/run.sh` also checks the usage command, and the API checks cover the Admin2 additions

# v1.2.1
## 10/21/2026

1. [](#bugfix)
    * `theme/site-logo` and `theme/schema-graph`: when both `picked_site_logo_image` and `site_logo` are set, the picked logo is used. On sites that keep several uploads in `site_logo`, the picker value is the editor's choice; before, the first upload was shown instead. If the picked file no longer exists, the upload is used
2. [](#improved)
    * `theme/schema-graph`: the WebSite node carries the site description, and the page name falls back to the header title before the page title

# v1.2.0
## 10/21/2026

1. [](#new)
    * `theme/schema-graph`: `main_entity` parameter points the page entity at another entity by `@id` (for example a map page's `#places` ItemList)
2. [](#improved)
    * Documented that several `import@` on one field are placed in reverse key order, and that same-named fields override imported ones in place; the API checks now assert the order

# v1.1.0
## 10/21/2026

1. [](#improved)
    * `page/cta-rows` and `page/tiles` keep a "Show Image" toggle per row and tile, so an image can be hidden without removing it (sites already store these values)
    * `theme/schema-graph`: Church added to place types; reads the older `schema_type` and `place_type` keys and a post's `preview_image_selected` as the page image
    * `theme/datalayer`: the environment is free text, so existing tag-manager filters (for example `PROD`) keep matching

# v1.0.1
## 10/21/2026

1. [](#bugfix)
    * The optional stylesheet no longer styles `#map`. On sites with their own map styles it made phone-sized maps taller (a minimum height of 24rem); it now styles only `.cl-*` classes, and the test suite fails on any selector outside them
2. [](#improved)
    * A theme can turn the stylesheet off in its own config (`component_library: { load_css: false }`), overriding the plugin setting

# v1.0.0
## 10/21/2026

1. [](#new)
    * First release: 26 patterns (7 theme, 8 page, 4 page types, 7 system), each with a manifest, blueprint fragment and/or partial, example, generated README and the problems it solves
    * Plugin `component-library`: patterns importable with `context: 'plugin://component-library/patterns'`, partials overridable by the theme, `authorOptions` provider for the author registry, optional framework-neutral stylesheet, icon family setting for Admin2's Font Awesome 7 picker
    * CLI: `validate` (manifests, blueprint resolution through Grav's loader, Twig compile, lint, PHP syntax), `lint <path>` (template rules for any theme or plugin), `catalog` (catalog.json, llms.txt, docs/CATALOG.md, READMEs)
    * AI layer: AGENTS.md, CLAUDE.md, llms.txt, catalog.json and a JSON Schema for manifests
    * Fixture site and render checks run against Grav 2.2.11 and 1.7.53; API checks through Admin2's blueprint endpoints
