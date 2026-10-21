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
