#!/usr/bin/env bash
# Resolve SWPM family manifest path and run check-family-deps.py
# Source: source "$SCRIPT_DIR/swpm-family-resolve.sh"
# Or: bash swpm-family-resolve.sh [--manifest PATH] order|list|check

swpm_family_tools_dir() {
    local here
    here="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    echo "$here"
}

swpm_family_main_dir() {
    dirname "$(swpm_family_tools_dir)"
}

# Load optional .env from tools/
swpm_family_load_env() {
    local tools_dir
    tools_dir="$(swpm_family_tools_dir)"
    if [ -f "$tools_dir/.env" ]; then
        set -a
        # shellcheck disable=SC1091
        source "$tools_dir/.env"
        set +a
    fi
}

# Print absolute path to manifest or return 1
swpm_family_find_manifest() {
    local explicit="${1:-}"
    local tools_dir main_dir
    tools_dir="$(swpm_family_tools_dir)"
    main_dir="$(swpm_family_main_dir)"

    if [ -n "$explicit" ]; then
        if [ -f "$explicit" ]; then
            (cd "$(dirname "$explicit")" && echo "$(pwd)/$(basename "$explicit")")
            return 0
        fi
        echo "Manifest nicht gefunden: $explicit" >&2
        return 1
    fi

    swpm_family_load_env
    if [ -n "${WOLTLAB_FAMILY_MANIFEST:-}" ]; then
        local m="$WOLTLAB_FAMILY_MANIFEST"
        if [[ "$m" != /* ]]; then
            # relative to MAIN_DIR or tools/
            if [ -f "$main_dir/$m" ]; then
                echo "$(cd "$(dirname "$main_dir/$m")" && pwd)/$(basename "$m")"
                return 0
            fi
            if [ -f "$tools_dir/$m" ]; then
                echo "$(cd "$(dirname "$tools_dir/$m")" && pwd)/$(basename "$m")"
                return 0
            fi
        elif [ -f "$m" ]; then
            echo "$m"
            return 0
        fi
        echo "WOLTLAB_FAMILY_MANIFEST gesetzt, Datei fehlt: $m" >&2
        return 1
    fi

    if [ -f "$main_dir/swpm-family.json" ]; then
        echo "$main_dir/swpm-family.json"
        return 0
    fi

    echo "Kein Familien-Manifest gefunden (swpm-family.json oder WOLTLAB_FAMILY_MANIFEST)." >&2
    return 1
}

swpm_family_run_deps() {
    local mode="$1"
    local manifest="$2"
    shift 2 || true
    local tools_dir
    tools_dir="$(swpm_family_tools_dir)"
    python3 "$tools_dir/check-family-deps.py" --manifest "$manifest" --mode "$mode" "$@"
}

# CLI when executed directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    MANIFEST_ARG=""
    MODE="order"
    EXTRA=()
    while [ $# -gt 0 ]; do
        case "$1" in
            --manifest)
                MANIFEST_ARG="${2:-}"
                shift 2
                ;;
            --json)
                EXTRA+=(--json)
                shift
                ;;
            check|order|list)
                MODE="$1"
                shift
                ;;
            *)
                echo "Usage: $0 [--manifest PATH] [--json] check|order|list" >&2
                exit 1
                ;;
        esac
    done
    M="$(swpm_family_find_manifest "$MANIFEST_ARG")" || exit 1
    swpm_family_run_deps "$MODE" "$M" "${EXTRA[@]}"
fi
