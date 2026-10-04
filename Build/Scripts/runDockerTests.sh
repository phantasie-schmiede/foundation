#!/usr/bin/env sh
#
# Docker test matrix runner.
#
# Usage:
#   sh Build/Scripts/runDockerTests.sh                     full Docker matrix
#   sh Build/Scripts/runDockerTests.sh <core-version> [--lowest] [--db sqlite|mysql|postgres]
#
# With no arguments it runs the full Docker matrix - the sqlite legs 12.4,
# 13.4 and 13.4 --lowest plus mysql and postgres legs on both cores - using
# the images built by buildTestImages.sh (select the PHP version via
# PHP_VERSION before calling). With arguments it runs a single leg: --db
# mysql/postgres selects the matching compose service (tests-mysql /
# tests-postgres) which carries the database connection settings and starts
# the database container. All remaining arguments are forwarded to
# runTests.sh inside the container.
#
# A failed leg does not stop the matrix; every leg is announced and concluded
# with a banner, a summary table is printed at the end, and the script exits
# non-zero if any leg failed. Afterwards the compose project is torn down
# again, so no database container keeps running (the trap also covers
# interrupted runs; the named composer-home volume is kept).
#
set -eu

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$REPO_ROOT"

. "$SCRIPT_DIR/console.sh"

compose() {
    docker compose -f Build/testing-docker/docker-compose.yml "$@"
}

LEG_NO=0
LEG_TOTAL=7
RESULTS=''
FAILED=0

record_result() {
    case "$1" in
        PASSED) COLOR="$_C_GREEN" ;;
        *)      COLOR="$_C_RED" ;;
    esac
    RESULTS="${RESULTS}  ${COLOR}$1${_C_RESET}  $2
"
}

# "compose run --rm" removes the test container but leaves the database
# services running. Stop them (and the project network) after the run; the
# data lives on tmpfs, so nothing is lost.
cleanup() {
    if ! compose down > /dev/null 2>&1; then
        echo "Warning: could not stop the database containers (docker compose down failed)." >&2
    fi
}

print_summary() {
    [ -n "$RESULTS" ] || return 0
    banner "Matrix summary (docker)"
    printf '%s' "$RESULTS"
    if [ "$FAILED" -eq 0 ]; then
        printf '%sAll %s legs passed.%s\n' "$_C_GREEN" "$LEG_TOTAL" "$_C_RESET"
    else
        printf '%s%s of %s legs failed.%s\n' "$_C_RED" "$FAILED" "$LEG_TOTAL" "$_C_RESET"
    fi
}

on_exit() {
    cleanup
    print_summary
}
trap on_exit EXIT

run_leg() {
    SERVICE="$1"
    shift
    compose run --rm "$SERVICE" sh Build/Scripts/runTests.sh "$@"
}

if [ $# -eq 0 ]; then
    PHP_TAG="${PHP_VERSION:-8.4}"
    banner "Test matrix (docker) — PHP ${PHP_TAG} (image psbits/foundation-test:${PHP_TAG})"
    banner "Legs: 12.4/sqlite, 13.4/sqlite, 13.4-lowest/sqlite, 12.4/mysql, 13.4/mysql, 12.4/postgres, 13.4/postgres"

    run_matrix_leg() {
        SERVICE="$1"
        DESC="$2"
        shift 2
        LEG_NO=$((LEG_NO + 1))
        banner "Leg ${LEG_NO}/${LEG_TOTAL} — ${DESC}"
        if compose run --rm "$SERVICE" sh Build/Scripts/runTests.sh "$@"; then
            record_result "PASSED" "Leg ${LEG_NO}/${LEG_TOTAL} — ${DESC}"
            banner green "Leg ${LEG_NO}/${LEG_TOTAL} — PASSED"
        else
            record_result "FAILED" "Leg ${LEG_NO}/${LEG_TOTAL} — ${DESC}"
            FAILED=$((FAILED + 1))
            banner red "Leg ${LEG_NO}/${LEG_TOTAL} — FAILED"
        fi
    }

    run_matrix_leg tests          "TYPO3 12.4 / sqlite"           12.4
    run_matrix_leg tests          "TYPO3 13.4 / sqlite"           13.4
    run_matrix_leg tests          "TYPO3 13.4 (lowest) / sqlite"  13.4 --lowest
    run_matrix_leg tests-mysql    "TYPO3 12.4 / mysql"            12.4 --db mysql
    run_matrix_leg tests-mysql    "TYPO3 13.4 / mysql"            13.4 --db mysql
    run_matrix_leg tests-postgres "TYPO3 12.4 / postgres"         12.4 --db postgres
    run_matrix_leg tests-postgres "TYPO3 13.4 / postgres"         13.4 --db postgres
    exit "$FAILED"
fi

# Preserve the original arguments (space-joined; the accepted flags and
# values never contain spaces or glob characters) so the parse loop below
# can consume them without losing them for the forwarded run.
set -f
ARGS=''
for arg in "$@"; do
    ARGS="$ARGS $arg"
done
set +f

DB='sqlite'
set -- $ARGS
while [ $# -gt 0 ]; do
    case "$1" in
        --db)
            shift
            if [ $# -eq 0 ]; then
                echo "--db requires a value (sqlite, mysql or postgres)" >&2
                exit 1
            fi
            DB="$1"
            ;;
    esac
    shift
done

case "$DB" in
    mysql)    SERVICE='tests-mysql' ;;
    postgres) SERVICE='tests-postgres' ;;
    *)        SERVICE='tests' ;;
esac

# No exec here, so the EXIT trap (cleanup) still runs. The tokens are safe
# for word splitting (see above).
set -f
run_leg "$SERVICE" $ARGS
