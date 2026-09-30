#!/usr/bin/env sh
#
# Run the full test matrix (unit + functional) against a single TYPO3 core version.
#
# Usage:
#   sh Build/Scripts/runTests.sh <core-version> [--lowest]
#
#   <core-version>   Core constraint to test against: 12.4 or 13.4.
#   --lowest         Resolve the lowest allowed dependency versions
#                    (--prefer-lowest --prefer-stable).
#
# The script is self-contained: it snapshots composer.json and composer.lock, drops
# saschaegerer/phpstan-typo3 (which hard-pins typo3/cms-core and cannot coexist with
# the full matrix), installs the requested core and runs both suites. composer.json and
# composer.lock are restored on exit - passing, failing or interrupted - so the script
# never leaves the working tree in a modified state. The installed core (.Build/vendor)
# is intentionally left on the tested version.
#
set -eu

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

CORE_VERSION=''
LOWEST=''

for arg in "$@"; do
    case "$arg" in
        --lowest)
            LOWEST='--prefer-lowest --prefer-stable'
            ;;
        12.4|13.4)
            CORE_VERSION="$arg"
            ;;
        *)
            echo "Unknown argument: $arg" >&2
            echo "Usage: $0 <core-version> [--lowest]" >&2
            echo "  <core-version>   12.4 | 13.4" >&2
            exit 1
            ;;
    esac
done

if [ -z "$CORE_VERSION" ]; then
    echo "Usage: $0 <core-version> [--lowest]" >&2
    echo "  <core-version>   12.4 | 13.4" >&2
    exit 1
fi

cd "$REPO_ROOT"

if [ ! -f composer.json ]; then
    echo "composer.json not found in $REPO_ROOT; cannot continue." >&2
    exit 1
fi

# ---------------------------------------------------------------------------
# State guarantee.
# `composer remove --no-update` and `composer update` rewrite composer.json and
# composer.lock. Snapshot both and restore them on exit so a test run - whatever
# its outcome - never mutates the tracked files.
# ---------------------------------------------------------------------------
SNAP_DIR="$(mktemp -d)"
trap '
    if [ -f "$SNAP_DIR/composer.json" ]; then
        cp "$SNAP_DIR/composer.json" composer.json
    fi
    if [ -f "$SNAP_DIR/composer.lock" ]; then
        cp "$SNAP_DIR/composer.lock" composer.lock
    fi
    rm -rf "$SNAP_DIR"
' EXIT

if [ -f composer.json ]; then
    cp composer.json "$SNAP_DIR/composer.json"
fi
if [ -f composer.lock ]; then
    cp composer.lock "$SNAP_DIR/composer.lock"
fi

# composer >= 2.10 blocks installs that hit a Packagist advisory; every supported
# core major currently carries one. Reporting is not dropped - the qa job runs
# `composer audit` separately.
export COMPOSER_NO_SECURITY_BLOCKING=1

# ---------------------------------------------------------------------------
# Drop saschaegerer/phpstan-typo3.
# Both 2.x and 3.0 hard-pin typo3/cms-core to ^13.4.3, so the package cannot
# coexist with the v12 leg of the matrix. Only analyze:php needs it, never the
# test suites, so it is removed for the duration of the run and restored via
# the snapshot above.
# ---------------------------------------------------------------------------
if grep -q '"saschaegerer/phpstan-typo3"' composer.json; then
    composer remove --dev --no-update --no-interaction saschaegerer/phpstan-typo3
fi

# --no-scripts: post-install-cmd only runs npm install and copies a git hook,
# neither of which the test suites need.
composer update \
    --with "typo3/cms-core:^${CORE_VERSION}" \
    -W \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    ${LOWEST}

php .Build/bin/phpunit -c Build/phpunit/UnitTests.xml
php .Build/bin/phpunit -c Build/phpunit/FunctionalTests.xml
