#!/usr/bin/env bash
# Full test run against a Grav install:
#   tests/run.sh /path/to/grav [port]
#
# 1. builds the fixture site, 2. validates every pattern (spec, blueprints,
# Twig, lint), 3. checks the generated catalog is current, 4. renders every
# fixture page and runs tests/check.py.
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
