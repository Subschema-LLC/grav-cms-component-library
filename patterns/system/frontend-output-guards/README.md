<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Front-end output guards for injecting plugins

`system/frontend-output-guards` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11)

The checks a plugin should pass before injecting scripts into rendered pages, HTML responses only, privacy signals, and staff accounts in both Grav 1.7 and Grav 2 permission models.

## Use it when

- A plugin adds analytics, consent, chat or other scripts to every page.

## Requires

- PHP in your theme or plugin class

## Apply

**PHP or shell**

```php
// HTML only
$page = $this->grav['page'] ?? null;
if (!$page || !method_exists($page, 'templateFormat') || $page->templateFormat() !== 'html') { return false; }
// Privacy signals
if (($_SERVER['HTTP_DNT'] ?? '') === '1' || in_array($_SERVER['HTTP_SEC_GPC'] ?? '', ['1', '?1'], true)) { return false; }
// Staff (Grav 1.7 admin.* and Grav 2 api.*)
if ($user->authenticated && ($user->authorize('admin.login') || $user->authorize('admin.super')
    || $user->authorize('api.access') || $user->authorize('api.super'))) { return false; }
```

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The tracker was injected into non-HTML responses (JSON, XML, Markdown) that happened to contain a closing &lt;/body&gt; in their data. | Inject only when the page's template format is html. | AGGREGATE plugin v1.1.2 |
| Staff visits were counted on Grav 2 sites, because Grav 2 admin accounts can hold only api.access / api.super and no admin.* permissions. | Check both permission families. | AGGREGATE plugin v1.1.2 |
| Global Privacy Control was ignored while Do Not Track was honored. | Treat Sec-GPC 1 (and ?1) like DNT 1. | AGGREGATE plugin v1.1.2 |
| Excluding "logged-in admins" by checking only that a user was logged in also excluded ordinary front-end members. | Exclude by permission, not by login state. | a public plugin's GPM review for Grav 2 |
| Inline tracker configuration blocked a strict Content-Security-Policy. | A single external script tag with defer and referrerpolicy, configured by URL parameters or data attributes, with no inline JavaScript. | AGGREGATE plugin v1.0.0 |

## Verify

- [ ] curl a .json or .xml route of the site; the response has no injected script.
- [ ] curl -H 'Sec-GPC: 1' on the home page returns no tracker script.
- [ ] Logged in as an Admin2-only account, the public pages have no tracker script.

## Files

- [`OutputGuards.php`](OutputGuards.php) (snippets)
