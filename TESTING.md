# Testing

Every release is tested on real Grav installs, not just linted.

| Grav | PHP | Twig | Result |
|---|---|---|---|
| 2.2.11 | 8.3 | 3 (`twig3_compat: false`) | validate, catalog, 9 usage checks, 456 render checks, 72 API checks pass |
| 1.7.53 | 8.3 | 1.x | validate, catalog, 9 usage checks, 456 render checks pass |

## What runs

Make throwaway installs with `tests/setup-grav.sh` (see
[CONTRIBUTING.md](CONTRIBUTING.md#set-up)), then
`tests/run.sh /path/to/grav [port]`:

1. **Builds a fixture site** (`tests/build-site.sh`): links this plugin and the
   fixture theme (`tests/site/themes/cl-test`, which imports every blueprint
   fragment and includes every partial), copies fixture config, and creates a
   page from each pattern's example plus pages for legacy keys, switches and
   escaping.
2. **`bin/plugin component-library validate`**: every manifest against
   `schema/pattern.schema.json` and its folder; every YAML file parses; every
   blueprint fragment resolves through Grav's `Blueprint` loader as an
   `import@`, with no phantom `fields` field and no untyped field, and provides
   the keys its manifest declares; references between patterns exist; every
   Twig file compiles with the installed Grav's Twig and passes the lint rules;
   every PHP file passes `php -l`.
3. **`bin/plugin component-library catalog --check`**: `catalog.json`,
   `llms.txt`, `docs/CATALOG.md` and the pattern READMEs match the manifests.
4. **Render checks** (`tests/check.py`): fetches every fixture page from
   `php -S` and checks the HTML and JSON-LD: no raw Twig or errors, escaping,
   alt text, `rel="noopener"`, eager header images with `srcset`, map markers
   and list in step, JobPosting and TouristTrip required properties, legacy
   keys, page front matter unable to inject trusted-HTML settings, and more.

5. **Usage checks** (`tests/usage-check.py`): runs
   `bin/plugin component-library usage --json` on the fixture site and checks
   which pages have content in which components, the tab each sits on, theme
   settings, and the templates that include them.

## Admin2 (API) checks

`tests/api-check.py` reads the fixture blueprints through
`/api/v1/blueprints/...`, the path Admin2 uses, and checks the imported
fields, the absence of phantom fields, and the options filled by the plugin's
and the theme's `data-options@` providers. It also checks what the plugin
adds to Admin2: the component labels in page and theme forms (position, text,
none on the theme's own fields), the per-page and site-wide usage endpoints,
the panel's badge count, the context panel and report registrations, the
served panel and report scripts, the MCP tool manifest, detection of a theme's
own copy of a pattern template, and the settings that turn each feature off.
It needs the API, Login and Form
plugins, an account with API access and an API key.
`tests/setup-grav.sh <dir> 2.2.11 --api` sets all of that up and leaves the
key in `<dir>/cl-api-key.txt`, which `tests/run.sh` picks up; CI does the same
on Grav 2.2.11. Against another install:

```bash
CL_API_KEY=grav_... python3 tests/api-check.py http://127.0.0.1:8765
```

It is skipped when no key is available (always on Grav 1.7, which has no API).

## System snippets

The PHP in `patterns/system/*` was also run as real plugins on Grav 2.2.11:
the Admin2 forms snippet (derived default visible through the API, and
`onAdminSave` refreshing the stored value on an API save) and the output
guards snippet (script injected normally, withheld for `DNT: 1` and
`Sec-GPC: 1`, absent from API responses).
