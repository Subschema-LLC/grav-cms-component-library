<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Plugin settings that work in Admin2

`system/admin2-plugin-forms` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11)

How a plugin's settings form behaves in Grav 2's Admin2 (an API client that reads saved YAML), and the event wiring that keeps derived or read-only values correct in both Admins.

## Use it when

- A plugin's settings form shows values computed by the plugin, or reacts to a save.
- A plugin worked in Grav 1.7's Admin and shows blank or stale fields in Admin2.

## Skip it when

- The form is plain fields with no computed values; blueprints carry over to Admin2 unchanged.

## Requires

- PHP in your theme or plugin class

## Apply

**PHP or shell**

```php
// getSubscribedEvents(): subscribe form events unconditionally
'onApiBlueprintResolved' => ['onApiBlueprintResolved', 0],   // Admin2: adjust the form definition
'onAdminSave'            => ['onAdminSave', 0],              // both Admins: adjust values being saved
// The event carries context ('plugin', 'theme', 'page', 'account'), the
// plugin or theme name (or template for pages), and the serialized `fields`.
```

**Blueprint**

```yaml
site_id:
  type: text
  label: Site ID
  disabled: true
```

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| A plugin set a display value with $this-&gt;config-&gt;set() for the settings form; it showed in classic Admin but stayed blank in Admin2. | Admin2 loads values from the saved YAML through the API, never from runtime config. Supply the value as the field's default in onApiBlueprintResolved, and persist it in onAdminSave. | AGGREGATE plugin v1.1.1 |
| The browser could not compute a value from a secret field, because Admin2 sends secrets masked. | Derive the value on the server (onApiBlueprintResolved / onAdminSave), never in the browser. | AGGREGATE plugin v1.1.1 |
| Form listeners registered inside onPluginsInitialized behind isAdmin() never ran in Admin2, because its API requests are not "admin" requests while plugins initialize. | Subscribe onApiBlueprintResolved and onAdminSave in getSubscribedEvents(); keep isAdmin() only for classic-Admin-specific work. | AGGREGATE plugin; another plugin's GPM review (connect flow never ran under Admin2) |
| An onAdminSave handler changed the wrong object, because the event fires for every save (pages, users, other plugins). | isOwnConfiguration() checks the storage file (plugins/&lt;slug&gt;.yaml), falling back to the classic Admin route. | AGGREGATE plugin |
| After saving a new secret, Admin2 still showed the value derived from the old one. | onAdminSave stores the refreshed derived value, which Admin2 reloads. | AGGREGATE plugin v1.1.1 |

## Verify

- [ ] GET /api/v1/blueprints/plugins/&lt;slug&gt; shows the derived field with a default.
- [ ] Saving a new secret in Admin2, then reloading the page, shows the new derived value.

## Files

- [`Admin2FormsPlugin.php`](Admin2FormsPlugin.php) (snippets)
- [`blueprints-snippet.yaml`](blueprints-snippet.yaml) (snippets)
