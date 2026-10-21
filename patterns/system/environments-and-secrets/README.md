<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Environments and secrets

`system/environments-and-secrets` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11)

Use Grav's per-host environment folders for production, staging and local settings, keep salts, keys and accounts out of the repository, and never let local copies talk to live services.

## Use it when

- A site is developed locally and deployed to one or more hosts.
- A theme or site repository is about to be made public.

## Apply

**Config**

```yaml
# user/env/staging.example.com/config/themes/mytheme.yaml
datalayer:
  env: staging
```

**PHP or shell**

```bash
# Run locally with production's environment config:
GRAV_ENVIRONMENT=www.example.com php -S 127.0.0.1:8765 system/router.php
```

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| A site repository contained user/config/security.yaml (the site's salt) and environment folders, which is fine while private but cannot be published as-is. | Ignore security.yaml, accounts and API private keys; publish reusable parts as a separate, generalized repository (this library). | Gantry particle base repository |
| A local checkout used the live site's environment configuration, including form endpoints and third-party integrations, so testing a form could reach real services. | A localhost environment folder that overrides integrations with test values, and a README warning before the first form submit. | community-guide theme README |
| Staging traffic was reported as production analytics. | Per-environment theme config (for example datalayer.env staging in user/env/staging.example.com/config/themes/&lt;theme&gt;.yaml). | community-guide theme data layer |
| Environment folders were kept at the older user/&lt;host&gt;/ location; adding a user/env/ folder (the Grav 1.7+ default) would silently switch every environment there and drop those overrides. | Pick one layout per site; the example documents both and the rule Grav uses to choose (checked in Grav 2.2's Setup). | community-guide site checkout (user/&lt;host&gt;/config) |
| The maps API key has to appear in page source, which reads like a leaked secret and invites copying it into server-side config too. | Treat browser keys as public by design and restrict them by domain in the provider's console; keep server keys and tokens only in untracked environment config. | community-guide theme, HERE Maps key |

## Verify

- [ ] git ls-files | grep -E 'security.yaml|accounts/|api-private' returns nothing.
- [ ] Visiting the site on localhost shows the debugger and uncached pages; production does not.
- [ ] Exactly one of user/env/ or user/&lt;host&gt;/ is used for environment folders.

## Files

- [`environment-example.yaml`](environment-example.yaml) (snippets)
- [`gitignore-snippet.txt`](gitignore-snippet.txt) (snippets)
