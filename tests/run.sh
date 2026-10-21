#!/usr/bin/env bash
# Full test run against a Grav install:
#   tests/run.sh /path/to/grav [port]
#
# 1. builds the fixture site, 2. validates every pattern (spec, blueprints,
# Twig, lint), 3. checks the generated catalog is current, 4. renders every
# fixture page and runs tests/check.py, 5. reads the blueprints through the
# API (tests/api-check.py) when CL_API_KEY is set or tests/setup-grav.sh --api
# left a key in <grav>/cl-api-key.txt.
set -euo pipefail

GRAV_ROOT="$(cd "${1:?usage: run.sh /path/to/grav [port]}" && pwd)"
PORT="${2:-8765}"
LIB="$(cd "$(dirname "$0")/.." && pwd)"

"$LIB/tests/build-site.sh" "$GRAV_ROOT"
cd "$GRAV_ROOT"
bin/grav clearcache >/dev/null 2>&1 || bin/grav cache >/dev/null 2>&1 || true

echo "== validate"
bin/plugin component-library validate

echo "== catalog"
bin/plugin component-library catalog --check

echo "== render"
php -S "127.0.0.1:${PORT}" system/router.php >"$GRAV_ROOT/logs/cl-test-server.log" 2>&1 &
SERVER=$!
trap 'kill $SERVER 2>/dev/null || true' EXIT
for _ in $(seq 1 50); do
    curl -fs "http://127.0.0.1:${PORT}/" >/dev/null 2>&1 && break
    sleep 0.2
done
python3 "$LIB/tests/check.py" "http://127.0.0.1:${PORT}"

echo "== api"
if [ -z "${CL_API_KEY:-}" ] && [ -s "$GRAV_ROOT/cl-api-key.txt" ]; then
    CL_API_KEY="$(cat "$GRAV_ROOT/cl-api-key.txt")"
    export CL_API_KEY
fi
python3 "$LIB/tests/api-check.py" "http://127.0.0.1:${PORT}"
