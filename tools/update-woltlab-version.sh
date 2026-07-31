#!/usr/bin/env bash

#################################################################
# WoltLab-Referenzen prüfen / synchronisieren
# Pfad: tools/update-woltlab-version.sh [OPTIONEN] [VERSION]
#
# Ohne VERSION (Menü 9 / interaktiv):
#   Online-Core vs. lokal vergleichen → bei neuer Version fragen → Sync
#
# Mit VERSION:
#   6.2 / 6.2.6 → Core (+ Git-Spiegel) auf diese Version bringen
#
# Optionen:
#   --check       Nur Status (Exit 0 aktuell, 2 Update verfügbar, 1 Fehler)
#   --yes / -y    Ohne Rückfrage aktualisieren (wenn Update da / Core fehlt)
#   --refs-only   Nur Git-Spiegel, kein Core-Download
#   --fresh       Download-Seite neu abfragen (Cache ignorieren)
#################################################################

set -euo pipefail

readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$TOOLS_DIR")"

# shellcheck source=common.sh
source "$TOOLS_DIR/common.sh"
# shellcheck source=woltlab-refs-lib.sh
source "$TOOLS_DIR/woltlab-refs-lib.sh"

SKIP_CORE=0
ASSUME_YES=0
CHECK_ONLY=0
FRESH=0
VERSION=""

usage() {
    cat <<'EOF'
Verwendung: update-woltlab-version.sh [OPTIONEN] [VERSION]

Ohne VERSION: prüft, ob online eine neuere Core-Version liegt, fragt nach,
             und synchronisiert bei Ja Core + Git-Referenzen.

  --check       Nur Status (Exit 0=aktuell, 2=Update, 1=Fehler)
  --yes, -y     Ohne Rückfrage aktualisieren
  --refs-only   Nur Git-Spiegel (kein Core-ZIP)
  --fresh       Online-Version neu von der Download-Seite holen
  VERSION       6.2 (Linie) oder 6.2.6 (Patch); Git immer major.minor

Linie: WOLTLAB_REF_LINE=6.2 in tools/.env (sonst aus lokalem Core / Git-Branch)
EOF
}

while [ $# -gt 0 ]; do
    case "$1" in
        --refs-only | --no-core) SKIP_CORE=1; shift ;;
        --yes | -y) ASSUME_YES=1; shift ;;
        --check) CHECK_ONLY=1; shift ;;
        --fresh) FRESH=1; shift ;;
        -h | --help) usage; exit 0 ;;
        -*)
            print_error "Unbekannte Option: $1"
            usage >&2
            exit 2
            ;;
        *)
            VERSION="$1"
            shift
            ;;
    esac
done

MIRRORS=(
    "woltlab-github|woltlab-github|https://github.com/WoltLab/WCF"
    "woltlab-docs|woltlab-docs|https://github.com/WoltLab/docs.woltlab.com"
    "woltlab-d-ts|woltlab-d-ts|https://github.com/WoltLab/d.ts"
    "woltlab-exporter|woltlab-exporter|https://github.com/WoltLab/com.woltlab.wcf.exporter"
    "woltlab-conversation|woltlab-conversation|https://github.com/WoltLab/com.woltlab.wcf.conversation"
    "woltlab-legal-notice|woltlab-legal-notice|https://github.com/WoltLab/com.woltlab.wcf.legalNotice"
)

ensure_git_mirror() {
    local name="$1" dir="$2" url="$3" branch="$4"
    if [ ! -d "$dir/.git" ]; then
        print_info "$name: klone $url (Branch $branch) …"
        if [ -d "$dir" ] && [ ! -d "$dir/.git" ]; then
            rm -rf "$dir"
        fi
        if ! git clone --branch "$branch" --single-branch "$url" "$dir"; then
            print_warning "$name: Clone mit Branch fehlgeschlagen — Default-Branch …"
            if ! git clone "$url" "$dir"; then
                print_warning "$name: Clone fehlgeschlagen."
                return 1
            fi
            git -C "$dir" fetch origin "refs/heads/${branch}:refs/remotes/origin/${branch}" --tags --prune || true
        fi
        touch "$dir/.gitkeep" 2>/dev/null || true
    fi
    return 0
}

sync_git_mirror() {
    local name="$1" dir="$2" url="$3" branch="$4"
    ensure_git_mirror "$name" "$dir" "$url" "$branch" || return 1
    [ -d "$dir/.git" ] || return 1

    print_info "$name: fetch origin/$branch …"
    if ! git -C "$dir" fetch origin "refs/heads/${branch}:refs/remotes/origin/${branch}" --tags --prune 2>/dev/null; then
        print_warning "$name: fetch fehlgeschlagen."
        return 1
    fi
    if ! git -C "$dir" rev-parse "origin/${branch}" >/dev/null 2>&1; then
        print_warning "$name: Branch origin/$branch nicht gefunden."
        return 1
    fi
    git -C "$dir" config core.fileMode false
    git -C "$dir" checkout -B "$branch" "origin/${branch}"
    git -C "$dir" reset --hard "origin/${branch}"
    print_success "$name: $(git -C "$dir" log -1 --oneline)"
}

run_full_sync() {
    local core_version="$1"
    local branch="$2"
    local do_core="${3:-1}"
    local step=1
    local total=${#MIRRORS[@]}
    if [ "$do_core" -eq 1 ]; then
        total=$((total + 1))
    fi

    echo ""
    print_section "WoltLab $core_version synchronisieren" "Hauptmenü" "Update Version"
    echo -e "  ${DIM}Git-Branch:${RESET} $branch"
    if [ "$do_core" -eq 1 ]; then
        echo -e "  ${DIM}Core-ZIP:${RESET}   $core_version"
    else
        echo -e "  ${DIM}Core-ZIP:${RESET}   übersprungen (--refs-only)"
    fi
    echo ""

    if [ "$do_core" -eq 1 ]; then
        mkdir -p "$MAIN_DIR/woltlab-core"
        [ -f "$MAIN_DIR/woltlab-core/.gitkeep" ] || touch "$MAIN_DIR/woltlab-core/.gitkeep"
        print_info "${step}/${total} woltlab-core …"
        "$TOOLS_DIR/download-woltlab-core.sh" "$core_version" || print_warning "Core-Download fehlgeschlagen."
        if [ -f "$MAIN_DIR/woltlab-core/.swpm-core-version" ]; then
            print_success "woltlab-core: $(cat "$MAIN_DIR/woltlab-core/.swpm-core-version")"
        fi
        echo ""
        step=$((step + 1))
    fi

    local entry name relpath url
    for entry in "${MIRRORS[@]}"; do
        IFS='|' read -r name relpath url <<< "$entry"
        print_info "${step}/${total} $name …"
        sync_git_mirror "$name" "$MAIN_DIR/$relpath" "$url" "$branch" || true
        echo ""
        step=$((step + 1))
    done

    print_success "Synchronisation auf WoltLab $core_version (Branch $branch) abgeschlossen."
    echo ""
}

confirm() {
    local prompt="$1"
    local ok="n"
    if [ "$ASSUME_YES" -eq 1 ]; then
        return 0
    fi
    if [ ! -t 0 ]; then
        return 1
    fi
    read -r -p "$prompt" ok
    ok=${ok:-n}
    [[ "$ok" =~ ^[jJyY] ]]
}

# ── Explizite VERSION ────────────────────────────────────────────────────────
if [ -n "$VERSION" ]; then
    CORE_VERSION="$VERSION"
    BRANCH="$VERSION"
    if [[ "$VERSION" =~ ^([0-9]+\.[0-9]+)\.[0-9]+$ ]]; then
        BRANCH="${BASH_REMATCH[1]}"
    elif [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+$ ]]; then
        print_error "Ungültige Version: $VERSION"
        exit 1
    fi
    do_core=1
    [ "$SKIP_CORE" -eq 1 ] && do_core=0
    run_full_sync "$CORE_VERSION" "$BRANCH" "$do_core"
    exit 0
fi

# ── Status: lokal vs. Download-Seite ─────────────────────────────────────────
if [ "$FRESH" -eq 1 ]; then
    rm -f "$WOLTLAB_REFS_CACHE" 2>/dev/null || true
fi

SWPM_REF_LINE="$(woltlab_preferred_line)"
SWPM_LOCAL_CORE="$(woltlab_local_core_version 2>/dev/null || true)"
SWPM_ONLINE_CORE=""
SWPM_ONLINE_URL=""
SWPM_UPDATE_AVAILABLE=0
status_rc=1

set +e
if [ "$FRESH" -eq 1 ]; then
    online="$(woltlab_detect_online_core "$SWPM_REF_LINE" --fresh)"
else
    online="$(woltlab_detect_online_core "$SWPM_REF_LINE")"
fi
det_rc=$?
set -e

if [ "$det_rc" -ne 0 ]; then
    echo ""
    print_error "Online-Version konnte nicht ermittelt werden ($WOLTLAB_DOWNLOAD_PAGE)."
    exit 1
fi

SWPM_ONLINE_CORE="$(printf '%s' "$online" | cut -f1)"
SWPM_ONLINE_URL="$(printf '%s' "$online" | cut -f2)"

if [ -z "$SWPM_LOCAL_CORE" ]; then
    SWPM_UPDATE_AVAILABLE=1
    status_rc=2
elif woltlab_version_gt "$SWPM_ONLINE_CORE" "$SWPM_LOCAL_CORE"; then
    SWPM_UPDATE_AVAILABLE=1
    status_rc=2
else
    status_rc=0
fi

echo ""
echo -e "${BOLD}WoltLab-Referenzen${RESET}"
echo -e "  ${DIM}Linie:${RESET}        ${SWPM_REF_LINE}"
echo -e "  ${DIM}Lokal (Core):${RESET} ${SWPM_LOCAL_CORE:-— (kein Core)}"
echo -e "  ${DIM}Online:${RESET}       ${SWPM_ONLINE_CORE}"
echo -e "  ${DIM}URL:${RESET}          ${SWPM_ONLINE_URL}"
echo ""

if [ "$status_rc" -eq 0 ]; then
    print_success "Lokal ist aktuell (${SWPM_LOCAL_CORE})."
elif [ -z "$SWPM_LOCAL_CORE" ]; then
    print_warning "Kein lokaler Core — Online: ${SWPM_ONLINE_CORE}."
else
    print_warning "Update verfügbar: ${SWPM_LOCAL_CORE} → ${SWPM_ONLINE_CORE}."
fi

if [ "$CHECK_ONLY" -eq 1 ]; then
    exit "$status_rc"
fi

TARGET_CORE="$SWPM_ONLINE_CORE"
TARGET_BRANCH="$(woltlab_line_from_version "$TARGET_CORE" || echo "$SWPM_REF_LINE")"
do_core=1
[ "$SKIP_CORE" -eq 1 ] && do_core=0

if [ "$SWPM_UPDATE_AVAILABLE" -eq 1 ]; then
    if [ -z "$SWPM_LOCAL_CORE" ]; then
        prompt="Core ${TARGET_CORE} laden und Git-Spiegel syncen? (j/N): "
    else
        prompt="Auf ${TARGET_CORE} aktualisieren (Core + Docs/WCF/d.ts/Beispiele)? (j/N): "
    fi
    if confirm "$prompt"; then
        run_full_sync "$TARGET_CORE" "$TARGET_BRANCH" "$do_core"
        exit 0
    fi
    echo "Abgebrochen."
    exit 0
fi

# Aktuell — optional nur Git-Spiegel (kein Core-Neudownload)
if confirm "Bereits aktuell. Trotzdem Git-Spiegel (Branch ${TARGET_BRANCH}) syncen? (j/N): "; then
    run_full_sync "$TARGET_CORE" "$TARGET_BRANCH" 0
    exit 0
fi

print_info "Nichts zu tun."
exit 0
