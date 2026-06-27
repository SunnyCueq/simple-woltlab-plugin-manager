#!/usr/bin/env bash
# ═══════════════════════════════════════════
#  ui.sh — Shell UI Bibliothek
#  Einbinden: source "$(dirname "$0")/ui.sh"  bzw.  source "${TOOLS_DIR}/ui.sh"
#  Angelehnt an Video Whisper scripts/ui.sh
# ═══════════════════════════════════════════

# ── TTY-Check: Farben nur bei Terminal, sonst leer (für Pipes/Logs) ──
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    MAGENTA='\033[0;35m'
    BOLD='\033[1m'
    DIM='\033[2m'
    RESET='\033[0m'
    NC='\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; BLUE=''; CYAN=''; MAGENTA=''
    BOLD=''; DIM=''; RESET=''; NC=''
fi

# ── Symbole (Kompatibel mit common.sh: CHECK, CROSS, ARROW, WARNING, INFO) ──
OK="✓"
FAIL="✗"
WARN="⚠"
INFO="ℹ"
ARROW="›"
SPIN="⟳"
CHECK="$OK"
CROSS="$FAIL"
WARNING="$WARN"

# ════════════════════════════════════════════
#  AUSGABE-FUNKTIONEN
# ════════════════════════════════════════════

# Haupt-Header mit Box (Breite 58 Zeichen Inhalt)
ui_header() {
    local title="${1:-Simple WoltLab Plugin Manager}"
    local width=58
    local line
    line=$(printf '═%.0s' $(seq 1 $width 2>/dev/null) || printf '═%.0s' {1..58})
    echo ""
    echo -e "${CYAN}╔${line}╗${RESET}"
    printf "${CYAN}║${RESET}%*s%s%*s${CYAN}║${RESET}\n" $(( (width - ${#title}) / 2 )) "" "$title" $(( (width - ${#title} + 1) / 2 )) ""
    echo -e "${CYAN}╚${line}╝${RESET}"
    echo ""
}

# Abschnitts-Header (schlicht: ─── TITEL ───)
ui_section() {
    echo ""
    echo -e "${BOLD}${CYAN}─── $1 ───${RESET}"
    echo ""
}

# Schritte: [n/total] Titel
ui_step() {
    local n="$1" total="$2" title="$3"
    echo ""
    echo -e "  ${BLUE}${BOLD}[${n}/${total}]${RESET} ${BOLD}${title}${RESET}"
}

# ── Status-Zeilen ────────────────────────────
ui_ok()   { echo -e "  ${GREEN}${OK}${RESET} $1"; }
ui_fail() { echo -e "${RED}${FAIL}${RESET} $1" >&2; }
ui_warn() { echo -e "  ${YELLOW}${WARN}${RESET} $1"; }
ui_info() { echo -e "  ${CYAN}${INFO}${RESET} $1"; }

# Key=Value Zeile (z. B. System-Infos)
ui_kv() {
    local key="$1" val="$2" hint="${3:-}"
    printf "  ${GREEN}${OK}${RESET}  %-14s ${DIM}→${RESET}  %s" "$key" "$val"
    [[ -n "$hint" ]] && printf "  ${DIM}(%s)${RESET}" "$hint"
    echo ""
}

# Protokoll-Ausgabe (gedimmt)
ui_log() { echo -e "  ${DIM}$1${RESET}"; }

# Trennlinie
ui_divider() {
    local w="${1:-50}"
    echo -e "${DIM}$(printf '─%.0s' $(seq 1 $w 2>/dev/null) || printf '─%.0s' {1..50})${RESET}"
}

# Info-Box mit blauem Rand
ui_infobox() {
    echo -e "  ${CYAN}│${RESET} $1"
}

# ════════════════════════════════════════════
#  ENTSCHEIDUNGS-PROMPT (eingerahmt)
#  Aufruf: ui_prompt_box "Titel" "KEY|Beschreibung|Hinweis" ...
#  Danach: read -r choice
# ════════════════════════════════════════════
ui_prompt_box() {
    local title="$1"
    shift
    local options=("$@")
    local i
    echo ""
    echo -e "  ${YELLOW}${BOLD}┌─ ${title} ${DIM}$(printf '─%.0s' {1..35})${RESET}"
    echo -e "  ${YELLOW}${BOLD}│${RESET}"
    for i in "${!options[@]}"; do
        local key desc hint
        IFS='|' read -r key desc hint <<< "${options[$i]}"
        printf "  ${YELLOW}${BOLD}│${RESET}  ${GREEN}%-10s${RESET}  %-28s ${DIM}%s${RESET}\n" "$key" "$desc" "$hint"
    done
    echo -e "  ${YELLOW}${BOLD}│${RESET}"
    printf "  ${YELLOW}${BOLD}└─${RESET} Auswahl: "
}

# Generisches Menü – gibt gewählte Eingabe zurück
# Aufruf: choice=$(ui_menu "Titel" "Opt1" "Opt2" ...)
ui_menu() {
    local title="$1"
    shift
    local options=("$@")
    local i
    echo ""
    echo -e "${BOLD}${CYAN}─── ${title} ───${RESET}"
    echo ""
    for i in "${!options[@]}"; do
        echo -e "  ${GREEN}$((i + 1))${RESET}) ${options[$i]}"
    done
    echo ""
    read -rp "$(echo -e "${YELLOW}→ Auswahl (1–${#options[@]}):${RESET} ")" choice
    echo "$choice"
}
