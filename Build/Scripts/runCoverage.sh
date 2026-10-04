#!/usr/bin/env sh
#
# Run the unit + functional suites with coverage, enforce the per-suite
# coverage baseline and write clover/cobertura/html reports.
#
# Usage:
#   sh Build/Scripts/runCoverage.sh [--update-baseline]
#
#   --update-baseline  Rewrite the baseline file with the measured values
#                      instead of checking it (requires a coverage driver).
#
# A PHP coverage driver (pcov or xdebug) is required for the reports and
# the baseline check. Without one the suites still run, just without
# coverage - so local environments without a driver are not blocked. CI
# provides pcov through setup-php.
#
# The baseline lives in Build/Quality/coverage-baseline.json as
# { "unit": <percent|null>, "functional": <percent|null> }. A null entry
# reports the suite's line coverage without enforcing it; a number fails
# the run when the suite's line coverage drops below it by more than
# $COVERAGE_TOLERANCE (to absorb small drift between PHP versions).
#
# The metric is line coverage (executed lines / executable lines, rounded
# to two decimals), read from the project-level metrics element of the
# generated Clover XML - the same number Codecov reports as total
# coverage.
#
# Reports go to .Build/coverage/{unit,functional}/ (git-ignored).
#
set -eu

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

. "$SCRIPT_DIR/console.sh"

BASELINE_FILE="$REPO_ROOT/Build/Quality/coverage-baseline.json"
OUT_DIR="$REPO_ROOT/.Build/coverage"
FAILED=0

# Measured line coverage drifts slightly between PHP versions and
# dependency resolutions (observed around 0.2 points), so a drop only
# fails the run when it exceeds this tolerance.
COVERAGE_TOLERANCE=0.5

if [ ! -f "$BASELINE_FILE" ]; then
    echo "Baseline file not found: $BASELINE_FILE" >&2
    exit 1
fi

cd "$REPO_ROOT"

UPDATE_BASELINE=0
while [ $# -gt 0 ]; do
    case "$1" in
        --update-baseline)
            UPDATE_BASELINE=1
            ;;
        *)
            echo "Unknown argument: $1" >&2
            echo "Usage: $0 [--update-baseline]" >&2
            exit 1
            ;;
    esac
    shift
done

# Coverage driver: prefer pcov, fall back to xdebug.
DRIVER=''
for CANDIDATE in pcov xdebug; do
    if php -m | grep -qx "$CANDIDATE"; then
        DRIVER="$CANDIDATE"
        break
    fi
done

if [ -z "$DRIVER" ]; then
    if [ "$UPDATE_BASELINE" -eq 1 ]; then
        echo "error: --update-baseline requires a coverage driver (pcov or xdebug)" >&2
        exit 1
    fi
    echo "warning: no coverage driver (pcov/xdebug) available - running without coverage" >&2
fi

# Read one baseline entry; prints nothing for null (report-only).
baseline_value() {
    php -r '
        $value = json_decode(file_get_contents($argv[1]), true)[$argv[2]] ?? null;
        echo is_null($value) ? "" : (string) $value;
    ' "$BASELINE_FILE" "$1"
}

# Line coverage percentage (two decimals) from a Clover report, or nothing
# if the report cannot be read.
coverage_percent() {
    php -r '
        $xml = simplexml_load_file($argv[1]);
        if ($xml === false) {
            exit(0);
        }
        $metrics = null;
        if (isset($xml->project->metrics)) {
            $metrics = $xml->project->metrics;
        }
        if ($metrics === null) {
            exit(0);
        }
        $covered = (int) $metrics["coveredstatements"];
        $total   = (int) $metrics["statements"];
        echo $total > 0 ? number_format(100 * $covered / $total, 2, ".", "") : "100.00";
    ' "$1"
}

# Rewrite one baseline entry with the measured value.
update_baseline() {
    SUITE="$1"
    ACTUAL="$2"
    php -r '
        $data = json_decode(file_get_contents($argv[1]), true);
        if (!is_array($data)) {
            fwrite(STDERR, "error: cannot update baseline from invalid JSON: {$argv[1]}\n");
            exit(1);
        }
        $data[$argv[2]] = (float) $argv[3];
        file_put_contents(
            $argv[1],
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );
    ' "$BASELINE_FILE" "$SUITE" "$ACTUAL"
    printf '  %-12s %s%s%s%%  (%s)\n' \
        "$SUITE" "$_C_GREEN" "$ACTUAL" "$_C_RESET" "baseline updated"
}

check_suite() {
    SUITE="$1"
    ACTUAL="$(coverage_percent "$OUT_DIR/$SUITE/clover.xml")"
    BASELINE="$(baseline_value "$SUITE")"

    if [ -z "$ACTUAL" ]; then
        echo "error: could not read ${SUITE} coverage from $OUT_DIR/$SUITE/clover.xml" >&2
        FAILED=1
        return
    fi

    if [ "$UPDATE_BASELINE" -eq 1 ]; then
        update_baseline "$SUITE" "$ACTUAL"
        return
    fi

    if [ -z "$BASELINE" ]; then
        printf '  %-12s %s%%  (baseline not set - reported only)\n' "$SUITE" "$ACTUAL"
        return
    fi

    BELOW="$(php -r 'echo (float) $argv[1] + (float) $argv[2] < (float) $argv[3] ? "1" : "0";' "$ACTUAL" "$COVERAGE_TOLERANCE" "$BASELINE")"
    if [ "$BELOW" = "1" ]; then
        printf '  %-12s %s%s%s%%  (baseline %s%% - %s%s%s)\n' \
            "$SUITE" "$_C_RED" "$ACTUAL" "$_C_RESET" "$BASELINE" "$_C_RED" "below baseline" "$_C_RESET"
        FAILED=1
    else
        printf '  %-12s %s%s%s%%  (baseline %s%% - %s%s%s)\n' \
            "$SUITE" "$_C_GREEN" "$ACTUAL" "$_C_RESET" "$BASELINE" "$_C_GREEN" "ok" "$_C_RESET"
    fi
}

run_suite() {
    SUITE="$1"
    CONFIG="$2"
    OUT="$OUT_DIR/$SUITE"
    rm -rf "$OUT"
    mkdir -p "$OUT"

    if [ -n "$DRIVER" ]; then
        banner "coverage — ${SUITE} (driver: ${DRIVER})"
        # 1G: coverage collection on top of the functional suite's full
        # TYPO3 boots exceeds the default 128M memory limit.
        php -d memory_limit=1G .Build/bin/phpunit -c "$CONFIG" \
            "--coverage-clover=$OUT/clover.xml" \
            "--coverage-cobertura=$OUT/cobertura.xml" \
            "--coverage-html=$OUT/html"
        check_suite "$SUITE"
    else
        banner "coverage — ${SUITE} (no coverage driver - running without coverage)"
        php .Build/bin/phpunit -c "$CONFIG"
    fi
}

run_suite "unit" "Build/phpunit/UnitTests.xml"
run_suite "functional" "Build/phpunit/FunctionalTests.xml"

if [ "$FAILED" -ne 0 ]; then
    banner red "Coverage baseline violated"
    exit 1
fi

if [ "$UPDATE_BASELINE" -eq 1 ]; then
    banner green "Coverage baseline updated"
elif [ -n "$DRIVER" ]; then
    banner green "Coverage baseline ok"
fi
