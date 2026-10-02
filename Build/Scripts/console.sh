#!/usr/bin/env sh
#
# Console helpers shared by the test matrix scripts.
#
# Usage (from a script in this directory):
#   . "$SCRIPT_DIR/console.sh"
#
# banner prints a prominent heading for matrix sets, legs and steps:
#
#   banner "Leg 2/7 — TYPO3 13.4 / sqlite"      neutral (bold on a TTY)
#   banner green "Leg 2/7 — PASSED"             success
#   banner red "Leg 2/7 — FAILED"               failure
#
# Colors are only used when stdout is a TTY, so CI logs and redirected output
# stay clean. Bold red/green (1;31 / 1;32) are used instead of the plain 31/32
# codes: bold dark red/green has good contrast on light terminal themes and
# stays vivid on dark ones.
#
if [ -t 1 ]; then
    _C_RESET="$(printf '\033[0m')"
    _C_BOLD="$(printf '\033[1m')"
    _C_RED="$(printf '\033[1;31m')"
    _C_GREEN="$(printf '\033[1;32m')"
else
    _C_RESET=''
    _C_BOLD=''
    _C_RED=''
    _C_GREEN=''
fi

banner() {
    COLOR="$_C_BOLD"
    case "${1:-}" in
        red)   COLOR="$_C_RED"; shift ;;
        green) COLOR="$_C_GREEN"; shift ;;
    esac
    printf '\n%s================================================================%s\n %s%s%s\n%s================================================================%s\n' \
        "$COLOR" "$_C_RESET" "$COLOR" "$*" "$_C_RESET" "$COLOR" "$_C_RESET"
}
