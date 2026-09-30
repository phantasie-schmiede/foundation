#!/usr/bin/env sh
#
# Local (non-Docker) test matrix runner.
#
# Usage:
#   sh Build/Scripts/runLocalTests.sh                     full local matrix
#   sh Build/Scripts/runLocalTests.sh <core-version> [--lowest] [--db sqlite|mysql|postgres]
#
# With no arguments it runs the full local matrix - the same legs CI runs
# natively: 12.4, 13.4 and 13.4 at the lowest allowed dependency set. With
# arguments it delegates a single leg to runTests.sh, so every flag (and its
# validation) is shared with the Docker and CI paths.
#
set -eu

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

cd "$SCRIPT_DIR"

if [ $# -eq 0 ]; then
    sh runTests.sh 12.4
    sh runTests.sh 13.4
    sh runTests.sh 13.4 --lowest
    exit 0
fi

exec sh runTests.sh "$@"
