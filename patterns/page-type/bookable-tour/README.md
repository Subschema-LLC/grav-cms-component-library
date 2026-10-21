<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Bookable tour or experience

`page-type/bookable-tour` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

A page type for a tour or experience with a structured weekly schedule, price and availability, ordered stops, a booking widget from page/embed-block, and TouristTrip structured data with an Offer and itinerary.

## Use it when

- The site sells or promotes recurring tours, classes or experiences booked through a third-party system.
- Replacing the Gantry tour-details, tour-snapshot and product-calendar particles.

## Requires

- [page/embed-block](../../page/embed-block/README.md)
- [page/header-image](../../page/header-image/README.md)
- [page/page-heading](../../page/page-heading/README.md)
- [theme/schema-graph](../../theme/schema-graph/README.md)
- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
# Copy page-blueprint.yaml -> user/themes/<theme>/blueprints/tour.yaml
```

**Template**

```twig
# Copy tour-page.html.twig -> user/themes/<theme>/templates/tour.html.twig
# and adjust extends/blocks.
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `tour` | map | `name` string; `summary` string; `image` page media filename; `duration_minutes` int; `schedule` list of { day, start: HH:MM, end: HH:MM }; `price` number; `currency` ISO 4217; `availability` InStock \| LimitedAvailability \| SoldOut \| PreOrder; `price_valid_until` date; `sku` string; `stops` list of { title, content, image, image_alt, url } |

## Older keys

- `tourname, tourdescription, tourofferprice, tourofferenddate, tourproductid`: Read when the matching tour.* key is empty.
- `tourimageurl`: Used only when it is an https URL and no tour.image or header image is set.
- `tourdayofweek + tourtime (+ tourtimeofday)`: Shown as one "When" line until tour.schedule rows are added.
- `stopdetails (detailtitle, content, image) and stops (description)`: Mapped to tour.stops when tour.stops is empty.
- `rezdyembed`: Not read. Move the calendar's iframe src into an embed (page/embed-block).

## Markup classes

`cl-tour` `cl-tour__summary` `cl-tour__facts` `cl-tour__heading` `cl-tour__schedule` `cl-tour__stops` `cl-tour__stop` `cl-tour__stop--media` `cl-tour__stop-text` `cl-tour__stop-title` `cl-tour__stop-body` `cl-tour__stop-media` `cl-tour__booking`

Extra classes can be passed for these slots: `root`, `facts`, `schedule`, `stops`, `stop`, `booking`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| Tour times were a select of fixed strings ("2:00 pm - 5:00 pm"), so a new departure time meant editing the blueprint, and a tour with two departures could not be described. | A schedule list of day, start and end in 24-hour time, validated, displayed in 12-hour format. | Gantry tour-details blueprint |
| Price, product ID and image were free text "for Schema.org", with the brand and seller names hard-coded in the particle that printed them. | Validated price and currency fields; the provider and seller are the site Organization by @id from theme/schema-graph. | Gantry schema particle |
| The tour was marked up as a Product, and its Open Graph tags were injected with JavaScript after load. | TouristTrip with an Offer and an itinerary of the stops, rendered server-side. Social meta belongs in page.header.metadata. | Gantry schema particle |
| Stops were entered twice, once as a plain list and again as detail sections. | One stops list with optional details, image and link. | Gantry tour-details blueprint |
| The booking calendar was a pasted iframe field per provider. | page/embed-block (by URL, titled, lazy). | Gantry tour-details blueprint |

## Verify

- [ ] The JSON-LD @type is TouristTrip, with offers.price formatted to two decimals and an itinerary whose numberOfItems equals the number of stops.
- [ ] A legacy page with only tourname and tourofferprice still shows its name and price.

## Files

- [`fields.yaml`](fields.yaml) (blueprint)
- [`tour.html.twig`](tour.html.twig) (template)
- [`tour-page.html.twig`](tour-page.html.twig) (templates)
- [`page-blueprint.yaml`](page-blueprint.yaml) (snippets)
- [`example.md`](example.md) (example)
