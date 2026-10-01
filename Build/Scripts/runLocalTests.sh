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
# A failed leg does not stop the matrix; every leg is announced and concluded
# with a banner, a summary table is printed at the end, and the script exits
# non-zero if any leg failed.
#
set -eu

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

cd "$SCRIPT_DIR"

. "$SCRIPT_DIR/console.sh"

LEG_NO=0
LEG_TOTAL=3
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

print_summary() {
    [ -n "$RESULTS" ] || return 0
    banner "Matrix summary (local)"
    printf '%s' "$RESULTS"
    if [ "$FAILED" -eq 0 ]; then
        printf '%sAll %s legs passed.%s\n' "$_C_GREEN" "$LEG_TOTAL" "$_C_RESET"
    else
        printf '%s%s of %s legs failed.%s\n' "$_C_RED" "$FAILED" "$LEG_TOTAL" "$_C_RESET"
    fi
}
trap print_summary EXIT

if [ $# -eq 0 ]; then
    PHP_FULL="$(php -r 'echo PHP_VERSION;')"
    banner "Test matrix (local) — PHP ${PHP_FULL} — legs: 12.4, 13.4, 13.4 (lowest)"

    run_matrix_leg() {
        DESC="$1"
        shift
        LEG_NO=$((LEG_NO + 1))
        banner "Leg ${LEG_NO}/${LEG_TOTAL} — ${DESC}"
        if sh runTests.sh "$@"; then
            record_result "PASSED" "Leg ${LEG_NO}/${LEG_TOTAL} — ${DESC}"
            banner green "Leg ${LEG_NO}/${LEG_TOTAL} — PASSED"
        else
            record_result "FAILED" "Leg ${LEG_NO}/${LEG_TOTAL} — ${DESC}"
            FAILED=$((FAILED + 1))
            banner red "Leg ${LEG_NO}/${LEG_TOTAL} — FAILED"
        fi
    }

    run_matrix_leg "TYPO3 12.4" 12.4
    run_matrix_leg "TYPO3 13.4" 13.4
    run_matrix_leg "TYPO3 13.4 (lowest)" 13.4 --lowest
    exit "$FAILED"
fi

exec sh runTests.sh "$@"
