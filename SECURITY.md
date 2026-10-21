# Security policy

## Reporting a vulnerability

Please report security problems privately, not in a public issue:
open [Report a vulnerability](https://github.com/Subschema-LLC/grav-cms-component-library/security/advisories/new)
on this repository's Security tab. Only the maintainers can see the report.

Include the pattern or file, the Grav version, and the steps or page front
matter that show the problem. We'll confirm we received it, keep you updated
while we work on a fix, and credit you in the advisory unless you'd rather we
didn't.

## What counts

- Output a visitor can abuse: a template that renders a stored value
  unescaped, JSON in a `<script>` tag that content can break out of, a link
  that opens a new tab without `rel="noopener"`.
- Page front matter that can change the site-wide trusted HTML an
  administrator set (tracking scripts, embeds), or anything else that lets an
  account do more than its role allows.
- The plugin's PHP and CLI commands (`component-library.php`, `classes/`,
  `cli/`), and the PHP snippets under `patterns/system/`.
- A pattern or document that teaches an unsafe practice.

Problems in Grav itself or in another plugin belong to their projects; see
[Grav's security policy](https://github.com/getgrav/grav/security/policy).

## Supported versions

Fixes go into the latest release. Update with `git pull` in
`user/plugins/component-library`, or download the newest release.
