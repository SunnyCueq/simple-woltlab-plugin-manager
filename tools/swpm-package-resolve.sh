#!/usr/bin/env bash
# Resolve package.xml metadata for generic SWPM tooling (no plugin-specific hardcoding).
# Source from other scripts: source "$SCRIPT_DIR/swpm-package-resolve.sh"

swpm_find_package_xml() {
    local base="${1:-.}"
    local candidate
    for candidate in \
        "$base/temp_edit/package.xml" \
        "$base/package.xml" \
        "$base/_extracted/package.xml"
    do
        if [ -f "$candidate" ]; then
            echo "$candidate"
            return 0
        fi
    done
    return 1
}

swpm_read_package_id() {
    local xml="$1"
    local id=""
    id=$(grep -oE '<package[^>]+name="[^"]+"' "$xml" 2>/dev/null | head -1 | sed 's/.*name="\([^"]*\)".*/\1/')
    if [ -n "$id" ]; then
        echo "$id"
        return 0
    fi
    id=$(grep -oP '<packagename>\K[^<]+' "$xml" 2>/dev/null | head -1)
    [ -n "$id" ] && echo "$id"
}

swpm_read_app_abbrev() {
    local xml="$1"
    local pkg="${2:-}"
    local abbrev=""
    abbrev=$(
        grep -oE '<instruction[^>]*type="application"[^>]*application="[^"]+"' "$xml" 2>/dev/null \
            | head -1 \
            | sed 's/.*application="\([^"]*\)".*/\1/'
    )
    if [ -z "$abbrev" ]; then
        abbrev=$(
            grep -oE '<instruction[^>]*application="[^"]+"[^>]*type="application"' "$xml" 2>/dev/null \
                | head -1 \
                | sed 's/.*application="\([^"]*\)".*/\1/'
        )
    fi
    if [ -z "$abbrev" ] && [ -n "$pkg" ]; then
        abbrev="${pkg##*.}"
    fi
    echo "$abbrev"
}

swpm_app_pascal_case() {
    local abbrev="$1"
    [ -z "$abbrev" ] && return 1
    local first="${abbrev:0:1}"
    local rest="${abbrev:1}"
    first=$(printf '%s' "$first" | tr '[:lower:]' '[:upper:]')
    echo "${first}${rest}"
}

# Default container paths for a WoltLab app package (html root = /var/www/html).
swpm_default_container_paths() {
    local abbrev="$1"
    local pkg="$2"
    [ -z "$abbrev" ] && return 1
    local pascal
    pascal=$(swpm_app_pascal_case "$abbrev")
    echo "/var/www/html/${abbrev}"
    [ -n "$pascal" ] && echo "/var/www/html/js/${pascal}"
    [ -n "$pkg" ] && echo "/var/www/html/lib/bootstrap/${pkg}.php"
}

swpm_load_plugin_context() {
    local plugin_dir="$1"
    local tools_dir="${2:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"
    local main_dir="${3:-$(dirname "$tools_dir")}"

    if [[ "$plugin_dir" != /* ]]; then
        plugin_dir="$main_dir/$plugin_dir"
    fi

    SWPM_PLUGIN_DIR="$plugin_dir"
    SWPM_PACKAGE_XML=""
    SWPM_PACKAGE_ID="${WOLTLAB_PACKAGE_ID:-}"
    SWPM_APP_ABBREV="${WOLTLAB_APP_ABBREV:-}"

    if [ -z "$SWPM_PACKAGE_ID" ] || [ -z "$SWPM_APP_ABBREV" ]; then
        SWPM_PACKAGE_XML=$(swpm_find_package_xml "$plugin_dir") || true
        if [ -n "$SWPM_PACKAGE_XML" ]; then
            [ -z "$SWPM_PACKAGE_ID" ] && SWPM_PACKAGE_ID=$(swpm_read_package_id "$SWPM_PACKAGE_XML")
            [ -z "$SWPM_APP_ABBREV" ] && SWPM_APP_ABBREV=$(swpm_read_app_abbrev "$SWPM_PACKAGE_XML" "$SWPM_PACKAGE_ID")
        fi
    fi

    if [ -z "$SWPM_PACKAGE_ID" ] || [ -z "$SWPM_APP_ABBREV" ]; then
        echo "Konnte package.xml nicht auswerten (Plugin: $plugin_dir)." >&2
        echo "Optional in tools/.env setzen: WOLTLAB_PACKAGE_ID, WOLTLAB_APP_ABBREV" >&2
        return 1
    fi

    export SWPM_PLUGIN_DIR SWPM_PACKAGE_XML SWPM_PACKAGE_ID SWPM_APP_ABBREV
    return 0
}

swpm_collect_container_paths() {
    local abbrev="$1"
    local pkg="$2"
    shift 2
    local -a paths=()
    local p
    while IFS= read -r p; do
        [ -n "$p" ] && paths+=("$p")
    done < <(swpm_default_container_paths "$abbrev" "$pkg")

    if [ -n "${WOLTLAB_EXTRA_CONTAINER_PATHS:-}" ]; then
        local IFS=':'
        for p in $WOLTLAB_EXTRA_CONTAINER_PATHS; do
            [ -n "$p" ] && paths+=("$p")
        done
    fi

    for p in "$@"; do
        [ -n "$p" ] && paths+=("$p")
    done

    printf '%s\n' "${paths[@]}"
}
