<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Dynamic select options (data-options@)

`system/dynamic-options` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11)

Build a select's options from site data with data-options@ and a static provider on the theme or plugin class, registered for Grav 2's provider allowlist.

## Use it when

- A dropdown lists things that live elsewhere (authors, categories, locations) and must not drift from them.

## Skip it when

- The options are fixed and few; list them in the blueprint.
- The options are pages; use the pages field type.

## Requires

- PHP in your theme or plugin class

## Apply

**Blueprint**

```yaml
header.category:
  type: select
  label: Category
  default: ''
  data-options@: '\Grav\Theme\Mytheme::categoryOptions'
```

**Php**

```php
// In onThemeInitialized (theme) or onPluginsInitialized (plugin), not behind isAdmin():
if (method_exists(\Grav\Common\Data\Blueprint::class, 'addAllowedDynamicCallable')) {
    \Grav\Common\Data\Blueprint::addAllowedDynamicCallable(self::class . '::categoryOptions');
}
```

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| Option lists copied into page blueprints (author names, for example) drifted from the data they described, and differed between themes. | The provider reads the data each time the form opens. | community-guide and Gantry child themes (author select) |
| A select's default was a value that none of its options used, so new pages started with an unsaved, invalid choice. | The provider always returns an explicit empty choice, and the blueprint default is ''. | Gantry child theme page blueprint |
| Grav 2.0.11 and later refuse Class::method providers that are not on the allowlist in some code paths, and registering them behind isAdmin() misses Admin2's API requests. | Blueprint::addAllowedDynamicCallable() on every request, guarded with method_exists for Grav 1.7. | Grav 2 Admin2 migration |
| A provider in a separate class needed a composer.json and autoloader just for one method. | A public static method on the theme or plugin class, which Grav already loads. |  |

## Verify

- [ ] GET /api/v1/blueprints/pages/&lt;template&gt; lists the provider's options on the field.
- [ ] In Admin2, adding an item to the source data shows it in the dropdown without editing the blueprint.

## Files

- [`OptionsProvider.php`](OptionsProvider.php) (snippets)
