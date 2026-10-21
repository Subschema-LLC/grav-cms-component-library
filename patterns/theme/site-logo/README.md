<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Site logo

`theme/site-logo` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

One upload field for the site logo, stored outside the theme folder, with a home-link partial and a URL helper shared with Schema.org output.

## Use it when

- Editors should be able to change the logo from the theme settings.
- The logo URL is also needed elsewhere (Organization schema, social cards).

## Skip it when

- The logo is part of the theme's design and never changes; reference it from the theme's own images folder.

## Requires

- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
# theme blueprints.yaml, under form.fields:
branding_section:
  type: section
  title: Branding
  underline: true
  import@:
    type: theme/site-logo/blueprint
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{% include 'patterns/theme/site-logo/site-logo.html.twig' with { classes: { root: 'navbar-item' } } %}

{# Anywhere else that needs the URL: #}
{% import 'patterns/theme/site-logo/logo.macros.twig' as logo %}
{% set logo_url = logo.src(true)|trim %}
```

## Stored data

Scope: `theme`

| Key | Type | Notes |
|---|---|---|
| `site_logo` | file map | Grav file field value: { 'user/assets/site-logo/logo.svg': { name, path, type, size } } |
| `site_logo_alt` | string |  |
| `site_logo_width` | int |  |

## Older keys

- `picked_site_logo_image`: Read when site_logo is empty, from theme://assets/images/global/&lt;file&gt;. Upload the logo again with the new field to move it out of the theme folder, then clear the old value.

## Markup classes

`cl-site-logo` `cl-site-logo__image` `cl-site-logo__text`

Extra classes can be passed for these slots: `root`, `image`, `text`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The logo was uploaded into the theme's own folder (theme://assets/images/global), so updating or reinstalling the theme deleted it. | The upload destination is user://assets/site-logo. | community-guide and Gantry-based themes |
| Two fields for one image (an upload field plus a file picker), so a freshly uploaded logo did not show until an editor also picked it. | A single file field; the template reads its stored path. | community-guide and Gantry-based themes |
| A blueprint copied between themes left the logo picker pointed at the authors' image folder, so it listed staff photos. | No picker, no folder to drift. | Gantry child theme |
| Schema.org Organization output rebuilt the logo URL by hand and broke when the storage path changed. | logo.src(true) returns the absolute URL for both the new and the legacy field. | community-guide theme, structured-data work |
| The header shifted when the logo loaded (layout shift). | Optional display width written as the width attribute. |  |

## Verify

- [ ] Theme settings show Site Logo, Logo Alt Text and Logo Display Width.
- [ ] With no logo set, the partial renders the site title inside a.cl-site-logo.
- [ ] After an upload, the file is in user/assets/site-logo/ and the img src points there.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`site-logo.html.twig`](site-logo.html.twig) (template)
- [`logo.macros.twig`](logo.macros.twig) (templates)
- [`example.yaml`](example.yaml) (example)
