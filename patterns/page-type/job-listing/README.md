<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Job listing and postings

`page-type/job-listing` · v1.0.0 · stable · Grav 1.7, 2.0 (tested on 2.2.11, 1.7.53)

Two page types, a jobs page that lists its open child postings automatically and a job posting with employment type, location, pay, closing date and JobPosting structured data.

## Use it when

- An organization posts openings on its site and wants them found by job search engines.
- Replacing the Gantry job-list and job-details particles.

## Skip it when

- Jobs live in an applicant tracking system; link to it or embed its widget with [page/embed-block](../../page/embed-block/README.md).

## Requires

- [theme/schema-graph](../../theme/schema-graph/README.md)
- [page/page-heading](../../page/page-heading/README.md)
- `patterns/_shared/macros.html.twig`

## Apply

**Blueprint**

```yaml
# Copy page-blueprint-job.yaml  -> user/themes/<theme>/blueprints/job.yaml
# Copy page-blueprint-jobs.yaml -> user/themes/<theme>/blueprints/jobs.yaml
```

**Template**

```twig
# Copy job.html.twig and jobs.html.twig to user/themes/<theme>/templates/
# and adjust their extends/blocks. Then create /jobs (template jobs) with one
# child page per opening (template job).
```

## Stored data

Scope: `page.header`

| Key | Type | Notes |
|---|---|---|
| `jobtitle` | string |  |
| `jobdescription` | markdown |  |
| `job` | map | `employment_type` list of FULL_TIME \| PART_TIME \| CONTRACTOR \| TEMPORARY \| INTERN \| VOLUNTEER \| PER_DIEM \| OTHER; `date_posted` datetime; `valid_through` datetime; `remote` bool; `location` { street, locality, region, postal_code, country }; `salary` { min, max, unit: HOUR \| DAY \| WEEK \| MONTH \| YEAR, currency }; `apply_url` path or URL |
| `jobs` | map | On the list page: empty_message, show_summary |

## Older keys

- `jobdetails (list page; detailtitle, content, link)`: Rendered when the list page has no child postings. Create one child page per job to get structured data and automatic closing.

## Markup classes

`cl-job` `cl-job__closed` `cl-job__facts` `cl-job__body` `cl-job__action` `cl-job__apply` `cl-jobs` `cl-jobs__list` `cl-jobs__item` `cl-jobs__heading` `cl-jobs__summary` `cl-jobs__empty`

Extra classes can be passed for these slots: `root`, `facts`, `body`, `apply`, `closed`, `item`, `heading`, `summary`, `empty`.

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| The job list was typed by hand on the list page, separately from the job pages, so titles and links drifted and filled positions stayed listed. | The list is built from child pages; postings past their closing date drop off by themselves. | Gantry job-list particle |
| Job pages had only a title and a description, so job search engines had nothing structured to read. | Employment type, location (or remote), pay range, posting and closing dates, and a JobPosting JSON-LD block linked to the site's Organization. | Gantry job-details particle |
| A closed position's page kept saying "Apply". | After the closing date the apply link is replaced by "This position is closed" and validThrough tells search engines. |  |
| The job pages depended on a Gantry outline to render. | Plain page types; any theme includes the partials. | Gantry child theme |

## Verify

- [ ] A child job page with a past valid_through is not listed and shows the closed notice.
- [ ] The posting's JSON-LD has title, description, datePosted, hiringOrganization and jobLocation (or jobLocationType TELECOMMUTE).
- [ ] Run a posting through Google's Rich Results Test.

## Files

- [`fields-posting.yaml`](fields-posting.yaml) (blueprint)
- [`job-posting.html.twig`](job-posting.html.twig) (template)
- [`job-listing.html.twig`](job-listing.html.twig) (templates)
- [`job.html.twig`](job.html.twig) (templates)
- [`jobs.html.twig`](jobs.html.twig) (templates)
- [`fields-listing.yaml`](fields-listing.yaml) (snippets)
- [`page-blueprint-job.yaml`](page-blueprint-job.yaml) (snippets)
- [`page-blueprint-jobs.yaml`](page-blueprint-jobs.yaml) (snippets)
- [`example.md`](example.md) (example)
