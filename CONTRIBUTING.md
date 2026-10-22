# Contributing

Thanks for helping. The library is useful because every pattern comes from a
problem someone actually hit on a Grav site, and because every pattern is
tested on real Grav installs. Contributions keep both of those true.

Good ways to help:

- **Report a bug** in a pattern, the plugin or the docs.
- **Propose a pattern** for a problem you've solved on a Grav site.
- **Fix or improve** an existing pattern, its checks or its docs.
- **Report compatibility**: a Grav or PHP version you've run the tests on.

By taking part you agree to the [code of conduct](CODE_OF_CONDUCT.md).
Security problems go through [SECURITY.md](SECURITY.md), not public issues.

## The one rule for a public repo

Never commit anything private: client or site names, real addresses, phone
numbers or coordinates, API keys, salts, account files, or content copied
from a private site. Fixtures and examples use invented places ("the River
Valley"), `example.org` / `example.invalid`, and test-only keys. In
`solves.seen_in`, describe where a problem was seen in general terms
("community-guide theme, map page"), not by site.

## Before you start

- **Small fixes**: open a pull request directly.
- **New patterns or changes to stored keys or markup**: open an issue first
  (use the *Pattern proposal* form), so we can agree on the shape before you
  build it.

## Set up

You need git, PHP 8.3 (with gd, zip, mbstring, curl, xml and intl), Composer,
Python 3 and curl. No Python packages are needed.

```bash
git clone https://github.com/<you>/grav-plugin-component-library   # your fork
cd grav-plugin-component-library

# Throwaway Grav installs next to the repo, the same way CI makes them.
tests/setup-grav.sh ../grav-2 2.2.11 --api   # Grav 2, with the Admin2 API checks
tests/setup-grav.sh ../grav-17 1.7.53        # Grav 1.7
```

Run everything:

```bash
tests/run.sh ../grav-2          # validate, catalog check, render checks, API checks
tests/run.sh ../grav-17 8766    # second port if you run both at once
```

`tests/run.sh` replaces the install's pages and config, so only point it at a
throwaway install, never a real site. It links your working copy into it as
`user/plugins/component-library`, builds the fixture site, runs
`bin/plugin component-library validate`, checks the generated catalog,
serves the site with `php -S` and runs `tests/check.py` (and
`tests/api-check.py` when the API was set up). [TESTING.md](TESTING.md) says
what each step checks. CI runs the same script on both Grav versions for
every pull request.

While working, from the Grav root:

```bash
bin/plugin component-library validate                 # manifests, blueprints, Twig, lint, php -l
bin/plugin component-library catalog                  # regenerate catalog.json, llms.txt, READMEs
bin/plugin component-library lint user/themes/<theme> # the template rules, on any theme
```

## Changing a pattern

1. Read the pattern's `pattern.yaml`, and the rules in
   [docs/PATTERN_SPEC.md](docs/PATTERN_SPEC.md). The ten
   [rules that break generated Grav code most often](AGENTS.md#rules-that-break-generated-grav-code-most-often)
   apply to every change.
2. Keep stored header keys. If a structure has to change, keep reading the old
   keys in the template and list them under `legacy_keys`.
3. Bump the pattern's `version`: patch for fixes, minor for new fields or
   parameters, major when stored keys or markup change.
4. Add or adjust a fixture page in `tests/build-site.sh` and checks in
   `tests/check.py` (and `tests/api-check.py` for blueprint changes) that fail
   without your change.
5. Run `bin/plugin component-library catalog` and commit the regenerated
   `catalog.json`, `llms.txt`, `docs/CATALOG.md` and pattern `README.md`
   files. Never edit those by hand; CI fails when they're out of date.
6. Run `tests/run.sh` on Grav 2, and on Grav 1.7 if you touched a template or
   blueprint.

## Adding a pattern

A pattern earns its place by solving a real, recurring problem. Before
writing one, check whether composing existing patterns already covers it.

1. Create `patterns/<level>/<name>/` with:
   - `pattern.yaml`, valid against
     [schema/pattern.schema.json](schema/pattern.schema.json);
   - the blueprint fragment (every top-level field tagged
     `cl_pattern: <level>/<name>`), the Twig partial, or both;
   - `example.md` (or `example.yaml`) with front matter that exercises every
     field.
2. Fill in `solves` with the problems you actually hit, each with its fix and
   a general `seen_in`. Don't invent problems to justify the pattern.
3. Templates accept `heading_level`, a `classes` map with the slots listed in
   `classes.slots`, and data overrides where useful; markup uses `cl-*` BEM
   classes; every stored value is escaped.
4. Add the example to `tests/build-site.sh`, wire the fragment and partial
   into the fixture theme (`tests/site/themes/cl-test`), and add checks.
5. Run `catalog`, then `tests/run.sh` on both Grav versions.

## Pull requests

Open pull requests against `development`, the default branch. `main` holds
released versions and only receives merges from `development`.

- One change per pull request, with a description of the problem it solves.
  Link the issue if there is one.
- Say which Grav versions you ran the tests on. For blueprint changes, a
  screenshot of the fields in Admin2 (or Admin on 1.7) helps review.
- CI must pass. If a check is wrong rather than your change, say so in the
  pull request.
- Leave the plugin version and `CHANGELOG.md` to the maintainers; they're
  updated when a release is cut. Describe the user-visible change in your pull
  request so it can go into the changelog.

Using an AI assistant is fine. [AGENTS.md](AGENTS.md) is written for them, and
the same rules apply: you're responsible for what you submit, the tests must
pass, and nothing private may be committed.

## License

Contributions are released under the [MIT License](LICENSE), the same as the
rest of the library.

## For maintainers: cutting a release

1. Update `version` in `blueprints.yaml`, the result table in `TESTING.md` if
   the counts changed, and add a `CHANGELOG.md` entry in Grav's format.
2. Merge `development` into `main` and wait for CI.
3. Tag `main` and publish:
   `gh release create vX.Y.Z --title vX.Y.Z --notes "<changelog entry>"`.
