#!/bin/bash

#################################################################
# WoltLab Development Tools - Gemeinsame Funktionen
# Zentrale Funktionen für alle Tools (Farben, Formatierung, etc.)
#################################################################

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Unicode-Symbole
CHECK="✓"
CROSS="✗"
ARROW="→"
WARNING="⚠"
INFO="ℹ"

# ============================================================
# Debug-Log-System (zentral für alle Tools)
# ============================================================

# Debug-Konfiguration
# Bestimme Tools-Verzeichnis für Log-Pfad
_TOOLS_DIR_FOR_LOG="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEBUG_LOG_DIR="${DEBUG_LOG_DIR:-$_TOOLS_DIR_FOR_LOG/docs/logs}"
DEBUG_LOG_FILE="${DEBUG_LOG_FILE:-$DEBUG_LOG_DIR/woltlab-dev-debug.log}"
DEBUG_ENABLED="${DEBUG_ENABLED:-true}"  # Standardmäßig aktiviert
DEBUG_LEVEL="${DEBUG_LEVEL:-INFO}"      # ERROR, WARNING, INFO, DEBUG, TRACE

# Stelle sicher, dass Log-Verzeichnis existiert
mkdir -p "$DEBUG_LOG_DIR" 2>/dev/null || true

# Debug-Level-Nummern (höher = mehr Details)
DEBUG_LEVEL_ERROR=1
DEBUG_LEVEL_WARNING=2
DEBUG_LEVEL_INFO=3
DEBUG_LEVEL_DEBUG=4
DEBUG_LEVEL_TRACE=5

# Funktion: Aktuelles Debug-Level als Nummer
_get_debug_level_num() {
    case "$DEBUG_LEVEL" in
        ERROR)   echo $DEBUG_LEVEL_ERROR ;;
        WARNING) echo $DEBUG_LEVEL_WARNING ;;
        INFO)    echo $DEBUG_LEVEL_INFO ;;
        DEBUG)   echo $DEBUG_LEVEL_DEBUG ;;
        TRACE)   echo $DEBUG_LEVEL_TRACE ;;
        *)       echo $DEBUG_LEVEL_INFO ;;
    esac
}

# Funktion: Script-Name extrahieren
_get_script_name() {
    local script_path="${BASH_SOURCE[2]:-${BASH_SOURCE[1]:-unknown}}"
    basename "$script_path" 2>/dev/null || echo "unknown"
}

# Funktion: Funktionsname extrahieren
_get_function_name() {
    local func_name="${FUNCNAME[2]:-${FUNCNAME[1]:-main}}"
    echo "$func_name"
}

# Funktion: Debug-Log schreiben (Hauptfunktion)
debug_log() {
    # Prüfe ob Debug aktiviert ist
    if [ "$DEBUG_ENABLED" != "true" ] && [ "$DEBUG_ENABLED" != "1" ]; then
        return 0
    fi
    
    local level="${1:-INFO}"
    local message="${2:-}"
    local data="${3:-}"
    
    # Prüfe ob Level aktiviert ist
    local current_level=$(_get_debug_level_num)
    local message_level_num=0
    case "$level" in
        ERROR)   message_level_num=$DEBUG_LEVEL_ERROR ;;
        WARNING) message_level_num=$DEBUG_LEVEL_WARNING ;;
        INFO)    message_level_num=$DEBUG_LEVEL_INFO ;;
        DEBUG)   message_level_num=$DEBUG_LEVEL_DEBUG ;;
        TRACE)   message_level_num=$DEBUG_LEVEL_TRACE ;;
        *)       message_level_num=$DEBUG_LEVEL_INFO ;;
    esac
    
    # Nur loggen wenn Level hoch genug ist
    if [ $message_level_num -gt $current_level ]; then
        return 0
    fi
    
    # Erstelle Log-Eintrag
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S.%3N' 2>/dev/null || date '+%Y-%m-%d %H:%M:%S')
    local script_name=$(_get_script_name)
    local function_name=$(_get_function_name)
    
    # Format: [TIMESTAMP] [LEVEL] [SCRIPT:FUNCTION] MESSAGE [DATA]
    local log_entry="[$timestamp] [$level] [$script_name:$function_name] $message"
    if [ -n "$data" ]; then
        log_entry="$log_entry | $data"
    fi
    
    # Schreibe in Log-Datei (mit Fallback)
    echo "$log_entry" >> "$DEBUG_LOG_FILE" 2>/dev/null || {
        # Fallback: Versuche in /tmp zu schreiben
        echo "$log_entry" >> "/tmp/woltlab-dev-debug.log" 2>/dev/null || true
    }
}

# Convenience-Funktionen für verschiedene Log-Level
debug_error() {
    debug_log "ERROR" "$1" "$2"
}

debug_warning() {
    debug_log "WARNING" "$1" "$2"
}

debug_info() {
    debug_log "INFO" "$1" "$2"
}

debug_debug() {
    debug_log "DEBUG" "$1" "$2"
}

debug_trace() {
    debug_log "TRACE" "$1" "$2"
}

# Funktion: Log-Datei anzeigen
show_debug_log() {
    local lines="${1:-50}"
    if [ -f "$DEBUG_LOG_FILE" ]; then
        echo -e "${CYAN}Debug-Log (letzte $lines Zeilen):${NC}"
        echo -e "${BLUE}==========================================${NC}"
        tail -n "$lines" "$DEBUG_LOG_FILE"
        echo -e "${BLUE}==========================================${NC}"
        echo ""
        echo -e "${YELLOW}Vollständige Log-Datei:${NC} $DEBUG_LOG_FILE"
    else
        echo -e "${YELLOW}Keine Debug-Log-Datei gefunden:${NC} $DEBUG_LOG_FILE"
    fi
}

# Funktion: Log-Datei löschen
clear_debug_log() {
    if [ -f "$DEBUG_LOG_FILE" ]; then
        > "$DEBUG_LOG_FILE"
        debug_info "Log-Datei geleert" "file=$DEBUG_LOG_FILE"
        echo -e "${GREEN}✓ Debug-Log-Datei geleert${NC}"
    else
        echo -e "${YELLOW}Keine Debug-Log-Datei gefunden${NC}"
    fi
}

# ============================================================
# Versions-Ermittlung für alle Tools
# ============================================================

# Funktion: Dockge-Version ermitteln
get_dockge_version() {
    debug_trace "get_dockge_version" "starting"
    local version=""
    
    if command -v docker &> /dev/null && docker info &>/dev/null 2>&1; then
        # Prüfe ob Dockge-Container läuft
        if docker ps --format "{{.Names}}" | grep -q "^dockge$"; then
            # Versuche Version aus Container-Image zu extrahieren
            version=$(docker inspect dockge --format='{{.Config.Image}}' 2>/dev/null | grep -oP ':\K[^:]+$' || echo "")
            if [ -z "$version" ]; then
                # Fallback: Versuche aus Container-Labels
                version=$(docker inspect dockge --format='{{index .Config.Labels "org.opencontainers.image.version"}}' 2>/dev/null || echo "")
            fi
            if [ -z "$version" ] || [ "$version" = "<no value>" ]; then
                # Fallback: Prüfe ob latest Image verwendet wird
                if docker inspect dockge --format='{{.Config.Image}}' 2>/dev/null | grep -q ":latest"; then
                    version="latest"
                else
                    version="running"
                fi
            fi
            debug_debug "get_dockge_version" "found version=$version"
        else
            debug_debug "get_dockge_version" "Dockge container not running"
        fi
    else
        debug_warning "get_dockge_version" "Docker not available"
    fi
    
    echo "${version:-not installed}"
}

# Funktion: DDEV-Version ermitteln
get_ddev_version() {
    debug_trace "get_ddev_version" "starting"
    local version=""
    if command -v ddev &> /dev/null; then
        # DDEV version gibt verschiedene Formate aus, versuche mehrere Patterns
        version=$(ddev version 2>/dev/null | grep -i "DDEV version" | grep -oP 'v?\K[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        if [ -z "$version" ]; then
            version=$(ddev version 2>/dev/null | grep -oP 'DDEV\s+version\s+v?\K[0-9]+\.[0-9]+' | head -1)
        fi
        if [ -z "$version" ]; then
            version=$(ddev version 2>/dev/null | grep -oP 'v?\K[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        fi
        debug_debug "get_ddev_version" "found version=$version"
    else
        debug_warning "get_ddev_version" "ddev command not found"
    fi
    echo "${version:-not installed}"
}

# Funktion: WoltLab-Version ermitteln
get_woltlab_version() {
    debug_trace "get_woltlab_version" "starting"
    local version=""
    local public_dir="${1:-}"
    
    # Wenn kein Pfad angegeben, versuche Standard-Pfade
    if [ -z "$public_dir" ]; then
        local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
        public_dir="$tools_dir/woltlab-dev/public"
    fi
    
    # Methode 1: Aus WCF.class.php lesen (WCF_VERSION Konstante)
    if [ -f "$public_dir/lib/system/WCF.class.php" ]; then
        debug_debug "get_woltlab_version" "trying WCF.class.php"
        # Versuche \define() mit Backslash (PHP Namespace)
        version=$(grep -oP "\\\\define\(['\"]WCF_VERSION['\"],\s*['\"]\K[^'\"]+" "$public_dir/lib/system/WCF.class.php" 2>/dev/null | head -1)
        if [ -z "$version" ]; then
            # Fallback: define() ohne Backslash
            version=$(grep -oP "define\(['\"]WCF_VERSION['\"],\s*['\"]\K[^'\"]+" "$public_dir/lib/system/WCF.class.php" 2>/dev/null | head -1)
        fi
        if [ -z "$version" ]; then
            # Fallback: Suche nach Version-String direkt
            version=$(grep "WCF_VERSION" "$public_dir/lib/system/WCF.class.php" 2>/dev/null | grep -oP "['\"][0-9]+\.[0-9]+\.[0-9]+['\"]" | tr -d "\"'" | head -1)
        fi
        if [ -n "$version" ] && [ "$version" != "WCF_VERSION" ] && echo "$version" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+'; then
            debug_info "get_woltlab_version" "found in WCF.class.php: $version"
            echo "$version"
            return 0
        fi
    fi
    
    # Methode 2: Aus Datenbank lesen (wcf1_package Tabelle)
    if command -v ddev &> /dev/null && ddev describe &>/dev/null 2>&1; then
        debug_debug "get_woltlab_version" "trying database"
        if ddev mysql -e "SELECT packageVersion FROM wcf1_package WHERE package = 'com.woltlab.wcf' LIMIT 1;" 2>/dev/null | tail -n 1 | grep -qE '^[0-9]+\.[0-9]+\.[0-9]'; then
            version=$(ddev mysql -e "SELECT packageVersion FROM wcf1_package WHERE package = 'com.woltlab.wcf' LIMIT 1;" 2>/dev/null | tail -n 1)
            if [ -n "$version" ]; then
                debug_info "get_woltlab_version" "found in database: $version"
                echo "$version"
                return 0
            fi
        fi
    fi
    
    debug_warning "get_woltlab_version" "could not determine version"
    echo "unknown"
    return 1
}

# Funktion: HeidiSQL-Version ermitteln (ohne HeidiSQL zu starten)
get_heidisql_version() {
    debug_trace "get_heidisql_version" "starting"
    local version=""
    
    # Methode 1: Prüfe Konfigurationsdatei (ohne HeidiSQL zu starten)
    local heidisql_config="$HOME/.config/heidisql/heidisql.ini"
    if [ -f "$heidisql_config" ]; then
        # Versuche Version aus INI-Datei zu extrahieren (falls vorhanden)
        version=$(grep -i "version\|Version" "$heidisql_config" 2>/dev/null | grep -oP '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        if [ -n "$version" ]; then
            debug_debug "get_heidisql_version" "found version=$version in config"
            echo "$version"
            return 0
        fi
        # Wenn Config existiert, ist HeidiSQL wahrscheinlich installiert
        debug_debug "get_heidisql_version" "config found, but no version info"
        echo "installed"
        return 0
    fi
    
    # Methode 2: Prüfe Installationspfade (ohne auszuführen)
    local heidisql_paths=(
        "/usr/bin/heidisql"
        "/usr/local/bin/heidisql"
        "$HOME/.local/bin/heidisql"
        "$HOME/heidisql/heidisql"
        "/opt/heidisql/heidisql"
    )
    
    for path in "${heidisql_paths[@]}"; do
        if [ -f "$path" ] && [ -x "$path" ]; then
            # Versuche Version aus Binary-Info zu extrahieren (ohne zu starten)
            # Verwende strings oder file, um Version zu finden
            if command -v strings &> /dev/null; then
                version=$(strings "$path" 2>/dev/null | grep -oP 'v?\K[0-9]+\.[0-9]+\.[0-9]+' | head -1)
            fi
            if [ -z "$version" ] && command -v file &> /dev/null; then
                # Fallback: Prüfe ob Binary existiert (dann ist es installiert)
                version="installed"
            fi
            if [ -n "$version" ]; then
                debug_debug "get_heidisql_version" "found at $path"
                echo "$version"
                return 0
            fi
        fi
    done
    
    # Fallback: Prüfe ob HeidiSQL über Windows-Subsystem verfügbar ist (ohne zu starten)
    if command -v heidisql.exe &> /dev/null; then
        debug_debug "get_heidisql_version" "found via WSL"
        echo "installed"
        return 0
    fi
    
    debug_debug "get_heidisql_version" "not found"
    echo "not installed"
}

# Funktion: PHP-Version ermitteln
get_php_version() {
    debug_trace "get_php_version" "starting"
    local version=""
    
    # Versuche DDEV PHP-Version zuerst (wenn DDEV läuft)
    if command -v ddev &> /dev/null; then
        # Prüfe ob DDEV läuft
        if ddev describe &>/dev/null 2>&1 && ddev describe 2>/dev/null | grep -q "running\|OK"; then
            version=$(ddev exec "php -v" 2>/dev/null | head -1 | grep -oP 'PHP\s+\K[0-9]+\.[0-9]+\.[0-9]+' | head -1)
            if [ -n "$version" ]; then
                debug_debug "get_php_version" "found via DDEV: $version"
                echo "$version"
                return 0
            fi
        else
            # DDEV ist installiert, aber nicht gestartet
            debug_debug "get_php_version" "DDEV installed but not running"
        fi
    fi
    
    # Fallback: System PHP
    if command -v php &> /dev/null; then
        version=$(php -v 2>/dev/null | head -1 | grep -oP 'PHP\s+\K[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        if [ -n "$version" ]; then
            debug_debug "get_php_version" "found system PHP: $version"
            echo "$version"
            return 0
        fi
    fi
    
    # Wenn DDEV installiert ist, aber nicht läuft
    if command -v ddev &> /dev/null; then
        debug_debug "get_php_version" "DDEV available but not running"
        echo "available via DDEV"
        return 0
    fi
    
    debug_warning "get_php_version" "not found"
    echo "not installed"
}

# Funktion: MySQL-Version ermitteln
get_mysql_version() {
    debug_trace "get_mysql_version" "starting"
    local version=""
    
    # Versuche DDEV MySQL-Version zuerst (wenn DDEV läuft)
    if command -v ddev &> /dev/null; then
        # Prüfe ob DDEV läuft
        if ddev describe &>/dev/null 2>&1 && ddev describe 2>/dev/null | grep -q "running\|OK"; then
            version=$(ddev mysql -e "SELECT VERSION();" 2>/dev/null | tail -n 1)
            if [ -n "$version" ] && echo "$version" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]'; then
                debug_debug "get_mysql_version" "found via DDEV: $version"
                echo "$version"
                return 0
            fi
        else
            # DDEV ist installiert, aber nicht gestartet
            debug_debug "get_mysql_version" "DDEV installed but not running"
        fi
    fi
    
    # Fallback: System MySQL
    if command -v mysql &> /dev/null; then
        version=$(mysql --version 2>/dev/null | grep -oP 'Distrib\s+\K[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        if [ -z "$version" ]; then
            version=$(mysql --version 2>/dev/null | grep -oP 'Ver\s+\K[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        fi
        if [ -n "$version" ]; then
            debug_debug "get_mysql_version" "found system MySQL: $version"
            echo "$version"
            return 0
        fi
    fi
    
    # Wenn DDEV installiert ist, aber nicht läuft
    if command -v ddev &> /dev/null; then
        debug_debug "get_mysql_version" "DDEV available but not running"
        echo "available via DDEV"
        return 0
    fi
    
    debug_warning "get_mysql_version" "not found"
    echo "not installed"
}

# Funktion: Docker-Version ermitteln
get_docker_version() {
    debug_trace "get_docker_version" "starting"
    local version=""
    
    if command -v docker &> /dev/null; then
        version=$(docker --version 2>/dev/null | grep -oP 'version\s+\K[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        if [ -z "$version" ]; then
            version=$(docker --version 2>/dev/null | grep -oP 'Docker version\s+\K[0-9]+\.[0-9]+' | head -1)
        fi
        if [ -n "$version" ]; then
            debug_debug "get_docker_version" "found version=$version"
            echo "$version"
            return 0
        fi
    fi
    
    debug_warning "get_docker_version" "not found"
    echo "not installed"
}

# Funktion: Git-Version ermitteln
get_git_version() {
    debug_trace "get_git_version" "starting"
    local version=""
    
    if command -v git &> /dev/null; then
        version=$(git --version 2>/dev/null | grep -oP 'git version\s+\K[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        if [ -n "$version" ]; then
            debug_debug "get_git_version" "found version=$version"
            echo "$version"
            return 0
        fi
    fi
    
    debug_warning "get_git_version" "not found"
    echo "not installed"
}

# Funktion: System-Übersicht anzeigen
show_system_overview() {
    debug_info "show_system_overview" "displaying system overview"
    
    print_section "System-Übersicht"
    
    echo -e "${CYAN}Installierte Tools & Versionen:${NC}"
    echo ""
    
    # Dockge
    local dockge_version=$(get_dockge_version)
    if [ "$dockge_version" != "not installed" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}Dockge:${NC}         ${YELLOW}${dockge_version}${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}Dockge:${NC}         ${YELLOW}nicht installiert${NC}"
    fi
    
    # DDEV
    local ddev_version=$(get_ddev_version)
    if [ "$ddev_version" != "not installed" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}DDEV:${NC}          ${YELLOW}v${ddev_version}${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}DDEV:${NC}          ${YELLOW}nicht installiert${NC}"
    fi
    
    # WoltLab
    local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    local woltlab_version=$(get_woltlab_version "$tools_dir/woltlab-dev/public")
    if [ "$woltlab_version" != "unknown" ] && [ "$woltlab_version" != "not installed" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}WoltLab Suite:${NC} ${YELLOW}${woltlab_version}${NC}"
    else
        echo -e "   ${YELLOW}?${NC} ${CYAN}WoltLab Suite:${NC} ${YELLOW}nicht gefunden${NC}"
    fi
    
    # HeidiSQL
    local heidisql_version=$(get_heidisql_version)
    if [ "$heidisql_version" != "not installed" ]; then
        if [ "$heidisql_version" = "installed" ]; then
            echo -e "   ${GREEN}✓${NC} ${CYAN}HeidiSQL:${NC}      ${YELLOW}installiert${NC}"
        else
            echo -e "   ${GREEN}✓${NC} ${CYAN}HeidiSQL:${NC}      ${YELLOW}${heidisql_version}${NC}"
        fi
    else
        echo -e "   ${YELLOW}?${NC} ${CYAN}HeidiSQL:${NC}      ${YELLOW}nicht gefunden${NC}"
    fi
    
    echo ""
    echo -e "${CYAN}System-Abhängigkeiten:${NC}"
    echo ""
    
    # PHP
    local php_version=$(get_php_version)
    if [ "$php_version" != "not installed" ] && [ "$php_version" != "available via DDEV" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}PHP:${NC}           ${YELLOW}${php_version}${NC}"
    elif [ "$php_version" = "available via DDEV" ]; then
        echo -e "   ${YELLOW}?${NC} ${CYAN}PHP:${NC}           ${YELLOW}verfügbar über DDEV (starte DDEV für Version)${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}PHP:${NC}           ${YELLOW}nicht installiert${NC}"
    fi
    
    # MySQL
    local mysql_version=$(get_mysql_version)
    if [ "$mysql_version" != "not installed" ] && [ "$mysql_version" != "available via DDEV" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}MySQL:${NC}         ${YELLOW}${mysql_version}${NC}"
    elif [ "$mysql_version" = "available via DDEV" ]; then
        echo -e "   ${YELLOW}?${NC} ${CYAN}MySQL:${NC}         ${YELLOW}verfügbar über DDEV (starte DDEV für Version)${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}MySQL:${NC}         ${YELLOW}nicht installiert${NC}"
    fi
    
    # Docker
    local docker_version=$(get_docker_version)
    if [ "$docker_version" != "not installed" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}Docker:${NC}        ${YELLOW}${docker_version}${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}Docker:${NC}        ${YELLOW}nicht installiert${NC}"
    fi
    
    # Git
    local git_version=$(get_git_version)
    if [ "$git_version" != "not installed" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}Git:${NC}          ${YELLOW}${git_version}${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}Git:${NC}          ${YELLOW}nicht installiert${NC}"
    fi
    
    echo ""
    
    # Zeige Log-Datei-Pfad
    echo -e "${CYAN}Debug-Log:${NC}"
    echo -e "   ${BLUE}${DEBUG_LOG_FILE}${NC}"
    if [ -f "$DEBUG_LOG_FILE" ]; then
        local log_size=$(du -h "$DEBUG_LOG_FILE" 2>/dev/null | cut -f1)
        local log_lines=$(wc -l < "$DEBUG_LOG_FILE" 2>/dev/null || echo "0")
        echo -e "   ${YELLOW}Größe: ${log_size} | Zeilen: ${log_lines}${NC}"
    fi
    echo ""
}

# ============================================================================
# Update-Checks und Systemvoraussetzungen
# ============================================================================

# Funktion: Prüfe WoltLab Systemvoraussetzungen
check_woltlab_requirements() {
    debug_info "check_woltlab_requirements" "checking WoltLab system requirements"
    
    local issues=0
    local warnings=0
    
    echo -e "${CYAN}WoltLab Systemvoraussetzungen:${NC}"
    echo ""
    echo -e "   ${CYAN}Basierend auf:${NC} https://manual.woltlab.com/de/requirements/"
    echo ""
    
    # PHP-Version prüfen
    local php_version=$(get_php_version)
    if [ "$php_version" != "not installed" ] && [ "$php_version" != "available via DDEV" ]; then
        # Extrahiere Major.Minor.Patch
        local php_major=$(echo "$php_version" | cut -d. -f1)
        local php_minor=$(echo "$php_version" | cut -d. -f2)
        local php_patch=$(echo "$php_version" | cut -d. -f3)
        
        # WoltLab benötigt PHP >= 8.1.2
        if [ "$php_major" -gt 8 ] || ([ "$php_major" -eq 8 ] && [ "$php_minor" -gt 1 ]) || ([ "$php_major" -eq 8 ] && [ "$php_minor" -eq 1 ] && [ "$php_patch" -ge 2 ]); then
            echo -e "   ${GREEN}✓${NC} ${CYAN}PHP:${NC} ${YELLOW}${php_version}${NC} ${GREEN}(>= 8.1.2)${NC}"
        else
            echo -e "   ${RED}✗${NC} ${CYAN}PHP:${NC} ${YELLOW}${php_version}${NC} ${RED}(< 8.1.2 erforderlich)${NC}"
            issues=$((issues + 1))
        fi
        
        # Prüfe PHP-Erweiterungen (wenn DDEV läuft)
        if command -v ddev &> /dev/null && ddev describe &>/dev/null 2>&1; then
            local missing_extensions=()
            
            # Wichtige Erweiterungen prüfen
            if ! ddev exec "php -m" 2>/dev/null | grep -q "gd\|imagick"; then
                missing_extensions+=("gd oder imagick (mit WebP-Support)")
            fi
            if ! ddev exec "php -m" 2>/dev/null | grep -q "pdo_mysql"; then
                missing_extensions+=("pdo_mysql")
            fi
            if ! ddev exec "php -m" 2>/dev/null | grep -q "mbstring"; then
                missing_extensions+=("mbstring")
            fi
            
            if [ ${#missing_extensions[@]} -gt 0 ]; then
                echo -e "   ${YELLOW}⚠${NC} ${CYAN}PHP-Erweiterungen fehlen:${NC} ${YELLOW}${missing_extensions[*]}${NC}"
                warnings=$((warnings + 1))
            fi
        fi
    elif [ "$php_version" = "available via DDEV" ]; then
        echo -e "   ${YELLOW}?${NC} ${CYAN}PHP:${NC} ${YELLOW}verfügbar über DDEV (starte DDEV für Prüfung)${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}PHP:${NC} ${YELLOW}nicht installiert${NC}"
        issues=$((issues + 1))
    fi
    
    # MySQL-Version prüfen
    local mysql_version=$(get_mysql_version)
    if [ "$mysql_version" != "not installed" ] && [ "$mysql_version" != "available via DDEV" ]; then
        # Extrahiere Major.Minor.Patch
        local mysql_major=$(echo "$mysql_version" | cut -d. -f1)
        local mysql_minor=$(echo "$mysql_version" | cut -d. -f2)
        local mysql_patch=$(echo "$mysql_version" | cut -d. -f3)
        
        # WoltLab benötigt MySQL >= 8.0.30 oder MariaDB >= 10.5.15
        if [ "$mysql_major" -gt 8 ] || ([ "$mysql_major" -eq 8 ] && [ "$mysql_minor" -gt 0 ]) || ([ "$mysql_major" -eq 8 ] && [ "$mysql_minor" -eq 0 ] && [ "$mysql_patch" -ge 30 ]); then
            echo -e "   ${GREEN}✓${NC} ${CYAN}MySQL:${NC} ${YELLOW}${mysql_version}${NC} ${GREEN}(>= 8.0.30)${NC}"
        elif echo "$mysql_version" | grep -qi "mariadb"; then
            # MariaDB Prüfung
            if [ "$mysql_major" -gt 10 ] || ([ "$mysql_major" -eq 10 ] && [ "$mysql_minor" -gt 5 ]) || ([ "$mysql_major" -eq 10 ] && [ "$mysql_minor" -eq 5 ] && [ "$mysql_patch" -ge 15 ]); then
                echo -e "   ${GREEN}✓${NC} ${CYAN}MariaDB:${NC} ${YELLOW}${mysql_version}${NC} ${GREEN}(>= 10.5.15)${NC}"
            else
                echo -e "   ${RED}✗${NC} ${CYAN}MariaDB:${NC} ${YELLOW}${mysql_version}${NC} ${RED}(< 10.5.15 erforderlich)${NC}"
                issues=$((issues + 1))
            fi
        else
            echo -e "   ${RED}✗${NC} ${CYAN}MySQL:${NC} ${YELLOW}${mysql_version}${NC} ${RED}(< 8.0.30 erforderlich)${NC}"
            issues=$((issues + 1))
        fi
    elif [ "$mysql_version" = "available via DDEV" ]; then
        echo -e "   ${YELLOW}?${NC} ${CYAN}MySQL:${NC} ${YELLOW}verfügbar über DDEV (starte DDEV für Prüfung)${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}MySQL:${NC} ${YELLOW}nicht installiert${NC}"
        issues=$((issues + 1))
    fi
    
    echo ""
    
    if [ $issues -eq 0 ] && [ $warnings -eq 0 ]; then
        echo -e "   ${GREEN}✓ Alle Systemvoraussetzungen erfüllt!${NC}"
        return 0
    elif [ $issues -eq 0 ]; then
        echo -e "   ${YELLOW}⚠ Systemvoraussetzungen erfüllt, aber Warnungen vorhanden${NC}"
        return 0
    else
        echo -e "   ${RED}✗ Systemvoraussetzungen nicht vollständig erfüllt!${NC}"
        echo -e "   ${YELLOW}Dokumentation:${NC} https://manual.woltlab.com/de/requirements/"
        return 1
    fi
}

# Funktion: Prüfe auf verfügbare Updates (kompakt)
check_updates() {
    debug_info "check_updates" "checking for available updates"
    
    print_section "Update-Prüfung"
    echo ""
    
    local updates_found=0
    local update_summary=()
    
    # DDEV Update prüfen (GitHub Releases API)
    if command -v ddev &> /dev/null; then
        local current_ddev=$(get_ddev_version)
        if [ "$current_ddev" != "not installed" ]; then
            local latest_ddev=""
            if command -v curl &> /dev/null; then
                latest_ddev=$(curl -s "https://api.github.com/repos/ddev/ddev/releases/latest" 2>/dev/null | grep -oP '"tag_name":\s*"v?\K[0-9]+\.[0-9]+\.[0-9]+' | head -1 || echo "")
            fi
            
            if [ -n "$latest_ddev" ] && [ "$current_ddev" != "$latest_ddev" ]; then
                echo -e "   ${YELLOW}⚠ DDEV:${NC} v${current_ddev} → v${latest_ddev} verfügbar"
                echo -e "      ${BLUE}https://github.com/ddev/ddev/releases/latest${NC}"
                updates_found=$((updates_found + 1))
            fi
        fi
    fi
    
    # Dockge Update prüfen (Docker Hub API)
    if command -v docker &> /dev/null && docker ps --format "{{.Names}}" 2>/dev/null | grep -q "^dockge$"; then
        local current_dockge=$(get_dockge_version)
        if [ "$current_dockge" != "not installed" ] && [ "$current_dockge" != "latest" ] && [ "$current_dockge" != "running" ]; then
            local latest_dockge=""
            if command -v curl &> /dev/null; then
                latest_dockge=$(curl -s "https://hub.docker.com/v2/repositories/louislam/dockge/tags?page_size=1&ordering=-last_updated" 2>/dev/null | grep -oP '"name":\s*"\K[0-9]+\.[0-9]+\.[0-9]+' | head -1 || echo "")
            fi
            
            if [ -n "$latest_dockge" ] && [ "$current_dockge" != "$latest_dockge" ]; then
                echo -e "   ${YELLOW}⚠ Dockge:${NC} ${current_dockge} → ${latest_dockge} verfügbar"
                echo -e "      ${BLUE}docker pull louislam/dockge:latest${NC}"
                updates_found=$((updates_found + 1))
            fi
        fi
    fi
    
    # Zusammenfassung
    echo ""
    if [ $updates_found -eq 0 ]; then
        echo -e "   ${GREEN}✓ Alle Tools sind auf dem neuesten Stand${NC}"
    else
        echo -e "   ${YELLOW}⚠ ${updates_found} Update(s) verfügbar${NC}"
    fi
    echo ""
    echo -e "   ${CYAN}ℹ️  Weitere Updates:${NC}"
    echo -e "      ${YELLOW}•${NC} WoltLab: Über ACP prüfen (${BLUE}https://manual.woltlab.com/de/installation/${NC})"
    echo -e "      ${YELLOW}•${NC} Docker/Git: Über System-Package-Manager"
    echo -e "      ${YELLOW}•${NC} HeidiSQL: Über HeidiSQL selbst (${BLUE}https://www.heidisql.com/help.php${NC})"
    echo ""
}

# Funktion: Zeige Update-Check (für Menü)
show_update_check() {
    check_woltlab_requirements
    echo ""
    check_updates
}

# Funktion: Header mit Titel
print_header() {
    local title="${1:-WoltLab Development Tools}"
    # Nur clear aufrufen, wenn interaktiv (TTY vorhanden)
    [ -t 0 ] && clear 2>/dev/null || true
    echo -e "${BLUE}==========================================${NC}"
    echo -e "${BLUE}${CYAN}${title}${NC}"
    echo -e "${BLUE}==========================================${NC}"
    echo ""
    debug_log "print_header" "title=$title"
}

# Funktion: Sektion-Header
print_section() {
    local title="$1"
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}${title}${NC}"
    echo -e "${CYAN}==========================================${NC}"
    echo ""
    debug_log "print_section" "title=$title"
}

# Funktion: Plugin-Verzeichnisse finden
find_plugin_directories() {
    local main_dir="$1"
    local plugins=()
    local seen=()
    
    # Funktion: Prüfe ob Verzeichnis bereits gesehen wurde
    is_seen() {
        local check_path="$1"
        local check_name=$(basename "$check_path")
        for seen_path in "${seen[@]}"; do
            if [ "$(basename "$seen_path")" = "$check_name" ]; then
                return 0
            fi
        done
        return 1
    }
    
    # Durchsuche spezifische Plugin-Verzeichnisse zuerst (höhere Priorität)
    local plugin_dirs=(
        "${main_dir}/basis-plugin"
        "${main_dir}/mein-plugin"
        "${main_dir}/plugins-integrieren"
    )
    
    for plugin_dir in "${plugin_dirs[@]}"; do
        # Prüfe direktes Verzeichnis
        if [ -d "$plugin_dir" ] && [ -f "$plugin_dir/package.xml" ]; then
            plugins+=("$plugin_dir")
            seen+=("$plugin_dir")
        fi
        
        # Durchsuche Unterverzeichnisse rekursiv (z.B. mein-plugin/extracted_plugin/*)
        if [ -d "$plugin_dir" ]; then
            # Rekursive Suche nach package.xml in Unterverzeichnissen (max 3 Ebenen tief)
            while IFS= read -r -d '' subdir; do
                if [ -d "$subdir" ] && [ -f "$subdir/package.xml" ]; then
                    if ! is_seen "$subdir"; then
                        plugins+=("$subdir")
                        seen+=("$subdir")
                    fi
                fi
            done < <(find "$plugin_dir" -mindepth 1 -maxdepth 3 -type d -print0 2>/dev/null)
        fi
    done
    
    # Durchsuche Hauptverzeichnis (nur wenn noch nicht gefunden)
    for dir in "${main_dir}"/*; do
        if [ -d "$dir" ] && [ -f "$dir/package.xml" ]; then
            if ! is_seen "$dir"; then
                plugins+=("$dir")
                seen+=("$dir")
            fi
        fi
    done
    
    printf '%s\n' "${plugins[@]}"
}

# Funktion: Plugin-Version aus package.xml lesen (mit Fallbacks)
get_plugin_version() {
    local plugin_dir="$1"
    local version=""
    
    # Methode 1: Direkte package.xml
    if [ -f "$plugin_dir/package.xml" ]; then
        # Versuche verschiedene Patterns
        version=$(grep -oP '<version>\K[^<]+' "$plugin_dir/package.xml" 2>/dev/null | head -1)
        
        # Fallback: Anderes Pattern
        if [ -z "$version" ] || [ "$version" = "" ]; then
            version=$(grep -E '<version>' "$plugin_dir/package.xml" 2>/dev/null | sed 's/.*<version>\([^<]*\)<\/version>.*/\1/' | head -1)
        fi
        
        # Fallback: sed-basiert
        if [ -z "$version" ] || [ "$version" = "" ]; then
            version=$(sed -n 's/.*<version>\([^<]*\)<\/version>.*/\1/p' "$plugin_dir/package.xml" 2>/dev/null | head -1)
        fi
    fi
    
    # Methode 2: _extracted/package.xml
    if [ -z "$version" ] || [ "$version" = "" ]; then
        if [ -f "$plugin_dir/_extracted/package.xml" ]; then
            version=$(grep -oP '<version>\K[^<]+' "$plugin_dir/_extracted/package.xml" 2>/dev/null | head -1)
            if [ -z "$version" ] || [ "$version" = "" ]; then
                version=$(sed -n 's/.*<version>\([^<]*\)<\/version>.*/\1/p' "$plugin_dir/_extracted/package.xml" 2>/dev/null | head -1)
            fi
        fi
    fi
    
    # Fallback: Versuche aus Verzeichnisnamen zu extrahieren (z.B. plugin_v1.2.3)
    if [ -z "$version" ] || [ "$version" = "" ]; then
        local dirname=$(basename "$plugin_dir")
        if echo "$dirname" | grep -qE '_v?[0-9]+\.[0-9]+\.[0-9]+'; then
            version=$(echo "$dirname" | grep -oE 'v?[0-9]+\.[0-9]+\.[0-9]+' | head -1 | sed 's/^v//')
        fi
    fi
    
    # Letzter Fallback
    if [ -z "$version" ] || [ "$version" = "" ]; then
        version="unknown"
    fi
    
    echo "$version"
}

# Funktion: Plugin-Name aus package.xml lesen (mit Fallbacks)
get_plugin_name() {
    local plugin_dir="$1"
    local name=""
    
    if [ -f "$plugin_dir/package.xml" ]; then
        # Pattern 1: <packagename>...</packagename> (WoltLab Standard, ohne language-Attribut)
        name=$(grep -oP '<packagename(?:\s+[^>]*)?>\K[^<]+' "$plugin_dir/package.xml" 2>/dev/null | head -1)
        
        # Pattern 2: <packagename> mit sed (Fallback)
        if [ -z "$name" ] || [ "$name" = "" ]; then
            name=$(sed -n 's/.*<packagename[^>]*>\([^<]*\)<\/packagename>.*/\1/p' "$plugin_dir/package.xml" 2>/dev/null | head -1)
        fi
        
        # Pattern 3: <name>...</name> (alternativ)
        if [ -z "$name" ] || [ "$name" = "" ]; then
            name=$(grep -oP '<name>\K[^<]+' "$plugin_dir/package.xml" 2>/dev/null | head -1)
        fi
        
        # Pattern 4: <name> mit sed (Fallback)
        if [ -z "$name" ] || [ "$name" = "" ]; then
            name=$(sed -n 's/.*<name>\([^<]*\)<\/name>.*/\1/p' "$plugin_dir/package.xml" 2>/dev/null | head -1)
        fi
        
        # Pattern 5: <package name="..."> (falls name leer)
        if [ -z "$name" ] || [ "$name" = "" ]; then
            name=$(grep -oP '<package[^>]*name="\K[^"]+' "$plugin_dir/package.xml" 2>/dev/null | head -1)
        fi
        
        # Pattern 6: <package name="..."> mit sed (Fallback)
        if [ -z "$name" ] || [ "$name" = "" ]; then
            name=$(sed -n 's/.*<package[^>]*name="\([^"]*\)".*/\1/p' "$plugin_dir/package.xml" 2>/dev/null | head -1)
        fi
    fi
    
    # Methode 2: _extracted/package.xml
    if [ -z "$name" ] || [ "$name" = "" ]; then
        if [ -f "$plugin_dir/_extracted/package.xml" ]; then
            name=$(grep -oP '<packagename[^>]*>\K[^<]+' "$plugin_dir/_extracted/package.xml" 2>/dev/null | head -1)
            if [ -z "$name" ] || [ "$name" = "" ]; then
                name=$(sed -n 's/.*<packagename[^>]*>\([^<]*\)<\/packagename>.*/\1/p' "$plugin_dir/_extracted/package.xml" 2>/dev/null | head -1)
            fi
            if [ -z "$name" ] || [ "$name" = "" ]; then
                name=$(grep -oP '<name>\K[^<]+' "$plugin_dir/_extracted/package.xml" 2>/dev/null | head -1)
            fi
        fi
    fi
    
    # Fallback: Versuche aus Verzeichnisnamen zu extrahieren
    if [ -z "$name" ] || [ "$name" = "" ]; then
        local dirname=$(basename "$plugin_dir")
        # Entferne Versionsnummern und Unterstrich-Präfixe
        name=$(echo "$dirname" | sed -E 's/_v?[0-9]+\.[0-9]+\.[0-9]+.*$//' | sed 's/^extracted_plugin\///')
    fi
    
    # Letzter Fallback: Verwende Verzeichnisname
    if [ -z "$name" ] || [ "$name" = "" ]; then
        name=$(basename "$plugin_dir")
    fi
    
    echo "$name"
}

# Funktion: Erfolgsmeldung
print_success() {
    echo -e "${GREEN}${CHECK} $1${NC}"
}

# Funktion: Fehlermeldung
print_error() {
    echo -e "${RED}${CROSS} $1${NC}"
}

# Funktion: Warnung
print_warning() {
    echo -e "${YELLOW}${WARNING} $1${NC}"
}

# Funktion: Info
print_info() {
    echo -e "${BLUE}${INFO} $1${NC}"
}

# Funktion: Fortschrittsanzeige
print_step() {
    local step="$1"
    local total="$2"
    local message="$3"
    echo -e "${YELLOW}[${step}/${total}] ${message}...${NC}"
}

# Funktion: Browser öffnen (mit mehreren Fallbacks)
open_browser() {
    local url="$1"
    
    if [ -z "$url" ]; then
        print_error "Keine URL angegeben!"
        return 1
    fi
    
    # Methode 1: xdg-open (Linux Standard)
    if command -v xdg-open &> /dev/null; then
        xdg-open "$url" &>/dev/null 2>&1 && return 0
    fi
    
    # Methode 2: open (macOS)
    if command -v open &> /dev/null; then
        open "$url" &>/dev/null 2>&1 && return 0
    fi
    
    # Methode 3: firefox (direkt)
    if command -v firefox &> /dev/null; then
        firefox "$url" &>/dev/null 2>&1 && return 0
    fi
    
    # Methode 4: google-chrome (Chrome)
    if command -v google-chrome &> /dev/null; then
        google-chrome "$url" &>/dev/null 2>&1 && return 0
    fi
    
    # Methode 5: chromium (Chromium)
    if command -v chromium &> /dev/null; then
        chromium "$url" &>/dev/null 2>&1 && return 0
    fi
    
    # Methode 6: brave-browser (Brave)
    if command -v brave-browser &> /dev/null; then
        brave-browser "$url" &>/dev/null 2>&1 && return 0
    fi
    
    # Methode 7: opera (Opera)
    if command -v opera &> /dev/null; then
        opera "$url" &>/dev/null 2>&1 && return 0
    fi
    
    # Methode 8: konqueror (KDE)
    if command -v konqueror &> /dev/null; then
        konqueror "$url" &>/dev/null 2>&1 && return 0
    fi
    
    # Fallback: Zeige URL an
    print_warning "Konnte Browser nicht automatisch öffnen"
    echo -e "${YELLOW}Öffne manuell:${NC} ${BLUE}${url}${NC}"
    return 1
}

# Funktion: Datei kopieren (mit Fallback)
safe_copy() {
    local source="$1"
    local dest="$2"
    
    if [ -z "$source" ] || [ -z "$dest" ]; then
        return 1
    fi
    
    # Methode 1: cp (Standard)
    if cp "$source" "$dest" 2>/dev/null; then
        return 0
    fi
    
    # Methode 2: cp mit sudo (falls Berechtigung fehlt)
    if sudo cp "$source" "$dest" 2>/dev/null; then
        return 0
    fi
    
    # Methode 3: cat + redirect (als letzter Fallback)
    if [ -f "$source" ]; then
        cat "$source" > "$dest" 2>/dev/null && return 0
    fi
    
    return 1
}

# Funktion: Verzeichnis kopieren (mit Fallback)
safe_copy_dir() {
    local source="$1"
    local dest="$2"
    
    if [ -z "$source" ] || [ -z "$dest" ]; then
        return 1
    fi
    
    # Methode 1: rsync (schnell und zuverlässig)
    if command -v rsync &> /dev/null; then
        if rsync -a "$source/" "$dest/" 2>/dev/null; then
            return 0
        fi
    fi
    
    # Methode 2: cp -r (Fallback)
    if cp -r "$source"/* "$dest/" 2>/dev/null; then
        return 0
    fi
    
    # Methode 3: cp -R (alternative Syntax)
    if cp -R "$source"/* "$dest/" 2>/dev/null; then
        return 0
    fi
    
    # Methode 4: find + cp (als letzter Fallback)
    if [ -d "$source" ]; then
        mkdir -p "$dest" 2>/dev/null || true
        find "$source" -type f -exec cp --parents {} "$dest" \; 2>/dev/null && return 0
    fi
    
    return 1
}

# Funktion: Verzeichnis erstellen (mit Fallback)
safe_mkdir() {
    local dir="$1"
    
    if [ -z "$dir" ]; then
        return 1
    fi
    
    # Methode 1: mkdir -p (Standard)
    if mkdir -p "$dir" 2>/dev/null; then
        return 0
    fi
    
    # Methode 2: mkdir -p mit sudo (falls Berechtigung fehlt)
    if sudo mkdir -p "$dir" 2>/dev/null; then
        # Setze Berechtigungen
        sudo chown "$USER:$USER" "$dir" 2>/dev/null || true
        return 0
    fi
    
    return 1
}

# Funktion: Datei löschen (mit Fallback)
safe_remove() {
    local target="$1"
    
    if [ -z "$target" ]; then
        return 1
    fi
    
    # Methode 1: rm (Standard)
    if rm -rf "$target" 2>/dev/null; then
        return 0
    fi
    
    # Methode 2: rm mit sudo (falls Berechtigung fehlt)
    if sudo rm -rf "$target" 2>/dev/null; then
        return 0
    fi
    
    # Methode 3: find + delete (als letzter Fallback)
    if [ -d "$target" ]; then
        find "$target" -delete 2>/dev/null && return 0
    elif [ -f "$target" ]; then
        > "$target" 2>/dev/null && rm -f "$target" 2>/dev/null && return 0
    fi
    
    return 1
}

# Funktion: HeidiSQL Passwort verschlüsseln (Obfuscation)
# Algorithmus: byteweise Addition mit Salt, gefolgt von Hex-Kodierung
heidisql_encrypt_password() {
    local password="$1"
    local salt="${2:-$((RANDOM % 16))}"  # Zufälliger Salt 0-15 wenn nicht angegeben
    
    if [ -z "$password" ]; then
        echo ""
        return 0
    fi
    
    # Versuche Python-Methode (schneller und zuverlässiger)
    if command -v python3 &> /dev/null; then
        python3 <<EOF
import random
import sys

def encrypt_password(pw, salt_val):
    if not pw:
        return ''
    result = ''
    for c in pw:
        nr = (ord(c) + salt_val) % 256
        result += f"{nr:02X}"
    result += f"{salt_val:X}"
    return result

password = sys.argv[1]
salt = int(sys.argv[2])
print(encrypt_password(password, salt))
EOF
        return $?
    fi
    
    # Bash-Fallback (langsamer, aber funktioniert immer)
    local result=""
    local i=0
    while [ $i -lt ${#password} ]; do
        local char="${password:$i:1}"
        local ascii=$(printf "%d" "'$char")
        local nr=$(( (ascii + salt) % 256 ))
        result=$(printf "%s%02X" "$result" "$nr")
        i=$((i + 1))
    done
    result=$(printf "%s%X" "$result" "$salt")
    echo "$result"
}

# Funktion: HeidiSQL Passwort entschlüsseln (für Tests)
heidisql_decrypt_password() {
    local obfuscated="$1"
    
    if [ -z "$obfuscated" ]; then
        echo ""
        return 0
    fi
    
    # Versuche Python-Methode
    if command -v python3 &> /dev/null; then
        python3 <<EOF
import sys

def decrypt_password(s):
    if not s:
        return ''
    salt = int(s[-1], 16)
    result = ''
    hex_pairs = [s[i:i+2] for i in range(0, len(s)-1, 2)]
    for hex_pair in hex_pairs:
        nr = int(hex_pair, 16) - salt
        if nr < 0:
            nr += 256
        result += chr(nr)
    return result

obfuscated = sys.argv[1]
print(decrypt_password(obfuscated))
EOF
        return $?
    fi
    
    # Bash-Fallback
    local salt_hex="${obfuscated: -1}"
    local salt=$((16#$salt_hex))
    local hex_part="${obfuscated%?}"
    local result=""
    local i=0
    while [ $i -lt ${#hex_part} ]; do
        local hex_pair="${hex_part:$i:2}"
        local nr=$((16#$hex_pair - salt))
        if [ $nr -lt 0 ]; then
            nr=$((nr + 256))
        fi
        result=$(printf "%s%c" "$result" "$(printf "\\$(printf "%03o" $nr)")")
        i=$((i + 2))
    done
    echo "$result"
}

# Funktion: HeidiSQL Server-Eintrag in INI-Datei speichern
heidisql_save_config() {
    local server_name="${1:-WoltLab DDEV}"
    local host="${2:-127.0.0.1}"
    local port="${3:-3306}"
    local user="${4:-db}"
    local password="${5:-db}"
    local database="${6:-db}"
    local ini_file="${7:-$HOME/.config/heidisql/heidisql.ini}"
    
    # Erstelle Verzeichnis falls nicht vorhanden
    local ini_dir=$(dirname "$ini_file")
    if [ ! -d "$ini_dir" ]; then
        mkdir -p "$ini_dir" 2>/dev/null || {
            print_error "Konnte HeidiSQL-Konfigurationsverzeichnis nicht erstellen: $ini_dir"
            return 1
        }
    fi
    
    # Verschlüssele Passwort
    local encrypted_password=$(heidisql_encrypt_password "$password")
    
    if [ -z "$encrypted_password" ]; then
        print_error "Konnte Passwort nicht verschlüsseln!"
        return 1
    fi
    
    # Erstelle oder aktualisiere Server-Eintrag
    local section_name="Servers\\${server_name}"
    
    # Prüfe ob INI-Datei existiert
    if [ ! -f "$ini_file" ]; then
        touch "$ini_file"
    fi
    
    # Entferne existierenden Eintrag falls vorhanden
    if grep -q "^\[${section_name}\]" "$ini_file" 2>/dev/null; then
        # Finde Start und Ende des Abschnitts
        local start_line=$(grep -n "^\[${section_name}\]" "$ini_file" | cut -d: -f1 | head -1)
        if [ -n "$start_line" ]; then
            local end_line=$(sed -n "$((start_line + 1)),\$p" "$ini_file" | grep -n "^\[" | head -1 | cut -d: -f1)
            if [ -n "$end_line" ]; then
                end_line=$((start_line + end_line - 1))
            else
                end_line=$(wc -l < "$ini_file")
            fi
            # Entferne den Abschnitt
            sed -i "${start_line},${end_line}d" "$ini_file"
        fi
    fi
    
    # Füge neuen Eintrag hinzu
    {
        echo "[${section_name}]"
        echo "Host=${host}"
        echo "Port=${port}"
        echo "User=${user}"
        echo "Password=${encrypted_password}"
        echo "Database=${database}"
        echo ""
    } >> "$ini_file"
    
    print_success "HeidiSQL Server-Eintrag gespeichert: $server_name"
    print_info "Konfigurationsdatei: $ini_file"
    return 0
}
