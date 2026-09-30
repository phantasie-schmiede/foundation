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
set -eu

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$REPO_ROOT"

run_leg() {
    SERVICE="$1"
    shift
    docker compose -f Build/testing-docker/docker-compose.yml run --rm "$SERVICE" sh Build/Scripts/runTests.sh "$@"
}

if [ $# -eq 0 ]; then
    run_leg tests          12.4
    run_leg tests          13.4
    run_leg tests          13.4 --lowest
    run_leg tests-mysql    12.4 --db mysql
    run_leg tests-mysql    13.4 --db mysql
    run_leg tests-postgres 12.4 --db postgres
    run_leg tests-postgres 13.4 --db postgres
    exit 0
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

exec docker compose -f Build/testing-docker/docker-compose.yml run --rm "$SERVICE" sh Build/Scripts/runTests.sh $ARGS
