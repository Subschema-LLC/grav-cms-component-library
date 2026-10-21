#!/usr/bin/env bash
# Installs a throwaway Grav to run the tests against, the same way CI does.
#
#   tests/setup-grav.sh <dir> [grav-version] [--api]
#
#   tests/setup-grav.sh ../grav-2 2.2.11 --api   # Grav 2 with the API checks
#   tests/setup-grav.sh ../grav-17 1.7.53        # Grav 1.7
#
# --api (Grav 2 only) also installs the API, Login and Form plugins, creates a
# test account and writes an API key to <dir>/cl-api-key.txt, which
# tests/run.sh then uses for tests/api-check.py.
#
# Needs git, PHP 8.3 (gd, zip, mbstring, curl, xml, intl) and Composer.
set -euo pipefail

DIR="${1:?usage: setup-grav.sh <dir> [grav-version] [--api]}"
VERSION="${2:-2.2.11}"
API="${3:-}"

# Plugin versions the API checks are known to pass with.
API_PLUGIN=1.0.54
LOGIN_PLUGIN=3.10.1
FORM_PLUGIN=9.1.35

if [ "$API" = "--api" ]; then
    case "$VERSION" in
        1.*) echo "--api needs Grav 2 (the API plugin requires Grav 2.1.5 or later)." >&2; exit 1 ;;
    esac
fi
if [ -e "$DIR" ]; then
    echo "$DIR already exists; choose a new folder (this script never overwrites one)." >&2
    exit 1
fi

git clone --quiet --depth 1 --branch "$VERSION" https://github.com/getgrav/grav "$DIR"
cd "$DIR"
composer install --no-dev --no-interaction --prefer-dist --quiet
mkdir -p logs cache tmp images assets backup user/plugins user/accounts

if [ "$API" = "--api" ]; then
    git clone --quiet --depth 1 --branch "$API_PLUGIN" https://github.com/getgrav/grav-plugin-api user/plugins/api
    git clone --quiet --depth 1 --branch "$LOGIN_PLUGIN" https://github.com/getgrav/grav-plugin-login user/plugins/login
    git clone --quiet --depth 1 --branch "$FORM_PLUGIN" https://github.com/getgrav/grav-plugin-form user/plugins/form

    cat > user/accounts/cl-tester.yaml <<'EOF'
state: enabled
email: cl-tester@example.invalid
fullname: Component Library Tester
access:
  site: { login: true }
  api: { login: true, super: true }
EOF
    bin/plugin api keys:generate --no-ansi -u cl-tester -N "component-library tests" \
        | sed -n 's/.*API Key: *\([^ ]*\).*/\1/p' > cl-api-key.txt
    chmod 600 cl-api-key.txt
    if [ ! -s cl-api-key.txt ]; then
        echo "Could not create an API key for cl-tester." >&2
        exit 1
    fi
fi

echo "Grav $VERSION installed in $(pwd)${API:+ with the API plugins (key in cl-api-key.txt)}."
echo "Next: tests/run.sh $(pwd)"
