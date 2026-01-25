#!/usr/bin/env bash

#################################################################
# WoltLab Development Tools - Gemeinsame Funktionen
# Zentrale Funktionen für alle Tools (Farben, Formatierung, etc.)
# 
# Kompatibilität: Linux, macOS, Windows WSL2
#################################################################

# ============================================================
# Plattform-Erkennung und Kompatibilitäts-Funktionen
# ============================================================

# Funktion: Plattform erkennen
detect_platform() {
    local platform="unknown"
    
    # Prüfe Betriebssystem
    if [[ "$OSTYPE" == "linux-gnu"* ]] || [[ "$OSTYPE" == "linux-musl"* ]]; then
        platform="linux"
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        platform="macos"
    elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]] || grep -qi microsoft /proc/version 2>/dev/null; then
        platform="wsl"
    fi
    
    echo "$platform"
}

# Setze Plattform-Variable
PLATFORM=$(detect_platform)

# Funktion: Prüft ob Befehl existiert (plattformkompatibel)
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Funktion: Stellt sicher, dass ein Script ausführbar ist
ensure_executable() {
    local script_path="$1"
    if [ -z "$script_path" ]; then
        return 1
    fi
    if [ -f "$script_path" ] && [ ! -x "$script_path" ]; then
        chmod +x "$script_path" 2>/dev/null || {
            print_warning "Keine Berechtigung, um ${script_path} ausführbar zu machen."
            return 1
        }
        print_info "Ausführungsrecht gesetzt: ${script_path}"
    fi
    return 0
}

# Funktion: grep mit Perl-Regex (plattformkompatibel)
# Falls grep -P nicht verfügbar ist, verwende perl oder awk
grep_perl() {
    local pattern="$1"
    shift
    
    # Versuche zuerst grep -P (GNU grep)
    if grep -P "$pattern" "$@" 2>/dev/null; then
        return 0
    fi
    
    # Fallback: perl (verfügbar auf macOS, Linux, WSL)
    if command_exists perl; then
        perl -ne "print if /$pattern/" "$@"
        return $?
    fi
    
    # Fallback: awk (verfügbar auf allen Plattformen)
    # Hinweis: awk unterstützt keine vollständigen Perl-Regex, aber einfache Patterns
    awk "/$pattern/" "$@"
}

# Funktion: sed -i plattformkompatibel
# macOS benötigt sed -i '' oder sed -i.bak
sed_inplace() {
    if [[ "$PLATFORM" == "macos" ]]; then
        sed -i '' "$@"
    else
        sed -i "$@"
    fi
}

# Funktion: Package Manager erkennen
detect_package_manager() {
    if command_exists pacman; then
        echo "pacman"
    elif command_exists apt-get || command_exists apt; then
        echo "apt"
    elif command_exists yum || command_exists dnf; then
        echo "yum"
    elif command_exists brew; then
        echo "brew"
    elif command_exists pkg; then
        echo "pkg"
    else
        echo "unknown"
    fi
}

# Setze Package Manager
PACKAGE_MANAGER=$(detect_package_manager)

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

# Funktion: Erweiterte Fehler-Log-Funktion mit Kontext
log_error_with_context() {
    local error_msg="$1"
    local context="${2:-}"
    local script_name="$(_get_script_name)"
    local function_name="$(_get_function_name)"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    # Logge mit vollem Kontext
    debug_log "ERROR" "$error_msg" "script=$script_name function=$function_name context=$context timestamp=$timestamp"
    
    # Zeige benutzerfreundliche Fehlermeldung
    print_error "$error_msg"
    if [ -n "$context" ]; then
        print_info "Kontext: $context"
    fi
    print_info "Script: $script_name | Funktion: $function_name"
    print_info "Details im Log: $DEBUG_LOG_FILE"
}

# Funktion: Log-Datei anzeigen
show_debug_log() {
    local lines="${1:-50}"
    if [ -f "$DEBUG_LOG_FILE" ]; then
        print_section "Debug-Log (letzte $lines Zeilen)"
        tail -n "$lines" "$DEBUG_LOG_FILE"
        echo ""
        print_info "Vollständige Log-Datei: $DEBUG_LOG_FILE"
        print_info "Log-Größe: $(du -h "$DEBUG_LOG_FILE" 2>/dev/null | cut -f1 || echo 'unbekannt')"
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
# Funktion: Dockge-Status prüfen
get_dockge_status() {
    if ! command -v docker &> /dev/null || ! docker info &>/dev/null 2>&1; then
        echo "docker_not_available"
        return
    fi
    
    # Prüfe ob Dockge-Container existiert
    if docker ps -a --format "{{.Names}}" 2>/dev/null | grep -q "^dockge$"; then
        # Prüfe ob Container läuft
        if docker ps --format "{{.Names}}" 2>/dev/null | grep -q "^dockge$"; then
            echo "running"
        else
            echo "stopped"
        fi
    else
        echo "not_installed"
    fi
}

# Funktion: Dockge-Informationen sammeln
get_dockge_info() {
    local dockge_state=$(get_dockge_status)
    local info=""
    
    if [ "$dockge_state" = "running" ]; then
        # Extrahiere Port aus Container
        local port=$(docker inspect dockge --format='{{range $p, $conf := .NetworkSettings.Ports}}{{(index $conf 0).HostPort}}{{end}}' 2>/dev/null | head -1)
        if [ -z "$port" ]; then
            # Fallback: Standard-Port
            port="5001"
        fi
        
        # Extrahiere Image-Version
        local image=$(docker inspect dockge --format='{{.Config.Image}}' 2>/dev/null || echo "")
        local version=""
        if [ -n "$image" ]; then
            # Extrahiere Version aus Image-Name (z.B. louislam/dockge:1.4.2)
            version=$(echo "$image" | grep -oE ':[^:]+$' | sed 's/^://' || echo "latest")
        fi
        
        # URL zusammenstellen
        local url="http://localhost:${port}"
        
        # Informationen untereinander formatieren
        info="Status: läuft\n      URL: ${url}\n      Container: dockge\n      Image: ${version}"
    elif [ "$dockge_state" = "stopped" ]; then
        info="gestoppt (Container vorhanden)"
    elif [ "$dockge_state" = "docker_not_available" ]; then
        info="Docker nicht verfügbar"
    else
        info="nicht installiert"
    fi
    
    echo "$info"
}

get_dockge_version() {
    debug_trace "get_dockge_version" "starting"
    local dockge_state=$(get_dockge_status)
    
    if [ "$dockge_state" = "running" ]; then
        # Versuche Version aus Container-Image zu extrahieren
        local image=$(docker inspect dockge --format='{{.Config.Image}}' 2>/dev/null || echo "")
        local version=""
        
        if [ -n "$image" ]; then
            # Extrahiere Version aus Image-Name (z.B. louislam/dockge:1.4.2)
            version=$(echo "$image" | grep -oE ':[^:]+$' | sed 's/^://' || echo "")
            
            # Wenn keine Version im Image-Name, versuche Labels
            if [ -z "$version" ] || [ "$version" = "latest" ]; then
                version=$(docker inspect dockge --format='{{index .Config.Labels "org.opencontainers.image.version"}}' 2>/dev/null || echo "")
            fi
            
            if [ -z "$version" ] || [ "$version" = "<no value>" ]; then
                version="running"
            fi
        else
            version="running"
        fi
        
        debug_debug "get_dockge_version" "found version=$version"
        echo "$version"
    elif [ "$dockge_state" = "stopped" ]; then
        echo "stopped"
    else
        echo "not installed"
    fi
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

# Funktion: phpMyAdmin-Version ermitteln
get_phpmyadmin_version() {
    debug_trace "get_phpmyadmin_version" "starting"
    local version=""
    
    # Methode 1: Prüfe ob phpMyAdmin über DDEV verfügbar ist
    # DDEV liefert phpMyAdmin standardmäßig als Container mit
    if command -v ddev &> /dev/null; then
        local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
        local ddev_dir="$tools_dir/woltlab-dev"
        if [ -d "$ddev_dir" ] && [ -f "$ddev_dir/.ddev/config.yaml" ]; then
            # DDEV-Projekt existiert → phpMyAdmin ist grundsätzlich verfügbar
            debug_debug "get_phpmyadmin_version" "DDEV project found, phpMyAdmin available by default"
            
            # Prüfe ob phpMyAdmin-Container läuft
            if command -v docker &> /dev/null; then
                local pma_container=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -E "ddev-.*-phpmyadmin|ddev-woltlab-phpmyadmin" | head -1)
                if [ -n "$pma_container" ]; then
                    debug_debug "get_phpmyadmin_version" "phpMyAdmin container running: $pma_container"
                    echo "available via DDEV"
                    return 0
                fi
            fi
            
            # Prüfe ob DDEV läuft und phpMyAdmin erreichbar ist
            if _is_ddev_running; then
                # Versuche phpMyAdmin-URL aus ddev describe zu extrahieren
                cd "$ddev_dir" 2>/dev/null || true
                local pma_url=$(ddev describe 2>/dev/null | grep -oE 'https?://[^/]+/phpmyadmin' | head -1)
                cd - > /dev/null 2>&1 || true
                if [ -n "$pma_url" ]; then
                    debug_debug "get_phpmyadmin_version" "found via DDEV (running): $pma_url"
                    echo "available via DDEV"
                    return 0
                fi
                
                # Fallback: Prüfe ob phpMyAdmin-Container existiert (auch wenn gestoppt)
                if command -v docker &> /dev/null; then
                    local pma_container_all=$(docker ps -a --format "{{.Names}}" 2>/dev/null | grep -E "ddev-.*-phpmyadmin|ddev-woltlab-phpmyadmin" | head -1)
                    if [ -n "$pma_container_all" ]; then
                        debug_debug "get_phpmyadmin_version" "phpMyAdmin container exists: $pma_container_all"
                        echo "available via DDEV"
                        return 0
                    fi
                fi
            fi
            
            # DDEV-Projekt existiert → phpMyAdmin ist verfügbar (wird beim Start automatisch gestartet)
            echo "available via DDEV"
            return 0
        fi
    fi
    
    # Methode 2: Prüfe ob phpMyAdmin lokal installiert ist
    local phpmyadmin_paths=(
        "/usr/share/phpmyadmin"
        "/var/www/phpmyadmin"
        "/opt/phpmyadmin"
        "$HOME/phpmyadmin"
    )
    
    for path in "${phpmyadmin_paths[@]}"; do
        if [ -d "$path" ] && [ -f "$path/README" ] || [ -f "$path/index.php" ]; then
            # Versuche Version aus README oder Version-Datei zu extrahieren
            if [ -f "$path/README" ]; then
                version=$(grep -oP 'phpMyAdmin\s+\K[0-9]+\.[0-9]+\.[0-9]+' "$path/README" 2>/dev/null | head -1)
            fi
            if [ -z "$version" ] && [ -f "$path/libraries/Config.php" ]; then
                version=$(grep -oP "VERSION\s*=\s*['\"]([0-9]+\.[0-9]+\.[0-9]+)" "$path/libraries/Config.php" 2>/dev/null | grep -oP "['\"]([0-9]+\.[0-9]+\.[0-9]+)" | tr -d "'\"" | head -1)
            fi
            if [ -n "$version" ]; then
                debug_debug "get_phpmyadmin_version" "found at $path: $version"
                echo "$version"
                return 0
            fi
            if [ -d "$path" ]; then
                debug_debug "get_phpmyadmin_version" "found at $path (no version info)"
                echo "installed"
                return 0
            fi
        fi
    done
    
    debug_debug "get_phpmyadmin_version" "not found"
    echo "not installed"
}

# Funktion: PHP-Version ermitteln
# Hilfsfunktion: Prüft ob DDEV läuft (Container-basiert)
_is_ddev_running() {
    if ! command -v docker &> /dev/null; then
        return 1
    fi
    
    # Prüfe ob DDEV-Container laufen
    local web_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-web" 2>/dev/null | tr -d '\n\r ' || echo "0")
    local db_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-db" 2>/dev/null | tr -d '\n\r ' || echo "0")
    
    # Bereinige Werte: entferne alle Zeichen außer Ziffern
    web_count=$(echo "$web_count" | tr -d '\n\r ' | grep -oE '^[0-9]+$' || echo "0")
    db_count=$(echo "$db_count" | tr -d '\n\r ' | grep -oE '^[0-9]+$' || echo "0")
    
    if [ "$web_count" -ge 1 ] && [ "$db_count" -ge 1 ]; then
        return 0  # DDEV läuft
    else
        return 1  # DDEV läuft nicht
    fi
}

get_php_version() {
    debug_trace "get_php_version" "starting"
    local version=""
    
    # Versuche PHP-Version aus DDEV-Konfiguration zu lesen (auch wenn DDEV nicht läuft)
    if command -v ddev &> /dev/null; then
        local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
        local ddev_config="$tools_dir/woltlab-dev/.ddev/config.yaml"
        if [ -f "$ddev_config" ]; then
            version=$(grep -E '^php_version:' "$ddev_config" 2>/dev/null | sed -E 's/^php_version:\s*["\047]?([0-9]+\.[0-9]+)["\047]?.*/\1/' | tr -d '\n\r ')
            if [ -n "$version" ] && echo "$version" | grep -qE '^[0-9]+\.[0-9]+'; then
                # Konvertiere 8.3 zu 8.3.0 für Anzeige
                if echo "$version" | grep -qE '^[0-9]+\.[0-9]+$'; then
                    version="${version}.0"
                fi
                debug_debug "get_php_version" "found via DDEV config: $version"
                echo "$version"
                return 0
            fi
        fi
        
        # Versuche DDEV PHP-Version aus Container (wenn DDEV läuft) - als Fallback
        if _is_ddev_running; then
            local ddev_dir="$tools_dir/woltlab-dev"
            if [ -d "$ddev_dir" ]; then
                cd "$ddev_dir" 2>/dev/null || true
                version=$(ddev exec "php -v" 2>/dev/null | head -1 | grep -oP 'PHP\s+\K[0-9]+\.[0-9]+\.[0-9]+' | head -1 | tr -d '\n\r ')
                cd - > /dev/null 2>&1 || true
            else
                version=$(ddev exec "php -v" 2>/dev/null | head -1 | grep -oP 'PHP\s+\K[0-9]+\.[0-9]+\.[0-9]+' | head -1 | tr -d '\n\r ')
            fi
            
            if [ -n "$version" ] && echo "$version" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+'; then
                debug_debug "get_php_version" "found via DDEV container: $version"
                echo "$version"
                return 0
            fi
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
    
    # Versuche MySQL-Version aus DDEV-Konfiguration zu lesen (auch wenn DDEV nicht läuft)
    if command -v ddev &> /dev/null; then
        local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
        local ddev_config="$tools_dir/woltlab-dev/.ddev/config.yaml"
        if [ -f "$ddev_config" ]; then
            # Suche nach database: version: in der YAML-Datei
            version=$(grep -A 2 '^database:' "$ddev_config" 2>/dev/null | grep -E '^\s+version:' | sed -E 's/^\s+version:\s*["\047]?([0-9]+\.[0-9]+)["\047]?.*/\1/' | tr -d '\n\r ')
            if [ -n "$version" ] && echo "$version" | grep -qE '^[0-9]+\.[0-9]+'; then
                # Konvertiere 8.0 zu 8.0.0 für Anzeige
                if echo "$version" | grep -qE '^[0-9]+\.[0-9]+$'; then
                    version="${version}.0"
                fi
                debug_debug "get_mysql_version" "found via DDEV config: $version"
                echo "$version"
                return 0
            fi
        fi
        
        # Versuche MySQL-Version aus DDEV-Container (wenn DDEV läuft) - als Fallback
        if _is_ddev_running; then
            local ddev_dir="$tools_dir/woltlab-dev"
            if [ -d "$ddev_dir" ]; then
                cd "$ddev_dir" 2>/dev/null || true
                version=$(ddev mysql -e "SELECT VERSION();" 2>/dev/null | tail -n 1 | tr -d '\n\r ')
                cd - > /dev/null 2>&1 || true
            else
                version=$(ddev mysql -e "SELECT VERSION();" 2>/dev/null | tail -n 1 | tr -d '\n\r ')
            fi
            
            if [ -n "$version" ] && echo "$version" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+'; then
                debug_debug "get_mysql_version" "found via DDEV container: $version"
                echo "$version"
                return 0
            fi
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
    local dockge_info=$(get_dockge_info)
    local dockge_status=$(get_dockge_status)
    if [ "$dockge_status" != "not_installed" ] && [ "$dockge_status" != "docker_not_available" ]; then
        if [ "$dockge_status" = "running" ]; then
            echo -e "   ${GREEN}✓${NC} ${CYAN}Dockge:${NC}         ${BLUE}(Container-Management)${NC}"
            echo -e "      ${YELLOW}${dockge_info}${NC}"
        else
            echo -e "   ${YELLOW}?${NC} ${CYAN}Dockge:${NC}         ${BLUE}(Container-Management)${NC}"
            echo -e "      ${YELLOW}${dockge_info}${NC}"
        fi
    else
        echo -e "   ${RED}✗${NC} ${CYAN}Dockge:${NC}"
        echo -e "      ${YELLOW}${dockge_info}${NC}"
    fi
    
    # DDEV
    local ddev_version=$(get_ddev_version)
    if [ "$ddev_version" != "not installed" ]; then
        local ddev_info="v${ddev_version}"
        # Prüfe ob DDEV läuft und füge URL hinzu
        if _is_ddev_running; then
            local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
            local ddev_dir="$tools_dir/woltlab-dev"
            if [ -d "$ddev_dir" ]; then
                cd "$ddev_dir" 2>/dev/null || true
                # Extrahiere URL aus ddev describe
                # Versuche zuerst JSON-Format, dann normale Ausgabe
                local ddev_url=$(ddev describe -j 2>/dev/null | grep -oE '"https?://[^"]+"' | head -1 | tr -d '"' || ddev describe 2>&1 | grep -iE 'https?://' | grep -oE 'https?://[^\s│]+' | head -1 | tr -d '│' | tr -d '\n' || echo "")
                cd - > /dev/null 2>&1 || true
                if [ -n "$ddev_url" ]; then
                    ddev_info="${ddev_info} (URL: ${ddev_url})"
                else
                    ddev_info="${ddev_info} (läuft)"
                fi
            else
                ddev_info="${ddev_info} (läuft)"
            fi
        fi
        echo -e "   ${GREEN}✓${NC} ${CYAN}DDEV:${NC}          ${YELLOW}${ddev_info}${NC} ${BLUE}(Entwicklungsumgebung)${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}DDEV:${NC}          ${YELLOW}nicht installiert${NC}"
    fi
    
    # WoltLab
    local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    local woltlab_version=$(get_woltlab_version "$tools_dir/woltlab-dev/public")
    if [ "$woltlab_version" != "unknown" ] && [ "$woltlab_version" != "not installed" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}WoltLab Suite:${NC} ${YELLOW}${woltlab_version}${NC} ${BLUE}(CMS/Framework)${NC}"
    else
        echo -e "   ${YELLOW}?${NC} ${CYAN}WoltLab Suite:${NC} ${YELLOW}nicht gefunden${NC} ${BLUE}(über Setup installieren)${NC}"
    fi
    
    # phpMyAdmin
    local phpmyadmin_version=$(get_phpmyadmin_version)
    if [ "$phpmyadmin_version" != "not installed" ]; then
        local pma_info="$phpmyadmin_version"
        # Wenn über DDEV verfügbar, füge URL hinzu
        if [ "$phpmyadmin_version" = "available via DDEV" ] || [ "$phpmyadmin_version" = "installed" ]; then
            if _is_ddev_running; then
                local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
                local ddev_dir="$tools_dir/woltlab-dev"
                if [ -d "$ddev_dir" ]; then
                    cd "$ddev_dir" 2>/dev/null || true
                    local ddev_url=$(ddev describe 2>/dev/null | grep -oE 'https?://[^\s]+' | head -1 || echo "")
                    cd - > /dev/null 2>&1 || true
                    if [ -n "$ddev_url" ]; then
                        pma_info="verfügbar (URL: ${ddev_url}/phpmyadmin)"
                    else
                        pma_info="verfügbar über DDEV"
                    fi
                else
                    pma_info="verfügbar über DDEV"
                fi
            else
                pma_info="verfügbar über DDEV (starte DDEV für URL)"
            fi
        fi
        echo -e "   ${GREEN}✓${NC} ${CYAN}phpMyAdmin:${NC}    ${YELLOW}${pma_info}${NC} ${BLUE}(Datenbank-Verwaltung)${NC}"
    else
        echo -e "   ${YELLOW}?${NC} ${CYAN}phpMyAdmin:${NC}    ${YELLOW}nicht gefunden${NC} ${BLUE}(optional, über DDEV verfügbar)${NC}"
    fi
    
    echo ""
    echo -e "${CYAN}System-Abhängigkeiten:${NC}"
    echo ""
    
    # PHP
    local php_version=$(get_php_version)
    if [ "$php_version" != "not installed" ] && [ "$php_version" != "available via DDEV" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}PHP:${NC}           ${YELLOW}${php_version}${NC} ${BLUE}(Programmiersprache)${NC}"
    elif [ "$php_version" = "available via DDEV" ]; then
        echo -e "   ${YELLOW}?${NC} ${CYAN}PHP:${NC}           ${YELLOW}verfügbar über DDEV${NC} ${BLUE}(starte DDEV für Version)${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}PHP:${NC}           ${YELLOW}nicht installiert${NC} ${BLUE}(wird über DDEV bereitgestellt)${NC}"
    fi
    
    # MySQL
    local mysql_version=$(get_mysql_version)
    if [ "$mysql_version" != "not installed" ] && [ "$mysql_version" != "available via DDEV" ]; then
        echo -e "   ${GREEN}✓${NC} ${CYAN}MySQL:${NC}         ${YELLOW}${mysql_version}${NC} ${BLUE}(Datenbank)${NC}"
    elif [ "$mysql_version" = "available via DDEV" ]; then
        echo -e "   ${YELLOW}?${NC} ${CYAN}MySQL:${NC}         ${YELLOW}verfügbar über DDEV${NC} ${BLUE}(starte DDEV für Version)${NC}"
    else
        echo -e "   ${RED}✗${NC} ${CYAN}MySQL:${NC}         ${YELLOW}nicht installiert${NC} ${BLUE}(wird über DDEV bereitgestellt)${NC}"
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

# Globale Variablen für fehlende Komponenten (für Installations-Menü)
MISSING_PHP=false
MISSING_MYSQL=false
MISSING_PHPMYADMIN=false
MYSQL_VERSION_TOO_OLD=false

# #region agent log
# Debug-Logging-Funktion
_debug_log() {
    local hypothesis_id="$1"
    local location="$2"
    local message="$3"
    local data="$4"
    local timestamp=$(date +%s%3N 2>/dev/null || date +%s000)
    local log_entry="{\"id\":\"log_${timestamp}_$$\",\"timestamp\":${timestamp},\"location\":\"${location}\",\"message\":\"${message}\",\"data\":${data},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"${hypothesis_id}\"}"
    echo "$log_entry" >> "/home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log" 2>/dev/null || true
}
# #endregion

# Funktion: Prüfe WoltLab Systemvoraussetzungen
check_woltlab_requirements() {
    debug_info "check_woltlab_requirements" "checking WoltLab system requirements"
    
    local issues=0
    local warnings=0
    
    # Reset globale Variablen
    MISSING_PHP=false
    MISSING_MYSQL=false
    MISSING_PHPMYADMIN=false
    MYSQL_VERSION_TOO_OLD=false
    
    print_list "WoltLab Systemvoraussetzungen"
    print_info "Basierend auf: https://manual.woltlab.com/de/requirements/"
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
            print_list_item "✓" "${CYAN}PHP:${NC} ${YELLOW}${php_version}${NC} ${GREEN}(>= 8.1.2)${NC}" "   "
        else
            print_list_item "✗" "${CYAN}PHP:${NC} ${YELLOW}${php_version}${NC} ${RED}(< 8.1.2 erforderlich)${NC}" "   "
            MISSING_PHP=true
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
                print_warning "PHP-Erweiterungen fehlen: ${missing_extensions[*]}"
                warnings=$((warnings + 1))
            fi
        fi
    elif [ "$php_version" = "available via DDEV" ]; then
        print_list_item "?" "${CYAN}PHP:${NC} ${YELLOW}verfügbar über DDEV (starte DDEV für Prüfung)${NC}" "   "
    else
        print_list_item "✗" "${CYAN}PHP:${NC} ${YELLOW}nicht installiert${NC}" "   "
        MISSING_PHP=true
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
            print_list_item "✓" "${CYAN}MySQL:${NC} ${YELLOW}${mysql_version}${NC} ${GREEN}(>= 8.0.30)${NC}" "   "
        elif echo "$mysql_version" | grep -qi "mariadb"; then
            # MariaDB Prüfung
            if [ "$mysql_major" -gt 10 ] || ([ "$mysql_major" -eq 10 ] && [ "$mysql_minor" -gt 5 ]) || ([ "$mysql_major" -eq 10 ] && [ "$mysql_minor" -eq 5 ] && [ "$mysql_patch" -ge 15 ]); then
                print_list_item "✓" "${CYAN}MariaDB:${NC} ${YELLOW}${mysql_version}${NC} ${GREEN}(>= 10.5.15)${NC}" "   "
            else
                print_list_item "✗" "${CYAN}MariaDB:${NC} ${YELLOW}${mysql_version}${NC} ${RED}(< 10.5.15 erforderlich)${NC}" "   "
                MYSQL_VERSION_TOO_OLD=true
                MISSING_MYSQL=true
                issues=$((issues + 1))
            fi
        else
            print_list_item "✗" "${CYAN}MySQL:${NC} ${YELLOW}${mysql_version}${NC} ${RED}(< 8.0.30 erforderlich)${NC}" "   "
            MYSQL_VERSION_TOO_OLD=true
            MISSING_MYSQL=true
            # #region agent log
            _debug_log "A" "common.sh:942" "Setting MySQL variables" "{\"mysql_version\":\"${mysql_version}\",\"MYSQL_VERSION_TOO_OLD\":\"${MYSQL_VERSION_TOO_OLD}\",\"MISSING_MYSQL\":\"${MISSING_MYSQL}\"}"
            # #endregion
            issues=$((issues + 1))
        fi
    elif [ "$mysql_version" = "available via DDEV" ]; then
        print_list_item "?" "${CYAN}MySQL:${NC} ${YELLOW}verfügbar über DDEV (starte DDEV für Prüfung)${NC}" "   "
    else
        print_list_item "✗" "${CYAN}MySQL:${NC} ${YELLOW}nicht installiert${NC}" "   "
        MISSING_MYSQL=true
        # #region agent log
        _debug_log "A" "common.sh:949" "Setting MISSING_MYSQL (not installed)" "{\"MISSING_MYSQL\":\"${MISSING_MYSQL}\"}"
        # #endregion
        issues=$((issues + 1))
    fi
    
    # phpMyAdmin prüfen
    local phpmyadmin_version=$(get_phpmyadmin_version)
    if [ "$phpmyadmin_version" = "not installed" ]; then
        print_list_item "✗" "${CYAN}phpMyAdmin:${NC} ${YELLOW}nicht installiert${NC}" "   "
        MISSING_PHPMYADMIN=true
        # #region agent log
        _debug_log "A" "common.sh:976" "Setting MISSING_PHPMYADMIN" "{\"phpmyadmin_version\":\"${phpmyadmin_version}\",\"MISSING_PHPMYADMIN\":\"${MISSING_PHPMYADMIN}\"}"
        # #endregion
        warnings=$((warnings + 1))
    elif [ "$phpmyadmin_version" = "available via DDEV" ] || [ "$phpmyadmin_version" = "installed" ] || [ -n "$phpmyadmin_version" ]; then
        print_list_item "✓" "${CYAN}phpMyAdmin:${NC} ${GREEN}verfügbar${NC}" "   "
    fi
    
    echo ""
    
    if [ $issues -eq 0 ] && [ $warnings -eq 0 ]; then
        print_success "Alle Systemvoraussetzungen erfüllt!"
        return 0
    elif [ $issues -eq 0 ]; then
        print_warning "Systemvoraussetzungen erfüllt, aber Warnungen vorhanden"
        return 0
    else
        print_error "Systemvoraussetzungen nicht vollständig erfüllt!"
        print_info "Dokumentation: https://manual.woltlab.com/de/requirements/"
        return 1
    fi
}

# Funktion: Prüfe auf verfügbare Updates (kompakt)
check_updates() {
    # #region agent log
    _debug_log "B" "common.sh:979" "check_updates ENTRY" "{\"function\":\"check_updates\"}"
    # #endregion
    
    debug_info "check_updates" "checking for available updates"
    
    # Kein print_section hier, um die Ausgabe nicht zu stören
    print_list "Update-Prüfung"
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
        print_success "Alle Tools sind auf dem neuesten Stand"
    else
        print_warning "${updates_found} Update(s) verfügbar"
    fi
    echo ""
    print_list "Weitere Updates"
    print_list_item "•" "WoltLab: Über ACP prüfen (${BLUE}https://manual.woltlab.com/de/installation/${NC})" "   "
    print_list_item "•" "Docker/Git: Über System-Package-Manager" "   "
    print_list_item "•" "phpMyAdmin: Über DDEV verfügbar (${BLUE}https://ddev.readthedocs.io/en/stable/users/quickstart/#phpmyadmin${NC})" "   "
    # Kein echo "" hier, damit das Menü direkt danach kommt
    
    # #region agent log
    _debug_log "B" "common.sh:1055" "check_updates EXIT" "{\"updates_found\":${updates_found}}"
    # #endregion
}

# Funktion: Installiere phpMyAdmin (aus setup.sh)
install_phpmyadmin_from_updates() {
    local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    local setup_script="$tools_dir/setup.sh"
    
    if [ -f "$setup_script" ]; then
        # Lade install_phpmyadmin Funktion aus setup.sh
        source "$setup_script" 2>/dev/null || true
        if type install_phpmyadmin &>/dev/null; then
            install_phpmyadmin
            return $?
        fi
    fi
    
    # Fallback: Direkte Installation
    local ddev_dir="$tools_dir/woltlab-dev"
    if [ ! -d "$ddev_dir/.ddev" ]; then
        print_error "DDEV-Projekt ist noch nicht initialisiert"
        print_info "Initialisiere zuerst DDEV, dann kann phpMyAdmin installiert werden"
        return 1
    fi
    
    cd "$ddev_dir" 2>/dev/null || {
        print_error "Konnte nicht ins DDEV-Verzeichnis wechseln: $ddev_dir"
        return 1
    }
    
    print_info "Installiere phpMyAdmin über DDEV..."
    if ddev add-on get ddev/ddev-phpmyadmin 2>/dev/null; then
        print_success "phpMyAdmin Add-on installiert"
        print_info "Starte DDEV neu, damit phpMyAdmin verfügbar wird..."
        if ddev restart 2>/dev/null; then
            print_success "DDEV neu gestartet - phpMyAdmin ist jetzt verfügbar"
            print_info "phpMyAdmin URL: https://woltlab.ddev.site/phpmyadmin"
            cd - > /dev/null 2>&1 || true
            return 0
        fi
    fi
    
    print_error "phpMyAdmin Installation fehlgeschlagen"
    cd - > /dev/null 2>&1 || true
    return 1
}

# Funktion: Zeige Update-Check (für Menü)
show_update_check() {
    # #region agent log
    _debug_log "D" "common.sh:1113" "show_update_check ENTRY" "{\"function\":\"show_update_check\"}"
    # #endregion
    
    # set -e temporär deaktivieren, damit Fehler in check_woltlab_requirements oder check_updates das Menü nicht verhindern
    set +e
    
    # Prüfe Systemvoraussetzungen (setzt globale Variablen)
    # #region agent log
    _debug_log "D" "common.sh:1121" "Before check_woltlab_requirements" "{\"about_to_call_check_woltlab_requirements\":true}"
    # #endregion
    check_woltlab_requirements
    local check_requirements_exit=$?
    
    # #region agent log
    _debug_log "A" "common.sh:1126" "After check_woltlab_requirements" "{\"check_requirements_exit\":${check_requirements_exit},\"MISSING_PHP\":\"${MISSING_PHP}\",\"MISSING_MYSQL\":\"${MISSING_MYSQL}\",\"MYSQL_VERSION_TOO_OLD\":\"${MYSQL_VERSION_TOO_OLD}\",\"MISSING_PHPMYADMIN\":\"${MISSING_PHPMYADMIN}\"}"
    # #endregion
    
    echo ""
    
    # Prüfe Updates (nur Info, stört nicht)
    # #region agent log
    _debug_log "B" "common.sh:1134" "Before check_updates" "{\"about_to_call_check_updates\":true}"
    # #endregion
    check_updates
    local check_updates_exit=$?
    
    # set -e wieder aktivieren
    set -e
    
    # #region agent log
    _debug_log "B" "common.sh:1142" "After check_updates" "{\"check_updates_exit\":${check_updates_exit}}"
    # #endregion
    
    # #region agent log
    _debug_log "B" "common.sh:1097" "After check_updates" "{\"check_updates_exit\":${check_updates_exit},\"MISSING_MYSQL\":\"${MISSING_MYSQL}\",\"MYSQL_VERSION_TOO_OLD\":\"${MYSQL_VERSION_TOO_OLD}\",\"MISSING_PHPMYADMIN\":\"${MISSING_PHPMYADMIN}\"}"
    # #endregion
    
    # Installations-Menü IMMER anzeigen (für Navigation und Installation)
    # Stelle sicher, dass das Menü immer angezeigt wird
    echo ""
    echo ""
    
    # #region agent log
    _debug_log "D" "common.sh:1105" "Before print_list menu" "{\"about_to_print_menu\":true}"
    # #endregion
    
    print_list "Verfügbare Aktionen"
    echo ""
    
    local option_num=1
    local options=()
    
    # #region agent log
    _debug_log "C" "common.sh:1113" "Before condition checks" "{\"MISSING_PHP\":\"${MISSING_PHP}\",\"MISSING_MYSQL\":\"${MISSING_MYSQL}\",\"MYSQL_VERSION_TOO_OLD\":\"${MYSQL_VERSION_TOO_OLD}\",\"MISSING_PHPMYADMIN\":\"${MISSING_PHPMYADMIN}\",\"option_num\":${option_num}}"
    # #endregion
    
    # Installations-Optionen nur anzeigen, wenn Komponenten fehlen
    # Verwende explizite String-Vergleiche für Sicherheit
    if [ "${MISSING_PHP}" = "true" ]; then
        print_list_item "${option_num})" "PHP installieren/aktualisieren" "   "
        options+=("php")
        option_num=$((option_num + 1))
    fi
    
    if [ "${MISSING_MYSQL}" = "true" ] || [ "${MYSQL_VERSION_TOO_OLD}" = "true" ]; then
        # #region agent log
        _debug_log "C" "common.sh:1120" "MySQL condition TRUE" "{\"MISSING_MYSQL\":\"${MISSING_MYSQL}\",\"MYSQL_VERSION_TOO_OLD\":\"${MYSQL_VERSION_TOO_OLD}\",\"option_num\":${option_num}}"
        # #endregion
        print_list_item "${option_num})" "MySQL/MariaDB installieren/aktualisieren" "   "
        options+=("mysql")
        option_num=$((option_num + 1))
    else
        # #region agent log
        _debug_log "C" "common.sh:1126" "MySQL condition FALSE" "{\"MISSING_MYSQL\":\"${MISSING_MYSQL}\",\"MYSQL_VERSION_TOO_OLD\":\"${MYSQL_VERSION_TOO_OLD}\"}"
        # #endregion
    fi
    
    if [ "${MISSING_PHPMYADMIN}" = "true" ]; then
        # #region agent log
        _debug_log "C" "common.sh:1131" "phpMyAdmin condition TRUE" "{\"MISSING_PHPMYADMIN\":\"${MISSING_PHPMYADMIN}\",\"option_num\":${option_num}}"
        # #endregion
        print_list_item "${option_num})" "phpMyAdmin installieren (über DDEV)" "   "
        options+=("phpmyadmin")
        option_num=$((option_num + 1))
    else
        # #region agent log
        _debug_log "C" "common.sh:1137" "phpMyAdmin condition FALSE" "{\"MISSING_PHPMYADMIN\":\"${MISSING_PHPMYADMIN}\"}"
        # #endregion
    fi
    
    # Info-Option immer anzeigen (wenn andere Optionen vorhanden sind)
    if [ $option_num -gt 1 ]; then
        print_list_item "${option_num})" "Installations-Anleitung anzeigen" "   "
        options+=("info")
        option_num=$((option_num + 1))
    fi
    
    # Wenn keine Installations-Optionen vorhanden sind, zeige nur Info und Zurück
    if [ $option_num -eq 1 ]; then
        print_list_item "${option_num})" "Installations-Anleitung anzeigen" "   "
        options+=("info")
        option_num=$((option_num + 1))
    fi
    
    # Zurück-Option immer anzeigen
    print_list_item "0)" "Zurück zum Hauptmenü" "   "
    echo ""
    
    # #region agent log
    _debug_log "D" "common.sh:1157" "Before read prompt" "{\"option_num\":${option_num},\"max_option\":$((option_num - 1)),\"options_count\":${#options[@]}}"
    # #endregion
    
    local max_option=$((option_num - 1))
    read -p "Wähle eine Option (0-${max_option}): " install_choice
    echo ""
    
    if [ "$install_choice" = "0" ]; then
        return 0
    fi
    
    local selected_option="${options[$((install_choice - 1))]}"
    
    case "$selected_option" in
        php)
            print_section "PHP Installation/Update" "Hauptmenü" "Updates" "Installation"
            print_list "Installations-Optionen"
            print_list_item "1." "Über DDEV (empfohlen für Entwicklung)" "   "
            print_info "   → PHP wird automatisch mit DDEV installiert"
            print_info "   → Starte DDEV: ./tools/woltlab-dev/start.sh"
            print_info "   → PHP-Version in .ddev/config.yaml konfigurieren"
            echo ""
            print_list_item "2." "System-Installation" "   "
            print_info "   → Nutze: ./tools/setup.sh"
            print_info "   → Oder installiere manuell über Package-Manager"
            echo ""
            print_list "DDEV PHP-Version ändern"
            print_info "1. Öffne: tools/woltlab-dev/.ddev/config.yaml"
            print_info "2. Ändere: php_version: \"8.3\" (oder gewünschte Version)"
            print_info "3. Starte DDEV neu: ddev restart"
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            # #region agent log
            _debug_log "D" "common.sh:1242" "Recursive call to show_update_check (php)" "{\"recursive\":true,\"from\":\"php\"}"
            # #endregion
            show_update_check
            # #region agent log
            _debug_log "D" "common.sh:1245" "After recursive show_update_check (php)" "{\"recursive\":true,\"from\":\"php\"}"
            # #endregion
            ;;
        mysql)
            print_section "MySQL/MariaDB Installation/Update" "Hauptmenü" "Updates" "Installation"
            print_list "Installations-Optionen"
            print_list_item "1." "Über DDEV (empfohlen für Entwicklung)" "   "
            print_info "   → MySQL wird automatisch mit DDEV installiert"
            print_info "   → Starte DDEV: ./tools/woltlab-dev/start.sh"
            print_info "   → MySQL-Version in .ddev/config.yaml konfigurieren"
            echo ""
            print_list_item "2." "System-Installation" "   "
            print_info "   → Nutze: ./tools/setup.sh"
            print_info "   → Oder installiere manuell über Package-Manager"
            echo ""
            print_list "DDEV MySQL-Version ändern"
            print_info "1. Öffne: tools/woltlab-dev/.ddev/config.yaml"
            print_info "2. Ändere: mariadb_version: \"10.11\" (oder mysql_version: \"8.0\")"
            print_info "3. Starte DDEV neu: ddev restart"
            echo ""
            print_warning "WoltLab benötigt MySQL >= 8.0.30 oder MariaDB >= 10.5.15"
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            # #region agent log
            _debug_log "D" "common.sh:1264" "Recursive call to show_update_check (mysql)" "{\"recursive\":true,\"from\":\"mysql\"}"
            # #endregion
            show_update_check
            # #region agent log
            _debug_log "D" "common.sh:1267" "After recursive show_update_check (mysql)" "{\"recursive\":true,\"from\":\"mysql\"}"
            # #endregion
            ;;
        phpmyadmin)
            print_section "phpMyAdmin Installation" "Hauptmenü" "Updates" "Installation"
            print_info "phpMyAdmin wird als DDEV Add-on installiert"
            print_info "Voraussetzungen:"
            print_list_item "•" "DDEV muss installiert sein" "   "
            print_list_item "•" "DDEV-Projekt muss initialisiert sein" "   "
            echo ""
            if install_phpmyadmin_from_updates; then
                print_success "phpMyAdmin erfolgreich installiert!"
                print_info "Zugriff: https://woltlab.ddev.site/phpmyadmin"
                print_info "Benutzer: db"
                print_info "Passwort: db"
            else
                print_error "phpMyAdmin Installation fehlgeschlagen"
                print_info "Prüfe ob DDEV installiert und initialisiert ist"
            fi
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            # #region agent log
            _debug_log "D" "common.sh:1284" "Recursive call to show_update_check (phpmyadmin)" "{\"recursive\":true,\"from\":\"phpmyadmin\"}"
            # #endregion
            show_update_check
            # #region agent log
            _debug_log "D" "common.sh:1287" "After recursive show_update_check (phpmyadmin)" "{\"recursive\":true,\"from\":\"phpmyadmin\"}"
            # #endregion
            ;;
        info)
            print_section "Installations-Anleitung" "Hauptmenü" "Updates" "Info"
            print_list "Allgemeine Informationen"
            print_info "Die meisten Komponenten werden über DDEV verwaltet"
            print_info "DDEV ist eine Container-basierte Entwicklungsumgebung"
            echo ""
            print_list "Schritt-für-Schritt Anleitung"
            print_list_item "1." "DDEV installieren" "   "
            print_info "   → Nutze: ./tools/setup.sh"
            print_info "   → Oder: https://ddev.readthedocs.io/en/stable/users/install/"
            echo ""
            print_list_item "2." "DDEV-Projekt initialisieren" "   "
            print_info "   → cd tools/woltlab-dev"
            print_info "   → ddev config --project-type=php"
            echo ""
            print_list_item "3." "PHP/MySQL-Version konfigurieren" "   "
            print_info "   → Bearbeite: .ddev/config.yaml"
            print_info "   → Setze: php_version: \"8.3\""
            print_info "   → Setze: mariadb_version: \"10.11\" (oder mysql_version: \"8.0\")"
            echo ""
            print_list_item "4." "DDEV starten" "   "
            print_info "   → ddev start"
            print_info "   → Oder: ./tools/woltlab-dev/start.sh"
            echo ""
            print_list_item "5." "phpMyAdmin installieren (optional)" "   "
            print_info "   → Wähle Option 3 im Updates-Menü"
            print_info "   → Oder: ddev add-on get ddev/ddev-phpmyadmin"
            echo ""
            print_list "Weitere Hilfe"
            print_info "DDEV-Dokumentation: https://ddev.readthedocs.io/"
            print_info "WoltLab-Anforderungen: https://manual.woltlab.com/de/requirements/"
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            # #region agent log
            _debug_log "D" "common.sh:1319" "Recursive call to show_update_check (info)" "{\"recursive\":true,\"from\":\"info\"}"
            # #endregion
            show_update_check
            # #region agent log
            _debug_log "D" "common.sh:1322" "After recursive show_update_check (info)" "{\"recursive\":true,\"from\":\"info\"}"
            # #endregion
            ;;
        *)
            if [ -n "$install_choice" ]; then
                print_warning "Ungültige Option: $install_choice"
                sleep 1
            fi
            # #region agent log
            _debug_log "D" "common.sh:1329" "Recursive call to show_update_check (default)" "{\"recursive\":true,\"from\":\"default\"}"
            # #endregion
            show_update_check
            # #region agent log
            _debug_log "D" "common.sh:1332" "After recursive show_update_check (default)" "{\"recursive\":true,\"from\":\"default\"}"
            # #endregion
            ;;
    esac
    
    # #region agent log
    _debug_log "D" "common.sh:1336" "show_update_check EXIT" "{\"function\":\"show_update_check\"}"
    # #endregion
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

# Funktion: Breadcrumb-Navigation anzeigen
print_breadcrumb() {
    local breadcrumbs=("$@")
    if [ ${#breadcrumbs[@]} -eq 0 ]; then
        return 0
    fi
    
    echo -e "${BLUE}Navigation:${NC} "
    local first=true
    for crumb in "${breadcrumbs[@]}"; do
        if [ "$first" = true ]; then
            echo -e -n "${CYAN}${crumb}${NC}"
            first=false
        else
            echo -e -n " ${YELLOW}>${NC} ${CYAN}${crumb}${NC}"
        fi
    done
    echo ""
    echo ""
}

# Funktion: Sektion-Header (mit optionaler Breadcrumb)
print_section() {
    local title="$1"
    shift
    local breadcrumbs=("$@")
    
    # Zeige Breadcrumb falls vorhanden
    if [ ${#breadcrumbs[@]} -gt 0 ]; then
        print_breadcrumb "${breadcrumbs[@]}"
    fi
    
    echo -e "${CYAN}==========================================${NC}"
    echo -e "${CYAN}${title}${NC}"
    echo -e "${CYAN}==========================================${NC}"
    echo ""
    debug_log "print_section" "title=$title"
}

# Funktion: Listen-Item formatieren
print_list_item() {
    local prefix="${1:-•}"
    local text="$2"
    local indent="${3:-2}"
    local spaces=""
    
    # Konvertiere indent zu Zahl (falls String mit Leerzeichen)
    if [[ "$indent" =~ ^[0-9]+$ ]]; then
        local indent_num=$indent
    else
        # Zähle Leerzeichen im String
        local indent_num=$(echo -n "$indent" | wc -c)
    fi
    
    for ((i=0; i<indent_num; i++)); do
        spaces="${spaces} "
    done
    echo -e "${spaces}${CYAN}${prefix}${NC} ${text}"
}

# Funktion: Liste formatieren
print_list() {
    local title="${1:-}"
    local indent="${2:-2}"
    
    if [ -n "$title" ]; then
        echo -e "${CYAN}${title}:${NC}"
        echo ""
    fi
}

# Funktion: Einheitliche Ja/Nein-Abfrage (J/N)
ask_yes_no() {
    local question="$1"
    local default="${2:-N}"
    local answer=""

    while true; do
        read -r -p "$(echo -e "${YELLOW}${question}${NC} [J/N]: ")" answer
        if [ -z "$answer" ]; then
            answer="$default"
        fi
        case "$answer" in
            j|J|y|Y) return 0 ;;
            n|N) return 1 ;;
            *) print_warning "Bitte J oder N eingeben." ;;
        esac
    done
}

# Funktion: Navigation anzeigen (Vor/Zurück)
print_navigation() {
    local back_text="${1:-Zurück}"
    local back_action="${2:-}"
    local forward_text="${3:-}"
    local forward_action="${4:-}"
    
    echo ""
    echo -e "${BLUE}Navigation:${NC}"
    if [ -n "$back_action" ]; then
        echo -e "  ${CYAN}←${NC} ${back_text} (${back_action})"
    fi
    if [ -n "$forward_action" ]; then
        echo -e "  ${CYAN}→${NC} ${forward_text} (${forward_action})"
    fi
    echo ""
}

# Funktion: Menü-Location anzeigen
show_menu_location() {
    local menu_name="$1"
    local breadcrumbs=("$@")
    shift
    
    echo -e "${BLUE}Aktuelles Menü:${NC} ${CYAN}${menu_name}${NC}"
    if [ ${#breadcrumbs[@]} -gt 1 ]; then
        echo -e "${BLUE}Pfad:${NC} "
        local first=true
        for crumb in "${breadcrumbs[@]}"; do
            if [ "$first" = true ]; then
                echo -e -n "${CYAN}${crumb}${NC}"
                first=false
            else
                echo -e -n " ${YELLOW}>${NC} ${CYAN}${crumb}${NC}"
            fi
        done
        echo ""
    fi
    echo ""
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

# Diese Funktionen werden nicht mehr benötigt, da phpMyAdmin über DDEV bereitgestellt wird
# und keine separate Konfiguration benötigt.
