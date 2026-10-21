<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Author registry

`theme/author-registry` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

A list of authors kept once in the theme settings, a page field that picks one by ID with options read live from that list, and an author card partial.

## Use it when

- Several people write for the site and pages credit one of them.
- Author details feed both a visible bio and Schema.org author data.

## Skip it when

- Authors need their own login accounts and profile pages; use Grav user accounts or Flex Objects instead.

## Requires

- `patterns/_shared/macros.html.twig`
- the component-library plugin (or copy the files; see the main README)
- PHP in your theme or plugin class

## Apply

**Blueprint**

```yaml
# theme blueprints.yaml, under form.fields:
authors_section:
  type: section
  title: Authors
  underline: true
  import@:
    type: theme/author-registry/blueprint
    context: 'plugin://component-library/patterns'

# page blueprint, on the tab that holds page info:
page_info:
  type: tab
  title: Page Info
  import@:
    type: theme/author-registry/page
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{% include 'patterns/theme/author-registry/author-bio.html.twig' with { heading: 'About the author', heading_level: 2 } %}
```

**Php**

```php
// Only needed WITHOUT the component-library plugin. In your theme class:
public static function getSubscribedEvents(): array
{
    return ['onThemeInitialized' => ['onThemeInitialized', 0]];
}
public function onThemeInitialized(): void
{
    if (method_exists(\Grav\Common\Data\Blueprint::class, 'addAllowedDynamicCallable')) {
        \Grav\Common\Data\Blueprint::addAllowedDynamicCallable(self::class . '::authorOptions');
    }
}
// ...then copy authorOptions() from component-library.php and change page.yaml's
// data-options@ to '\Grav\Theme\YourTheme::authorOptions'.
```

## Stored data

Scope: `theme`

| Key | Type | Notes |
|---|---|---|
| `authors` | list | `authorid` lowercase id, stable; `authorname` string; `jobtitle` string; `bio` markdown; `image` file map (user://assets/authors); `authorpageurl` path or URL; `socialmedia` list of { socialurl, label, icon } |
| `page.header.author` | string | An authorid from the registry |

## Older keys

- `authors.*.picked_image`: Read when image is empty, from theme://assets/images/authors/&lt;file&gt;.
- `authors.*.title`: Ignored. It only set an image tooltip, which repeated the name.

## Markup classes

`cl-author` `cl-author__heading` `cl-author__image` `cl-author__text` `cl-author__name` `cl-author__role` `cl-author__bio` `cl-author__social`

Extra classes can be passed for these slots: `root`, `image`, `name`, `role`, `bio`, `social`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| Page blueprints hard-coded the author dropdown ("Jane Doe (Author ID: jd)"), so every new author meant editing a YAML file in each theme, and the list drifted from the registry. | data-options@ builds the options from the theme's authors list when the form opens, in both Admins. | community-guide theme and Gantry child theme |
| The author select's default was a person's full name while the option keys were initials, so the default never matched any option and new pages showed a blank, unsaved author. | Default is '' (None); the provider always includes a None option. | Gantry child theme page blueprint |
| Author photos were uploaded into the theme folder and disappeared on theme updates. | Uploads go to user://assets/authors. | community-guide and Gantry-based themes |
| Changing an author's ID silently unlinked every page that used it. | The ID field is validated (lowercase pattern, required) and its help text says pages store it. |  |
| Icon-only social links had no accessible name. | A per-link label, with a "Name on host" fallback, used as aria-label. |  |
| Grav 2.0.11+ refuses a data-options@ Class::method provider that is not on its allowlist whenever the blueprint is not treated as a trusted file, and registering it behind isAdmin() misses Admin2, whose requests go through the API where isAdmin() is false. | The plugin registers the provider with Blueprint::addAllowedDynamicCallable() in onPluginsInitialized, for every request type. | Grav 2 Admin2 migration |

## Verify

- [ ] GET /api/v1/blueprints/pages/&lt;template&gt; returns header.author with one option per registry entry plus None.
- [ ] A page whose author is in the registry renders aside.cl-author with the name; an unknown ID renders nothing.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`author-bio.html.twig`](author-bio.html.twig) (template)
- [`page.yaml`](page.yaml) (snippets)
- [`example.yaml`](example.yaml) (example)
