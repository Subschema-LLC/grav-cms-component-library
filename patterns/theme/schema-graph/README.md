<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Schema.org graph

`theme/schema-graph` · v1.2.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

One linked JSON-LD @graph per page (Organization, WebSite, BreadcrumbList, the page, plus BlogPosting and Place when they apply), with site-wide defaults in the theme and a per-page Schema.org tab.

## Use it when

- Any public site; search engines and AI crawlers read JSON-LD.

## Skip it when

- An SEO plugin already outputs the same entities (two Organization nodes with different @id values confuse parsers). Pick one source.

## Requires

- [theme/site-logo](../../theme/site-logo/README.md)
- [theme/author-registry](../../theme/author-registry/README.md)
- [page/map-location](../../page/map-location/README.md)

## Apply

**Blueprint**

```yaml
# theme blueprints.yaml
schema_section:
  type: section
  title: Structured Data
  underline: true
  import@:
    type: theme/schema-graph/blueprint
    context: 'plugin://component-library/patterns'

# page blueprint: its own tab
schema:
  type: tab
  title: Schema.org
  import@:
    type: theme/schema-graph/page
    context: 'plugin://component-library/patterns'
```

**Template**

```twig
{# in <head> #}
{% include 'patterns/theme/schema-graph/schema-graph.html.twig' with {
    page_types: { contact: 'ContactPage', blog: 'CollectionPage' },
    article_templates: ['item', 'blogpost'],
    main_entity: page.template == 'map' ? '#places' : null
} %}
```

## Stored data

Scope: `theme`

| Key | Type | Notes |
|---|---|---|
| `schema_defaults.organization_type` | string |  |
| `schema_defaults.organization_name` | string |  |
| `schema_defaults.telephone` | string |  |
| `schema_defaults.address_locality` | string |  |
| `schema_defaults.address_region` | string |  |
| `schema_defaults.postal_code` | string |  |
| `schema_defaults.address_country` | ISO 3166-1 alpha-2 |  |
| `schema_defaults.home_about` | map | name, description, latitude, longitude |
| `page.header.schema` | map | `enabled` bool; `type` auto \| WebPage \| AboutPage \| ContactPage \| CollectionPage \| ItemPage \| FAQPage \| BlogPosting; `name` string; `description` string; `image` media filename, path or URL; `place_type` Schema.org Place subtype; `place_name` string; `place_description` string; `latitude` number; `longitude` number; `telephone` string; `street_address` string; `address_locality` string; `address_region` string; `postal_code` string; `author_name` string; `author_url` string; `date_published` datetime; `custom_json` JSON object |

## Older keys

- `page.header.schema_type`: Read when schema.type is auto or empty.
- `page.header.place_type`: Read when schema.place_type is empty.
- `page.header.preview_image_selected`: A preview image chosen for a post is the page image when schema.image is empty (before the header image).

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The JSON-LD partial hard-coded one site's town, region, postal code, coordinates and organization name, so it was wrong on any other site and the address defaults could not be changed without editing Twig. | Site-wide defaults (publisher type and name, address defaults, home-page subject) are theme settings. | community-guide theme, Schema.org work |
| Older Gantry particles wrote Product JSON-LD with a hard-coded brand and seller name, and added Open Graph tags with JavaScript, which most crawlers never run. | Names come from settings; social meta belongs in server-rendered &lt;meta&gt; tags (page.header.metadata), never in JavaScript. | Gantry schema particle |
| Values were printed into JSON by hand inside a script tag, so a "&lt;/script&gt;" in any field could end the tag early. | The whole graph is built as a Twig hash and printed once with json_encode(321), which includes JSON_HEX_TAG. | Gantry schema particle (fixed in the community-guide rewrite) |
| The Map page's ItemList listed only the extra markers, not the pages carrying a Map Location, so structured data disagreed with the visible list. | The map page type outputs its own ItemList from the same list it renders (page-type/map-page); this graph links to it by | community-guide theme, map page |
| Page images in the JSON-LD were relative paths, because media url(true) does not add the host (its argument means "reset"). | url(page.media[file].url, true). | community-guide theme, Schema.org partial |
| The map page's WebPage pointed at its list of places (mainEntity) only while the list was built inside this partial; moving the list to the map template lost the link. | The main_entity parameter sets mainEntity by @id ('#places' is relative to the page URL). | community-guide theme, moving onto this library |
| The Organization logo URL broke when the logo storage moved. | Reads theme/site-logo's field, with the legacy picker as fallback. |  |
| An author override and the author registry disagreed on precedence. | schema.author_name, then the registry entry for page.header.author, then site.author.name. |  |

## Verify

- [ ] Exactly one application/ld+json script with an @graph is in &lt;head&gt;.
- [ ] Paste the page into the Schema Markup Validator (validator.schema.org) with no errors.
- [ ] Setting schema.enabled false on a page removes the block.

## Files

- [`blueprint.yaml`](blueprint.yaml) (blueprint)
- [`schema-graph.html.twig`](schema-graph.html.twig) (template)
- [`page.yaml`](page.yaml) (snippets)
- [`example.md`](example.md) (example)
