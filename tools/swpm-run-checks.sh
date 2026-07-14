#!/usr/bin/env bash
# Run SWPM plugin checks from swpm-check-registry.txt (build ↔ validate alignment).
#
# Usage:
#   ./tools/swpm-run-checks.sh --mode build [--strict-layout] [--amd-prefix MyApp] PLUGIN_DIR
#   ./tools/swpm-run-checks.sh --mode list
#
# Exit 0 = all fail-checks clean; 1 = usage; 2 = at least one fail-check reported issues.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REGISTRY="${SCRIPT_DIR}/swpm-check-registry.txt"

if [ -f "$SCRIPT_DIR/common.sh" ]; then
    # shellcheck source=common.sh
    source "$SCRIPT_DIR/common.sh"
else
    print_success() { echo "OK: $1"; }
    print_error() { echo "ERR: $1" >&2; }
    print_warning() { echo "WARN: $1" >&2; }
    print_info() { echo "INFO: $1"; }
fi

MODE="build"
STRICT_LAYOUT=0
AMD_PREFIX=""
PLUGIN_DIR=""

while [ $# -gt 0 ]; do
    case "$1" in
        --mode)
            MODE="${2:-}"
            shift 2
            ;;
        --strict-layout)
            STRICT_LAYOUT=1
            shift
            ;;
        --amd-prefix)
            AMD_PREFIX="${2:-}"
            shift 2
            ;;
        -h|--help)
            sed -n '2,12p' "$0"
            exit 0
            ;;
        -*)
            print_error "Unbekanntes Flag: $1"
            exit 1
            ;;
        *)
            PLUGIN_DIR="$1"
            shift
            ;;
    esac
done

if [ "$MODE" = "list" ]; then
    echo "=== SWPM Check-Registry ($REGISTRY) ==="
    grep -vE '^\s*(#|$)' "$REGISTRY" | while IFS='|' read -r id sev script needs label; do
        printf '  [%s] %-24s %s (%s)\n' "$sev" "$id" "$label" "$script"
    done
    exit 0
fi

if [ -z "$PLUGIN_DIR" ]; then
    print_error "PLUGIN_DIR fehlt"
    echo "Verwendung: $0 --mode build [--strict-layout] [--amd-prefix X] PLUGIN_DIR"
    exit 1
fi

PLUGIN_DIR="$(cd "$PLUGIN_DIR" && pwd)"
if [ ! -d "$PLUGIN_DIR" ]; then
    print_error "Kein Verzeichnis: $PLUGIN_DIR"
    exit 1
fi

if ! command -v python3 &>/dev/null; then
    print_error "python3 erforderlich für Plugin-Checks"
    exit 2
fi

needs_ok() {
    local needs="$1"
    case "$needs" in
        always) return 0 ;;
        language) [ -d "$PLUGIN_DIR/language" ] || [ -f "$PLUGIN_DIR/language.xml" ] ;;
        templates)
            [ -d "$PLUGIN_DIR/templates" ] || [ -d "$PLUGIN_DIR/acptemplates" ] \
                || compgen -G "$PLUGIN_DIR/*.tpl" >/dev/null
            ;;
        lib) [ -d "$PLUGIN_DIR/lib" ] || [ -d "$PLUGIN_DIR/files/lib" ] ;;
        style) [ -d "$PLUGIN_DIR/style" ] || [ -d "$PLUGIN_DIR/files/style" ] ;;
        js_acp)
            [ -d "$PLUGIN_DIR/js" ] && { [ -d "$PLUGIN_DIR/acptemplates" ] || [ -f "$PLUGIN_DIR/templateListener.xml" ]; }
            ;;
        *) return 0 ;;
    esac
}

# Returns 0 if output indicates real findings
has_findings() {
    local id="$1"
    local rc="$2"
    local out="$3"

    case "$id" in
        template-layout)
            printf '%s\n' "$out" | grep -q 'WARN:' && return 0
            [ "$rc" -eq 2 ] && return 0
            return 1
            ;;
        language-keys)
            [ "$rc" -eq 1 ] || [ "$rc" -eq 2 ] && return 0
            return 1
            ;;
    esac

    # Prefer exit codes when checkers use them (xss, categories, like, amd → 1)
    if [ "$rc" -eq 1 ] || [ "$rc" -eq 2 ]; then
        return 0
    fi
    # Exit 0 + stdout findings (modifiers, foreach, endpoint, notices, style, integrity)
    if [ -n "$out" ]; then
        if printf '%s\n' "$out" | grep -qvE '^(OK:|===|$)'; then
            return 0
        fi
    fi
    return 1
}

FAIL_TOTAL=0
WARN_TOTAL=0
RAN=0

while IFS='|' read -r id sev script needs label; do
    [ -z "${id:-}" ] && continue

    script_path="${SCRIPT_DIR}/${script}"
    if [ ! -f "$script_path" ]; then
        print_warning "Check übersprungen (fehlt): ${script} — ${label}"
        WARN_TOTAL=$((WARN_TOTAL + 1))
        continue
    fi

    if ! needs_ok "$needs"; then
        continue
    fi

    RAN=$((RAN + 1))
    print_info "Check [${sev}]: ${label}..."

    args=()
    case "$id" in
        template-layout)
            [ "$STRICT_LAYOUT" -eq 1 ] && args+=(--strict)
            ;;
        js-amd-exports)
            [ -n "$AMD_PREFIX" ] && args+=(--prefix "$AMD_PREFIX")
            ;;
    esac
    args+=("$PLUGIN_DIR")

    rc=0
    out=$(python3 "$script_path" "${args[@]}" 2>&1) || rc=$?

    if ! has_findings "$id" "$rc" "$out"; then
        print_success "${label} OK"
        continue
    fi

    printf '%s\n' "$out" | head -40

    treat_as_fail=0
    if [ "$sev" = "fail" ]; then
        treat_as_fail=1
    elif [ "$id" = "template-layout" ] && [ "$STRICT_LAYOUT" -eq 1 ]; then
        treat_as_fail=1
    fi

    if [ "$treat_as_fail" -eq 1 ]; then
        print_error "${label}: Probleme — Abbruch"
        FAIL_TOTAL=$((FAIL_TOTAL + 1))
    else
        print_warning "${label}: Warnungen (kein Build-Abbruch)"
        WARN_TOTAL=$((WARN_TOTAL + 1))
    fi
done < <(grep -vE '^\s*(#|$)' "$REGISTRY")

echo ""
print_info "Checks gelaufen: ${RAN} — Fail: ${FAIL_TOTAL}, Warn: ${WARN_TOTAL}"

if [ "$FAIL_TOTAL" -gt 0 ]; then
    exit 2
fi
exit 0
