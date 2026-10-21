<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Compatibility flag

`system/compatibility-flag` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11)

Declare the Grav generations a plugin or theme was tested on with compatibility.grav, record the exact versions in a comment, and keep dependencies for minimum versions.

## Use it when

- Releasing or updating any plugin or theme for Grav 2.

## Apply

**Blueprint**

```yaml
# blueprints.yaml
# Tested on Grav 2.2.11 (Twig 3, twig3_compat off) and Grav 1.7.53.
compatibility:
  grav: ['1.7', '2.0']
dependencies:
  - { name: grav, version: '>=1.7.0' }
```

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| Without a compatibility block, Grav 2 infers support from dependencies, so a package that requires grav &gt;=1.7.0 is treated as 1.7-only and held back by the migration tool and by GPM's strict mode. | An explicit compatibility.grav list that includes '2.0' once the package is tested on 2.x. | theme and plugin releases for Grav 2 |
| compatibility says which generations, not which releases, so it was unclear what "tested" meant. | A comment above the block with the exact versions and the Twig compatibility setting used in testing, matching what the README says. | community-guide theme and AGGREGATE plugin |
| Raising dependencies to grav &gt;=2.0.0 to "mark" 2.0 support dropped 1.7 sites from updates. | dependencies keeps the true minimum; compatibility carries the tested generations. |  |

## Verify

- [ ] blueprints.yaml has compatibility.grav with every generation you tested on, and no others.
- [ ] On a Grav 2 site, the package installs and updates through GPM without a compatibility warning.

## Files

- [`blueprints-snippet.yaml`](blueprints-snippet.yaml) (snippets)
