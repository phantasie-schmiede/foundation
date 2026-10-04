#!/usr/bin/env sh
#
# Run the full test matrix (unit + functional) against a single TYPO3 core version.
#
# Usage:
#   sh Build/Scripts/runTests.sh <core-version> [--lowest] [--db sqlite|mysql|postgres]
#
#   <core-version>   Core constraint to test against: 13.4 or 14.3.
#   --lowest         Resolve the lowest allowed dependency versions
#                    (--prefer-lowest --prefer-stable).
#   --db             Database for the functional suite (default: sqlite).
#
# Tracked files are never modified. The core pin and the removal of
# saschaegerer/phpstan-typo3 (which hard-pins typo3/cms-core and cannot
# coexist with the matrix legs) are applied to a generated composer-matrix.json
# that composer addresses through the COMPOSER environment variable. The
# generated composer-matrix.json / composer-matrix.lock pair stays on disk
# between runs (both are git-ignored) so repeat runs can reuse the lock.
# The installed core (.Build/vendor) is intentionally left on the tested
# version.
#
# mysql and postgres read their connection settings from environment
# variables with these defaults:
#   typo3DatabaseHost      mysql: 127.0.0.1, postgres: 127.0.0.1
#   typo3DatabasePort      mysql: 3306,      postgres: 5432
#   typo3DatabaseUsername  mysql: root,      postgres: postgres
#   typo3DatabasePassword  mysql: '',        postgres: postgres
#   typo3DatabaseName      typo3
#
set -eu

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

. "$SCRIPT_DIR/console.sh"

CORE_VERSION=''
LOWEST=''
DB='sqlite'

while [ $# -gt 0 ]; do
    case "$1" in
        --lowest)
            LOWEST='--prefer-lowest --prefer-stable'
            ;;
        --db)
            if [ $# -lt 2 ]; then
                echo "--db requires a value (sqlite, mysql or postgres)" >&2
                exit 1
            fi
            DB="$2"
            shift
            ;;
        13.4|14.3)
            CORE_VERSION="$1"
            ;;
        *)
            echo "Unknown argument: $1" >&2
            echo "Usage: $0 <core-version> [--lowest] [--db sqlite|mysql|postgres]" >&2
            exit 1
            ;;
    esac
    shift
done

if [ -z "$CORE_VERSION" ]; then
    echo "Usage: $0 <core-version> [--lowest] [--db sqlite|mysql|postgres]" >&2
    echo "  <core-version>   13.4 | 14.3" >&2
    exit 1
fi

case "$DB" in
    sqlite|mysql|postgres)
        ;;
    *)
        echo "Unknown database: $DB (sqlite, mysql or postgres)" >&2
        exit 1
        ;;
esac

cd "$REPO_ROOT"

if [ ! -f composer.json ]; then
    echo "composer.json not found in $REPO_ROOT; cannot continue." >&2
    exit 1
fi

PHP_FULL="$(php -r 'echo PHP_VERSION;')"
LOWEST_NOTE=''
if [ -n "$LOWEST" ]; then
    LOWEST_NOTE=' (lowest)'
fi
banner "runTests — TYPO3 ${CORE_VERSION} / ${DB} / PHP ${PHP_FULL}${LOWEST_NOTE}"

# ---------------------------------------------------------------------------
# Step 1/4 - matrix environment.
# composer >= 2.10 blocks installs that hit a Packagist advisory; every supported
# core major currently carries one. Reporting is not dropped - the qa job runs
# `composer audit` separately.
# ---------------------------------------------------------------------------
banner "step 1/4 — matrix environment (composer-matrix.json)"
export COMPOSER=composer-matrix.json
export COMPOSER_NO_SECURITY_BLOCKING=1

cp composer.json composer-matrix.json

if grep -q '"saschaegerer/phpstan-typo3"' composer.json; then
    composer remove --dev --no-update --no-interaction saschaegerer/phpstan-typo3
fi

# Core 13.4.0's ClassLoadingInformationGenerator already uses
# Composer\ClassMapGenerator\ClassMapGenerator, but no package in its lowest
# dependency set (testing-framework 8.2.0, class-alias-loader 1.2.0) pulls
# composer/class-map-generator in - an upstream omission. Require the library
# in the generated composer file on lowest runs so the functional bootstrap
# works.
if [ -n "$LOWEST" ]; then
    composer require --dev --no-update --no-interaction composer/class-map-generator:^1.3.4
fi

# ---------------------------------------------------------------------------
# Step 2/4 - install the pinned core.
# --no-scripts: post-install-cmd only runs npm install and copies a git hook,
# neither of which the test suites need.
# ---------------------------------------------------------------------------
banner "step 2/4 — composer update (typo3/cms-core:^${CORE_VERSION}${LOWEST_NOTE})"
composer update \
    --with "typo3/cms-core:^${CORE_VERSION}" \
    -W \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    ${LOWEST}

# ---------------------------------------------------------------------------
# Functional suite database.
# The testing-framework reads the typo3Database* variables; the functional
# phpunit configuration only pins the sqlite driver as fallback, so exported
# variables win for all drivers.
# ---------------------------------------------------------------------------
case "$DB" in
    mysql)
        export typo3DatabaseDriver=pdo_mysql
        export typo3DatabaseHost="${typo3DatabaseHost:-127.0.0.1}"
        export typo3DatabasePort="${typo3DatabasePort:-3306}"
        export typo3DatabaseUsername="${typo3DatabaseUsername:-root}"
        export typo3DatabasePassword="${typo3DatabasePassword:-}"
        export typo3DatabaseName="${typo3DatabaseName:-typo3}"
        ;;
    postgres)
        export typo3DatabaseDriver=pdo_pgsql
        export typo3DatabaseHost="${typo3DatabaseHost:-127.0.0.1}"
        export typo3DatabasePort="${typo3DatabasePort:-5432}"
        export typo3DatabaseUsername="${typo3DatabaseUsername:-postgres}"
        export typo3DatabasePassword="${typo3DatabasePassword:-postgres}"
        export typo3DatabaseName="${typo3DatabaseName:-typo3}"
        ;;
esac

# ---------------------------------------------------------------------------
# Step 3/4 - unit suite.
# ---------------------------------------------------------------------------
banner "step 3/4 — unit tests"
php .Build/bin/phpunit -c Build/phpunit/UnitTests.xml

# ---------------------------------------------------------------------------
# Step 4/4 - functional suite.
# ---------------------------------------------------------------------------
banner "step 4/4 — functional tests (${DB})"
php .Build/bin/phpunit -c Build/phpunit/FunctionalTests.xml
