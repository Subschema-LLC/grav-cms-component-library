# Testing

Every release is tested on real Grav installs, not just linted.

| Grav | PHP | Twig | Result |
|---|---|---|---|
| 2.2.11 | 8.3 | 3 (`twig3_compat: false`) | validate, catalog, 428 render checks, 41 API checks pass |
| 1.7.53 | 8.3 | 1.x | validate, catalog, 428 render checks pass |

## What runs

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

## Admin2 (API) checks

`tests/api-check.py` reads the fixture blueprints through
`/api/v1/blueprints/...`, the path Admin2 uses, and checks the imported
fields, the absence of phantom fields, and the options filled by the plugin's
and the theme's `data-options@` providers. It needs the API, Login and Form
plugins, an account with API access and an API key:

```bash
CL_API_KEY=grav_... python3 tests/api-check.py http://127.0.0.1:8765
```

It is skipped when `CL_API_KEY` is not set, so CI runs it only when configured.

## System snippets

The PHP in `patterns/system/*` was also run as real plugins on Grav 2.2.11:
the Admin2 forms snippet (derived default visible through the API, and
`onAdminSave` refreshing the stored value on an API save) and the output
guards snippet (script injected normally, withheld for `DNT: 1` and
`Sec-GPC: 1`, absent from API responses).
