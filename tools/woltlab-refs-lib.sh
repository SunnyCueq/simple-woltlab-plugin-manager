#!/usr/bin/env bash
# Gemeinsame Hilfen für WoltLab-Core-/Referenz-Sync (von download- und update-Skripten).
# Nicht direkt ausführen — sourcen nach common.sh.
#
# Erwartet: TOOLS_DIR, MAIN_DIR gesetzt (oder leitet sie ab).

: "${TOOLS_DIR:=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"
: "${MAIN_DIR:=$(dirname "$TOOLS_DIR")}"

WOLTLAB_DOWNLOAD_PAGE="${WOLTLAB_DOWNLOAD_PAGE:-https://www.woltlab.com/de/woltlab-suite-download/}"
WOLTLAB_ASSETS_BASE="${WOLTLAB_ASSETS_BASE:-https://assets.woltlab.com/release}"
WOLTLAB_REFS_CACHE="${WOLTLAB_REFS_CACHE:-$TOOLS_DIR/.woltlab-refs-latest.cache}"
WOLTLAB_REFS_CACHE_MAX_AGE="${WOLTLAB_REFS_CACHE_MAX_AGE:-86400}" # 24h

# SemVer-Vergleich: 0 gleich, 1 a>b, 2 a<b
woltlab_version_cmp() {
    local a="$1" b="$2"
    if [ "$a" = "$b" ]; then
        return 0
    fi
    local top
    top="$(printf '%s\n%s\n' "$a" "$b" | sort -V | tail -1)"
    if [ "$top" = "$a" ]; then
        return 1
    fi
    return 2
}

woltlab_version_gt() {
    woltlab_version_cmp "$1" "$2"
    [ $? -eq 1 ]
}

woltlab_line_from_version() {
    local ver="$1"
    if [[ "$ver" =~ ^([0-9]+\.[0-9]+)(\.|$) ]]; then
        echo "${BASH_REMATCH[1]}"
        return 0
    fi
    return 1
}

# Lokal installierte Core-Version (Referenz-ZIP in woltlab-core/)
woltlab_local_core_version() {
    local f="$MAIN_DIR/woltlab-core/.swpm-core-version"
    local ver=""
    if [ -f "$f" ]; then
        ver="$(tr -d '[:space:]' < "$f")"
    fi
    if [[ "$ver" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
        echo "$ver"
        return 0
    fi
    # Fallback: WCF-Konstante im geklonten Git-Spiegel
    local constants="$MAIN_DIR/woltlab-github/constants.php"
    if [ -f "$constants" ]; then
        ver="$(grep -oE "WCF_VERSION['\"],\s*['\"][0-9]+\.[0-9]+\.[0-9]+" "$constants" 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1 || true)"
        if [ -n "$ver" ]; then
            echo "$ver"
            return 0
        fi
    fi
    return 1
}

# Bevorzugte Linie (6.2): .env → lokaler Core → Git-Branch → 6.2
woltlab_preferred_line() {
    local line ver branch
    if [ -f "$TOOLS_DIR/.env" ]; then
        # shellcheck disable=SC1091
        line="$(grep -E '^[[:space:]]*WOLTLAB_REF_LINE=' "$TOOLS_DIR/.env" 2>/dev/null | tail -1 | cut -d= -f2- | tr -d '\"'\''[:space:]' || true)"
    fi
    line="${WOLTLAB_REF_LINE:-$line}"
    if [[ "$line" =~ ^[0-9]+\.[0-9]+$ ]]; then
        echo "$line"
        return 0
    fi
    if ver="$(woltlab_local_core_version 2>/dev/null)"; then
        if line="$(woltlab_line_from_version "$ver")"; then
            echo "$line"
            return 0
        fi
    fi
    if [ -d "$MAIN_DIR/woltlab-github/.git" ]; then
        branch="$(git -C "$MAIN_DIR/woltlab-github" branch --show-current 2>/dev/null || true)"
        if [[ "$branch" =~ ^[0-9]+\.[0-9]+$ ]]; then
            echo "$branch"
            return 0
        fi
    fi
    echo "6.2"
}

woltlab_scrape_release_urls() {
    if ! command -v curl &>/dev/null; then
        return 1
    fi
    curl -fsSL "$WOLTLAB_DOWNLOAD_PAGE" 2>/dev/null \
        | grep -oE "${WOLTLAB_ASSETS_BASE//\./\\.}/woltlab-suite-[0-9.]+\.zip" \
        | sort -u
}

woltlab_url_to_version() {
    local url="$1"
    local ver="${url##*/woltlab-suite-}"
    echo "${ver%.zip}"
}

# Neueste Version (optional nur Linie X.Y). Gibt VERSION\tURL aus.
woltlab_pick_latest_release() {
    local line="${1:-}"
    local urls url ver best="" best_url=""
    urls="$(woltlab_scrape_release_urls || true)"
    [ -n "$urls" ] || return 1
    while IFS= read -r url; do
        [ -n "$url" ] || continue
        ver="$(woltlab_url_to_version "$url")"
        if [ -n "$line" ]; then
            [[ "$ver" == "$line" || "$ver" == "$line".* ]] || continue
        fi
        if [ -z "$best" ] || woltlab_version_gt "$ver" "$best"; then
            best="$ver"
            best_url="$url"
        fi
    done <<< "$urls"
    [ -n "$best_url" ] || return 1
    printf '%s\t%s\n' "$best" "$best_url"
}

woltlab_asset_url_for_version() {
    local ver="$1"
    echo "${WOLTLAB_ASSETS_BASE}/woltlab-suite-${ver}.zip"
}

# Cache: VERSION|URL|UNIXTS|LINE
woltlab_cache_write_latest() {
    local ver="$1" url="$2" line="${3:-}"
    mkdir -p "$(dirname "$WOLTLAB_REFS_CACHE")"
    printf '%s|%s|%s|%s\n' "$ver" "$url" "$(date +%s)" "$line" > "$WOLTLAB_REFS_CACHE"
}

woltlab_cache_read_latest() {
    local max_age="${1:-$WOLTLAB_REFS_CACHE_MAX_AGE}"
    local ver url ts line now
    [ -f "$WOLTLAB_REFS_CACHE" ] || return 1
    IFS='|' read -r ver url ts line < "$WOLTLAB_REFS_CACHE" || return 1
    now="$(date +%s)"
    if [ -n "$ts" ] && [ $((now - ts)) -gt "$max_age" ]; then
        return 1
    fi
    [[ "$ver" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]] || return 1
    printf '%s\t%s\t%s\n' "$ver" "$url" "${line:-}"
}

# Online-Version ermitteln (Cache oder Scraping). Ausgabe: VERSION\tURL
# Args: [line] [--fresh]
woltlab_detect_online_core() {
    local line="" fresh=0
    while [ $# -gt 0 ]; do
        case "$1" in
            --fresh) fresh=1; shift ;;
            *) line="$1"; shift ;;
        esac
    done
    local cached ver url cline
    if [ "$fresh" -eq 0 ] && cached="$(woltlab_cache_read_latest)"; then
        ver="$(printf '%s' "$cached" | cut -f1)"
        url="$(printf '%s' "$cached" | cut -f2)"
        cline="$(printf '%s' "$cached" | cut -f3)"
        if [ -z "$line" ] || [ "$cline" = "$line" ] || [[ "$ver" == "$line".* ]]; then
            printf '%s\t%s\n' "$ver" "$url"
            return 0
        fi
    fi
    local pick
    if ! pick="$(woltlab_pick_latest_release "$line")"; then
        return 1
    fi
    ver="$(printf '%s' "$pick" | cut -f1)"
    url="$(printf '%s' "$pick" | cut -f2)"
    woltlab_cache_write_latest "$ver" "$url" "$line"
    printf '%s\t%s\n' "$ver" "$url"
}

# Status: lokal vs online. Exit 0 = aktuell, 2 = Update verfügbar / fehlend, 1 = Fehler
# Setzt Globals: SWPM_LOCAL_CORE SWPM_ONLINE_CORE SWPM_ONLINE_URL SWPM_REF_LINE SWPM_UPDATE_AVAILABLE (0/1)
woltlab_refs_status() {
    local line fresh_args=()
    line="$(woltlab_preferred_line)"
    SWPM_REF_LINE="$line"
    SWPM_LOCAL_CORE=""
    SWPM_ONLINE_CORE=""
    SWPM_ONLINE_URL=""
    SWPM_UPDATE_AVAILABLE=0

    SWPM_LOCAL_CORE="$(woltlab_local_core_version 2>/dev/null || true)"

    local online
    if ! online="$(woltlab_detect_online_core "$line" "${fresh_args[@]}")"; then
        return 1
    fi
    SWPM_ONLINE_CORE="$(printf '%s' "$online" | cut -f1)"
    SWPM_ONLINE_URL="$(printf '%s' "$online" | cut -f2)"

    if [ -z "$SWPM_LOCAL_CORE" ]; then
        SWPM_UPDATE_AVAILABLE=1
        return 2
    fi
    if woltlab_version_gt "$SWPM_ONLINE_CORE" "$SWPM_LOCAL_CORE"; then
        SWPM_UPDATE_AVAILABLE=1
        return 2
    fi
    return 0
}
