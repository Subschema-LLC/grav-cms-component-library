#!/usr/bin/env python3
"""
Optional: checks the fixture theme's blueprints through the Grav API, the same
path Admin2 uses. Needs the API plugin (and its Login/Form dependencies), a
user with api access, and an API key for that user:

    CL_API_KEY=grav_... python3 tests/api-check.py http://127.0.0.1:8765

Skips (exit 0) when CL_API_KEY is not set.
"""
import json
import os
import sys
import urllib.request

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8765").rstrip("/") + "/api/v1"
KEY = os.environ.get("CL_API_KEY")
if not KEY:
    print("CL_API_KEY not set; skipping API blueprint checks")
    sys.exit(0)

failures, passes = [], 0


def fetch(path):
    req = urllib.request.Request(BASE + path, headers={"X-API-Key": KEY})
    with urllib.request.urlopen(req, timeout=30) as r:
        return json.loads(r.read())["data"]


def names(fields, out=None):
    out = {} if out is None else out
    for f in fields or []:
        if isinstance(f, dict):
            if f.get("name"):
                out[f["name"]] = f
            names(f.get("fields"), out)
    return out


EXPECT = {
    "/blueprints/pages/default": ["header.seo_page_title", "header.header_image_file", "header.author", "header.map_marker.latitude",
                                  "header.map_enabled", "header.primary_cta_text", "header.quick_actions", "header.tiles",
                                  "header.info_cta_section", "header.embeds", "header.schema.type", "header.schema.custom_json"],
    "/blueprints/pages/map": ["header.map_page.include_pages", "header.map_markers"],
    "/blueprints/pages/job": ["header.jobtitle", "header.job.salary.min", "header.job.valid_through"],
    "/blueprints/pages/jobs": ["header.jobs.empty_message"],
    "/blueprints/pages/tour": ["header.tour.schedule", "header.tour.price", "header.embeds"],
    "/blueprints/themes/cl-test": ["site_logo", "authors", "footer_columns", "footer_embed", "datalayer.env", "tracking_tags",
                                   "here_map.api_key", "schema_defaults.organization_type"],
    "/blueprints/plugins/component-library": ["enabled", "load_css", "icon_base_class"],
}

for path, expected in EXPECT.items():
    try:
        found = names(fetch(path).get("fields"))
    except Exception as e:  # noqa: BLE001
        failures.append(f"{path}: {e}")
        continue
    for n in expected:
        if n in found:
            passes += 1
        else:
            failures.append(f"{path}: missing {n}")
    phantom = [n for n in found if n == "fields" or n.endswith(".fields")]
    if phantom:
        failures.append(f"{path}: phantom fields {phantom}")
    else:
        passes += 1
    if path == "/blueprints/pages/default":
        opts = [o.get("value") for o in (found.get("header.author", {}).get("options") or [])]
        if opts == ["", "jd", "rk"]:
            passes += 1
        else:
            failures.append(f"{path}: author options from the registry were {opts}")
        cats = [o.get("value") for o in (found.get("header.category", {}).get("options") or [])]
        if cats == ["", "outdoors", "history"]:
            passes += 1
        else:
            failures.append(f"{path}: theme-provided category options were {cats}")

print(f"{passes} API checks passed, {len(failures)} failed")
for f in failures:
    print("  FAIL", f)
sys.exit(1 if failures else 0)
