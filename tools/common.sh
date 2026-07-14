#!/usr/bin/env bash

#################################################################
# WoltLab Development Tools - Gemeinsame Funktionen
# Zentrale Funktionen für alle Tools (Farben, Formatierung, etc.)
# 
# Kompatibilität: Linux, macOS, Windows (WSL2, Git Bash / MSYS)
#################################################################

#=====================================
# KONFIGURATION (Plattform, Log, Farben)
#=====================================

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
    elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]]; then
        platform="msys"
    elif grep -qi microsoft /proc/version 2>/dev/null; then
        platform="wsl"
    fi
    
    echo "$platform"
}

# Setze Plattform-Variable
PLATFORM=$(detect_platform)

# Anzeigename für Logs und README
platform_label() {
    case "$PLATFORM" in
        linux) echo "Linux" ;;
        macos) echo "macOS" ;;
        wsl) echo "Windows (WSL2)" ;;
        msys|cygwin) echo "Windows (Git Bash)" ;;
        *) echo "unknown ($OSTYPE)" ;;
    esac
}

# Pflicht-Tools für alle Skripte (Cross-Platform)
check_swpm_requirements() {
    local missing=0
    for cmd in bash git tar; do
        if ! command -v "$cmd" &>/dev/null; then
            print_error "Erforderlich, fehlt im PATH: $cmd"
            missing=1
        fi
    done
    if ! command -v python3 &>/dev/null; then
        print_warning "python3 empfohlen (Validierung, Sprach-Checks)"
    fi
    if [ "$missing" -ne 0 ]; then
        echo ""
        echo "Plattform: $(platform_label)"
        echo "Unterstützt: Linux, macOS, Windows via WSL2 oder Git Bash."
        echo "Details: tools/docs/CROSS-PLATFORM.md"
        return 1
    fi
    return 0
}

# Funktion: Prüft ob ein Port (TCP) bereits belegt ist (LISTEN)
# Aufruf: is_port_free <port>; Rückgabe: 0 = frei, 1 = belegt
# Nutzt ss (Linux/WSL) oder netstat (macOS/Windows Fallback)
is_port_free() {
    local port="${1:-}"
    [ -z "$port" ] || [ "$port" -lt 1 ] 2>/dev/null && return 1
    if command -v ss &>/dev/null; then
        ! ss -tln 2>/dev/null | grep -qE ":${port}[[:space:]]"
        return $?
    fi
    if command -v netstat &>/dev/null; then
        ! netstat -tln 2>/dev/null | grep -qE "\.${port}[[:space:]]|:${port}[[:space:]]"
        return $?
    fi
    return 0
}

# Funktion: Nächsten freien Port ab start_port finden
# Aufruf: get_free_port <start_port> [max_tries]; Ausgabe: Port-Nummer
get_free_port() {
    local start="${1:-8080}"
    local max_tries="${2:-100}"
    local p="$start"
    local n=0
    while [ $n -lt "$max_tries" ]; do
        if is_port_free "$p"; then
            echo "$p"
            return 0
        fi
        p=$((p + 1))
        n=$((n + 1))
    done
    echo "$start"
    return 1
}

# Funktion: Architektur erkennen (für Runtime-Unterordner: linux-x64, macos-arm64, etc.)
detect_arch() {
    local arch="x64"
    local uname_m
    uname_m="$(uname -m 2>/dev/null || echo "")"
    case "$uname_m" in
        x86_64|amd64) arch="x64" ;;
        aarch64|arm64|armv8*) arch="arm64" ;;
        i386|i686) arch="x86" ;;
        *) arch="x64" ;;
    esac
    echo "$arch"
}

# Funktion: WoltLab test.php – PHP-Version-Grenzen auslesen (phpVersionLowerBound, phpVersionUpperBound)
# Aufruf: get_woltlab_php_bounds <verzeichnis_mit_test.php>
# Setzt WOLTLAB_PHP_LOWER und WOLTLAB_PHP_UPPER; gibt 0 zurück bei Erfolg, 1 wenn test.php fehlt/ungültig
get_woltlab_php_bounds() {
    local dir="${1:-}"
    local test_php=""
    if [ -n "$dir" ]; then
        [ -f "$dir/test.php" ] && test_php="$dir/test.php"
    fi
    if [ -z "$test_php" ] && [ -n "${MAIN_DIR:-}" ]; then
        [ -f "$MAIN_DIR/woltlab-core/test.php" ] && test_php="$MAIN_DIR/woltlab-core/test.php"
    fi
    if [ -z "$test_php" ] || [ ! -f "$test_php" ]; then
        WOLTLAB_PHP_LOWER=""
        WOLTLAB_PHP_UPPER=""
        return 1
    fi
    WOLTLAB_PHP_LOWER=""
    WOLTLAB_PHP_UPPER=""
    WOLTLAB_PHP_LOWER=$(grep -E "^\s*\\\$phpVersionLowerBound\s*=" "$test_php" 2>/dev/null | sed -n "s/.*['\"]\\([^'\"]*\\)['\"].*/\\1/p" | head -1)
    WOLTLAB_PHP_UPPER=$(grep -E "^\s*\\\$phpVersionUpperBound\s*=" "$test_php" 2>/dev/null | sed -n "s/.*['\"]\\([^'\"]*\\)['\"].*/\\1/p" | head -1)
    if [ -z "$WOLTLAB_PHP_UPPER" ]; then
        return 1
    fi
    return 0
}

# Funktion: Maximale PHP-Version für Download (phpVersionUpperBound; „8.3.x“ → „8.3“, für Abruf neuestes 8.3.x)
# Aufruf: get_woltlab_php_version_max [<verzeichnis_mit_test.php>]
# Ausgabe: z.B. 8.3 (Minor-Linie für Download-URLs)
get_woltlab_php_version_max() {
    local dir="${1:-}"
    get_woltlab_php_bounds "$dir" || return 1
    local upper="${WOLTLAB_PHP_UPPER:-}"
    if [ -z "$upper" ]; then
        echo ""
        return 1
    fi
    # „8.3.x“ → 8.3; „8.1.2“ → 8.1
    echo "$upper" | sed -n 's/^\([0-9]*\.[0-9]*\).*/\1/p'
}

# Funktion: Prüft ob portable Runtimes (tools/runtime/) genutzt werden sollen
# Gibt 0 zurück wenn PHP- und MariaDB-Runtime unter tools/runtime/ vorhanden sind
is_portable_runtime() {
    local tools_dir="${1:-}"
    [ -z "$tools_dir" ] && tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    local runtime_dir="$tools_dir/runtime"
    local key
    key="$(get_runtime_platform_key)"
    [ -d "$runtime_dir/php/$key" ] && [ -d "$runtime_dir/mariadb/$key" ] && return 0
    return 1
}

# Funktion: Runtime-Plattform-Key (z.B. linux-x64, macos-arm64, win-x64) für tools/runtime/<component>/<key>/
get_runtime_platform_key() {
    local platform
    platform="$(detect_platform)"
    local arch
    arch="$(detect_arch)"
    case "$platform" in
        linux|wsl) [ "$arch" = "arm64" ] && echo "linux-arm64" || echo "linux-x64" ;;
        macos)     [ "$arch" = "arm64" ] && echo "macos-arm64" || echo "macos-x64" ;;
        msys|cygwin) echo "win-x64" ;;
        *)         echo "linux-x64" ;;
    esac
}

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

# Farben und Symbole (TTY-sicher via ui.sh)
_UI_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$_UI_DIR/ui.sh" ]; then
    source "$_UI_DIR/ui.sh"
else
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'
    CYAN='\033[0;36m'; MAGENTA='\033[0;35m'; BOLD='\033[1m'; DIM='\033[2m'; RESET='\033[0m'; NC='\033[0m'
    CHECK="✓"; CROSS="✗"; ARROW="→"; WARNING="⚠"; INFO="ℹ"
fi

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

# tools/.env für env_get/env_get (von setup-minimal.sh und tools.sh genutzt)
_WOLTLAB_ENV_FILE="${ENV_FILE:-${TOOLS_DIR:-$_TOOLS_DIR_FOR_LOG}/.env}"
_WOLTLAB_MAIN_DIR="${MAIN_DIR:-$(dirname "${TOOLS_DIR:-$_TOOLS_DIR_FOR_LOG}")}"

env_get() {
    local key="$1"
    grep -E "^${key}=" "$_WOLTLAB_ENV_FILE" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r\n' || true
}

env_set() {
    local key="$1"
    local val="$2"
    [ -f "$_WOLTLAB_ENV_FILE" ] || touch "$_WOLTLAB_ENV_FILE"
    if grep -qE "^${key}=" "$_WOLTLAB_ENV_FILE" 2>/dev/null; then
        grep -vE "^${key}=" "$_WOLTLAB_ENV_FILE" > "${_WOLTLAB_ENV_FILE}.tmp"
        echo "${key}=${val}" >> "${_WOLTLAB_ENV_FILE}.tmp"
        mv "${_WOLTLAB_ENV_FILE}.tmp" "$_WOLTLAB_ENV_FILE"
    else
        echo "${key}=${val}" >> "$_WOLTLAB_ENV_FILE"
    fi
}

# Sprachwahl für Tools (DE/EN): WOLTLAB_LANG aus .env oder aus LANG/LC_* ableiten
# Verwendung: spätere i18n (tr/msg) und Menü „Sprache wechseln“
if [ -z "${WOLTLAB_LANG:-}" ]; then
    WOLTLAB_LANG="$(env_get WOLTLAB_LANG 2>/dev/null)" || true
fi
if [ -z "${WOLTLAB_LANG:-}" ]; then
    _woltlab_detect_lang="${LANG:-${LC_ALL:-${LC_MESSAGES:-}}}"
    if [[ "${_woltlab_detect_lang,,}" == de* ]]; then
        WOLTLAB_LANG="de"
    else
        WOLTLAB_LANG="en"
    fi
fi
export WOLTLAB_LANG

# Übersetzung für Zweisprachigkeit (DE/EN): swpm_tr "key" liest tools/language/${WOLTLAB_LANG}.txt
# Wenn Schlüssel oder Datei fehlt, wird der Schlüssel zurückgegeben.
swpm_tr() {
    local key="${1:-}"
    local lang="${WOLTLAB_LANG:-en}"
    local file="$_TOOLS_DIR_FOR_LOG/language/${lang}.txt"
    if [ -n "$key" ] && [ -f "$file" ]; then
        local key_escaped val
        key_escaped="$(printf '%s' "$key" | sed 's/\./\\./g')"
        val="$(grep -E "^${key_escaped}=" "$file" 2>/dev/null | cut -d= -f2- | head -1)"
        if [ -n "$val" ]; then
            echo "$val"
            return 0
        fi
    fi
    echo "$key"
}

# Prüft, ob Setup bereits vollständig durchgeführt wurde.
# Gültig: env_created + mindestens eines von ddev_ready, deps_ready, portable_runtimes_ready, own_server_configured.
# own_server_configured = User nutzt eigenen Server (nur .env), keine DDEV/Docker-Pflicht.
check_setup_complete() {
    local sf="${STATE_FILE:-$_TOOLS_DIR_FOR_LOG/.woltlab-setup-state}"
    [ -f "$sf" ] || return 1
    [ -n "$(grep -E "^env_created=" "$sf" 2>/dev/null | cut -d= -f2-)" ] || return 1
    grep -qE "^(woltlab_core_downloaded|ddev_ready|deps_ready|portable_runtimes_ready|own_server_configured)=" "$sf" 2>/dev/null || return 1
    return 0
}

# Gibt das WoltLab-Public-Verzeichnis zurück (für Snapshot/Restore).
# Liest WOLTLAB_PUBLIC_DIR aus .env; wenn leer, Default: tools/woltlab-dev/public.
# Aufruf: get_public_dir [tools_dir]
get_public_dir() {
    local tools_dir="${1:-$_TOOLS_DIR_FOR_LOG}"
    [ -z "$tools_dir" ] && return 1
    local env_file="$tools_dir/.env"
    local default_public="$tools_dir/woltlab-dev/public"
    if [ -f "$env_file" ]; then
        local val
        val="$(grep -E '^WOLTLAB_PUBLIC_DIR=' "$env_file" 2>/dev/null | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")"
        if [ -n "$val" ]; then
            case "$val" in
                /*) echo "$val"; return 0 ;;
                *)  echo "$(cd "$tools_dir/.." 2>/dev/null && pwd)/$val" 2>/dev/null || echo "$tools_dir/$val"; return 0 ;;
            esac
        fi
    fi
    echo "$default_public"
}

# Validierung: WoltLab Core Download (WCFSetup.tar.gz + install.php/test.php)
# Aufruf: verify_woltlab_download <main_dir> [quiet]
# Rückgabe: 0 = ok, 1 = Fehler
verify_woltlab_download() {
    local main_dir="${1:-}"
    local quiet="${2:-}"
    local core_dir="${main_dir}/woltlab-core"
    local tarball="${core_dir}/WCFSetup.tar.gz"
    if [ -z "$main_dir" ] || [ ! -f "$tarball" ] || ! [ -s "$tarball" ]; then
        [ -z "$quiet" ] && print_error "Validierung fehlgeschlagen: WCFSetup.tar.gz fehlt oder ist leer in ${core_dir}"
        return 1
    fi
    if [ ! -f "${core_dir}/install.php" ] && [ ! -f "${core_dir}/test.php" ]; then
        [ -z "$quiet" ] && print_error "Validierung fehlgeschlagen: install.php/test.php nicht gefunden in ${core_dir}"
        return 1
    fi
    local size_mb
    size_mb="$(stat -c%s "$tarball" 2>/dev/null || stat -f%z "$tarball" 2>/dev/null)" || size_mb=0
    size_mb="$(( size_mb / 1048576 ))"
    [ -z "$quiet" ] && print_success "WoltLab Core: WCFSetup.tar.gz (${size_mb} MB), install.php/test.php vorhanden"
    return 0
}

# Validierung: Portable Runtimes (PHP + MariaDB Binary)
# Aufruf: verify_runtimes <tools_dir> [quiet]
verify_runtimes() {
    local tools_dir="${1:-}"
    local quiet="${2:-}"
    local runtime_dir="${tools_dir}/runtime"
    local key
    key="$(get_runtime_platform_key)"
    local php_bin="${runtime_dir}/php/${key}/bin/php"
    local php_exe="${runtime_dir}/php/${key}/bin/php.exe"
    local mariadb_bin="${runtime_dir}/mariadb/${key}/bin/mariadbd"
    local mysql_bin="${runtime_dir}/mariadb/${key}/bin/mysqld"
    local ok=0
    if [ -x "$php_bin" ] || [ -x "$php_exe" ]; then
        :
    else
        [ -z "$quiet" ] && print_error "Validierung Runtimes: PHP-Binary fehlt (${runtime_dir}/php/${key}/bin/)"
        ok=1
    fi
    if [ -x "$mariadb_bin" ] || [ -x "$mysql_bin" ]; then
        :
    else
        [ -z "$quiet" ] && print_error "Validierung Runtimes: MariaDB-Binary fehlt (${runtime_dir}/mariadb/${key}/bin/)"
        ok=1
    fi
    [ "$ok" -eq 0 ] && [ -z "$quiet" ] && print_success "Runtimes: PHP und MariaDB vorhanden (${key})"
    return $ok
}

# Validierung: .env mit DB_PORT und HTTP_PORT
# Aufruf: verify_env_ports <env_file> [quiet]; setzt optional DB_PORT und HTTP_PORT als gesetzt für Aufrufer
# Rückgabe: 0 = ok, 1 = Fehler
verify_env_ports() {
    local env_file="${1:-}"
    local quiet="${2:-}"
    if [ -z "$env_file" ] || [ ! -f "$env_file" ]; then
        [ -z "$quiet" ] && print_error "Validierung .env: Datei fehlt (${env_file})"
        return 1
    fi
    local db_port http_port
    db_port="$(grep -E '^DB_PORT=' "$env_file" 2>/dev/null | cut -d= -f2-)"
    http_port="$(grep -E '^HTTP_PORT=' "$env_file" 2>/dev/null | cut -d= -f2-)"
    if [ -z "$db_port" ] || [ -z "$http_port" ]; then
        [ -z "$quiet" ] && print_error "Validierung .env: DB_PORT oder HTTP_PORT fehlt in ${env_file}"
        return 1
    fi
    [ -z "$quiet" ] && print_success ".env: DB_PORT=${db_port} HTTP_PORT=${http_port}"
    export DB_PORT="$db_port" HTTP_PORT="$http_port"
    return 0
}

# Prüft ob ein Port (TCP) erreichbar ist (Verbindungsaufbau)
# Aufruf: check_port_reachable <host> <port>; Rückgabe: 0 = erreichbar
check_port_reachable() {
    local host="${1:-127.0.0.1}"
    local port="${2:-}"
    [ -z "$port" ] || [ "$port" -lt 1 ] 2>/dev/null && return 1
    if command -v timeout &>/dev/null; then
        timeout 2 bash -c "echo >/dev/tcp/$host/$port" 2>/dev/null && return 0
    fi
    if command -v nc &>/dev/null; then
        nc -z "$host" "$port" 2>/dev/null && return 0
    fi
    return 1
}

# Prüft ob eine URL per HTTP erreichbar ist (curl)
# Aufruf: check_http_reachable <url>; Rückgabe: 0 = 2xx/3xx
check_http_reachable() {
    local url="${1:-}"
    [ -z "$url" ] && return 1
    local code
    code="$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 3 "$url" 2>/dev/null)" || return 1
    case "$code" in
        2*) return 0 ;;
        3*) return 0 ;;
        *) return 1 ;;
    esac
}

# Verifizierung: System-Deps (PHP-Version aus test.php, MariaDB erreichbar, optional Node)
# Aufruf: verify_deps <main_dir> <env_file> [quiet]
# Liest DB_PORT, DB_USER, DB_PASSWORD aus env_file; prüft PHP-Version gegen woltlab-core/test.php
verify_deps() {
    local main_dir="${1:-}"
    local env_file="${2:-}"
    local quiet="${3:-}"
    local ok=0
    local core_dir="${main_dir}/woltlab-core"
    local db_port="3306" db_user="db" db_pass="db"
    [ -f "$env_file" ] && source "$env_file" 2>/dev/null || true
    [ -n "${DB_PORT:-}" ] && db_port="$DB_PORT"
    [ -n "${DB_USER:-}" ] && db_user="$DB_USER"
    [ -n "${DB_PASSWORD:-}" ] && db_pass="$DB_PASSWORD"

    if ! command -v php &>/dev/null; then
        [ -z "$quiet" ] && print_error "verify_deps: PHP nicht im PATH"
        return 1
    fi
    if [ -f "$core_dir/test.php" ]; then
        get_woltlab_php_bounds "$core_dir" 2>/dev/null || true
        local need_min="${WOLTLAB_PHP_LOWER:-8.2}"
        local need_max="${WOLTLAB_PHP_UPPER:-8.4}"
        local cur
        cur="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)" || cur="0"
        if [ -n "$cur" ] && [ "$(printf '%s\n' "$need_min" "$cur" | sort -V | head -1)" = "$need_min" ] && [ "$(printf '%s\n' "$cur" "$need_max" | sort -V | head -1)" = "$cur" ]; then
            :
        else
            [ -z "$quiet" ] && print_error "verify_deps: PHP $cur außerhalb WoltLab-Grenze ($need_min - $need_max)"
            ok=1
        fi
    fi

    local mysql_cmd=""
    command -v mariadb &>/dev/null && mysql_cmd="mariadb" || command -v mysql &>/dev/null && mysql_cmd="mysql"
    if [ -z "$mysql_cmd" ]; then
        [ -z "$quiet" ] && print_error "verify_deps: weder mariadb noch mysql im PATH"
        ok=1
    else
        if ! $mysql_cmd -h 127.0.0.1 -P "$db_port" -u "$db_user" -p"$db_pass" -e "SELECT 1;" &>/dev/null; then
            [ -z "$quiet" ] && print_warning "verify_deps: MariaDB auf Port $db_port nicht erreichbar (Start Service oder Docker?)"
            ok=1
        else
            [ -z "$quiet" ] && print_success "MariaDB erreichbar (Port $db_port)"
        fi
    fi
    return $ok
}

# WoltLab Core nach public deployen (Public leeren, WCFSetup.tar.gz + install.php + test.php kopieren)
# Aufruf: deploy_woltlab_to_public <main_dir> <tools_dir>
# Nutzt tools_dir/woltlab-dev/public als Ziel. Überschreibt keine anderen System-Configs.
deploy_woltlab_to_public() {
    local main_dir="${1:-}"
    local tools_dir="${2:-}"
    local core_dir="${main_dir}/woltlab-core"
    local public_dir="${tools_dir}/woltlab-dev/public"
    [ -z "$main_dir" ] || [ -z "$tools_dir" ] && return 1
    [ -f "$core_dir/WCFSetup.tar.gz" ] || [ -f "$core_dir/install.php" ] || return 1
    mkdir -p "$public_dir"
    rm -rf "$public_dir"/* 2>/dev/null || true
    rm -rf "$public_dir"/.[!.]* 2>/dev/null || true
    cp "$core_dir/WCFSetup.tar.gz" "$public_dir/" 2>/dev/null || true
    cp "$core_dir/install.php" "$public_dir/" 2>/dev/null || true
    cp "$core_dir/test.php" "$public_dir/" 2>/dev/null || true
    [ -f "$public_dir/install.php" ] && return 0
    return 1
}

# Prüfung: WoltLab-Installation erkennbar (global.php bzw. typische Dateien + DB-Tabellen)
# Aufruf: verify_woltlab_installed <public_dir> <env_file> [quiet]
# Rückgabe: 0 = Installation erkennbar, 1 = nicht erkennbar
verify_woltlab_installed() {
    local public_dir="${1:-}"
    local env_file="${2:-}"
    local quiet="${3:-}"
    [ -z "$public_dir" ] || [ ! -d "$public_dir" ] && return 1
    if [ ! -f "$public_dir/global.php" ] && [ ! -f "$public_dir/index.php" ]; then
        [ -z "$quiet" ] && print_warning "verify_woltlab_installed: global.php/index.php nicht in $public_dir"
        return 1
    fi
    local db_port="3306" db_user="db" db_pass="db" db_name="db"
    [ -f "$env_file" ] && source "$env_file" 2>/dev/null || true
    [ -n "${DB_PORT:-}" ] && db_port="$DB_PORT"
    [ -n "${DB_USER:-}" ] && db_user="$DB_USER"
    [ -n "${DB_PASSWORD:-}" ] && db_pass="$DB_PASSWORD"
    [ -n "${DB_NAME:-}" ] && db_name="$DB_NAME"
    local mysql_cmd=""
    command -v mariadb &>/dev/null && mysql_cmd="mariadb" || command -v mysql &>/dev/null && mysql_cmd="mysql"
    if [ -z "$mysql_cmd" ]; then
        [ -z "$quiet" ] && print_warning "verify_woltlab_installed: MySQL-Client nicht gefunden, überspringe DB-Check"
        return 0
    fi
    local tables
    tables=$($mysql_cmd -h 127.0.0.1 -P "$db_port" -u "$db_user" -p"$db_pass" "$db_name" -e "SHOW TABLES;" 2>/dev/null | wc -l)
    if [ "${tables:-0}" -lt 2 ]; then
        [ -z "$quiet" ] && print_warning "verify_woltlab_installed: Keine oder zu wenige Tabellen in DB $db_name"
        return 1
    fi
    [ -z "$quiet" ] && print_success "Installation erkennbar (Dateien + DB-Tabellen)"
    return 0
}

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

# Gibt die aktive Git-Repository-URL für die Anzeige zurück (Workspace-Root origin oder GIT_REPO_URL aus .env).
get_git_repo_display() {
    local main_dir="${1:-$_WOLTLAB_MAIN_DIR}"
    local env_file="${2:-$_WOLTLAB_ENV_FILE}"
    if [ -d "$main_dir/.git" ] && git -C "$main_dir" remote get-url origin >/dev/null 2>&1; then
        local url
        url=$(git -C "$main_dir" remote get-url origin 2>/dev/null | sed 's/\.git$//' | sed 's|^git@github.com:|https://github.com/|')
        echo "$url"
        return
    fi
    if [ -f "$env_file" ]; then
        local url
        url=$(grep -E "^GIT_REPO_URL=" "$env_file" 2>/dev/null | cut -d= -f2- | tr -d '\r\n' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
        if [ -n "$url" ]; then
            echo "$url" | sed 's/\.git$//' | sed 's|^git@github.com:|https://github.com/|'
            return
        fi
    fi
    echo "nicht hinterlegt"
}

# Funktion: System-Übersicht anzeigen (Plugin-Tools: Git, Node, Debug-Log)
show_system_overview() {
    debug_info "show_system_overview" "displaying system overview"
    if declare -f ui_section &>/dev/null; then
        ui_section "System-Übersicht"
    else
        echo ""; echo -e "${CYAN}─── System-Übersicht ───${NC}"; echo ""
    fi
    local git_version=$(get_git_version)
    if [ "$git_version" != "not installed" ]; then
        ui_kv "Git" "$git_version" ""
    else
        echo -e "  ${RED}${FAIL}${RESET} Git: nicht installiert"
    fi
    if command -v node &>/dev/null; then
        local node_version=$(node -v 2>/dev/null || echo "?")
        ui_kv "Node" "$node_version" "für TypeScript"
    else
        ui_warn "Node: nicht gefunden (optional, für TypeScript)"
    fi
    local woltlab_ver
    woltlab_ver=$(get_woltlab_version "$(get_public_dir)" 2>/dev/null) || woltlab_ver="unknown"
    if [ -n "$woltlab_ver" ] && [ "$woltlab_ver" != "unknown" ]; then
        ui_kv "WoltLab" "$woltlab_ver" "Core"
    else
        ui_warn "WoltLab: nicht ermittelt (Core)"
    fi
    local repo_display
    repo_display=$(get_git_repo_display)
    echo ""
    ui_infobox "Repository (Push): $repo_display"
    ui_infobox "Debug-Log: $DEBUG_LOG_FILE"
    if [ -f "$DEBUG_LOG_FILE" ]; then
        local log_size=$(du -h "$DEBUG_LOG_FILE" 2>/dev/null | cut -f1)
        local log_lines=$(wc -l < "$DEBUG_LOG_FILE" 2>/dev/null || echo "0")
        echo -e "  ${DIM}Größe: ${log_size} | Zeilen: ${log_lines}${RESET}"
    fi
    echo ""
}

# REMOVED: check_woltlab_requirements, check_updates, install_phpmyadmin_from_updates, show_update_check, _debug_log, MISSING_* (Phase 1.2)
# Funktion: Header mit Titel (moderne Box via ui.sh)
print_header() {
    local title="${1:-Simple WoltLab Plugin Manager}"
    [ -t 0 ] && clear 2>/dev/null || true
    if declare -f ui_header &>/dev/null; then
        ui_header "$title"
    else
        echo -e "${BLUE}==========================================${NC}"
        echo -e "${CYAN}${title}${NC}"
        echo -e "${BLUE}==========================================${NC}"
        echo ""
    fi
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

# Funktion: Sektion-Header (mit optionaler Breadcrumb, moderner Stil via ui.sh)
print_section() {
    local title="$1"
    shift
    local breadcrumbs=("$@")
    
    if [ ${#breadcrumbs[@]} -gt 0 ]; then
        print_breadcrumb "${breadcrumbs[@]}"
    fi
    
    if declare -f ui_section &>/dev/null; then
        ui_section "$title"
    else
        echo -e "${CYAN}==========================================${NC}"
        echo -e "${CYAN}${title}${NC}"
        echo -e "${CYAN}==========================================${NC}"
        echo ""
    fi
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

# Funktion: Ja/Nein/Abbrechen ausschließlich über Zahlen (1=Ja, 2=Nein, 0=Abbrechen)
# Gibt aus: "y", "n" oder "abort". Rückgabecode: 0=Ja, 1=Nein, 2=Abbrechen
ask_choice_yn() {
    local question="$1"
    local default="${2:-2}"
    local num
    while true; do
        echo -e "${YELLOW}${question}${NC}"
        echo "  1) Ja"
        echo "  2) Nein"
        echo "  0) Abbrechen"
        echo ""
        read -r -p "$(echo -e "${YELLOW}Wahl${NC} [${default}]: ")" num
        num="${num:-$default}"
        case "$num" in
            1) echo "y"; return 0 ;;
            2) echo "n"; return 1 ;;
            0) echo "abort"; return 2 ;;
            *) print_warning "Bitte 0, 1 oder 2 eingeben." ;;
        esac
    done
}

# Funktion: Einheitliche Ja/Nein-Abfrage (nummernbasiert: 1=Ja, 2=Nein, 0=Abbrechen)
# Rückgabecode: 0=Ja, 1=Nein, 2=Abbrechen (für bestehende Aufrufer: 0=Ja, 1=Nein)
ask_yes_no() {
    local result
    result=$(ask_choice_yn "$1" "${2:-2}")
    case "$result" in
        y) return 0 ;;
        n) return 1 ;;
        abort) return 2 ;;
        *) return 1 ;;
    esac
}

# Funktion: "0 = Zurück zum Menü" anzeigen und warten (ersetzt "Drücke ENTER")
# Gibt 0 zurück wenn User 0 eingegeben hat, sonst 1. Kein Echo.
press_zero_to_back() {
    local choice
    echo ""
    read -r -p "$(echo -e "${YELLOW}0) Zurück zum Menü${NC}: ")" choice
    [ "$choice" = "0" ]
}

# Funktion: Plugin-Verzeichnisse finden (kanonisch: check-family-deps.py --scan-workspace)
find_plugin_directories() {
    local main_dir="$1"
    local tools_dir
    tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    local scanner="${tools_dir}/check-family-deps.py"

    if command -v python3 &>/dev/null && [ -f "$scanner" ]; then
        python3 "$scanner" --scan-workspace "$main_dir" 2>/dev/null || true
        return 0
    fi

    # Fallback ohne Python
    local plugins=()
    local seen=()
    is_seen() {
        local check_path="$1"
        local check_name
        check_name=$(basename "$check_path")
        for seen_path in "${seen[@]}"; do
            if [ "$(basename "$seen_path")" = "$check_name" ]; then
                return 0
            fi
        done
        return 1
    }
    local plugin_dirs=(
        "${main_dir}/basis-plugin"
        "${main_dir}/mein-plugin"
        "${main_dir}/plugins-integrieren"
    )
    for plugin_dir in "${plugin_dirs[@]}"; do
        if [ -d "$plugin_dir" ]; then
            if [ -f "$plugin_dir/package.xml" ] || [ -f "$plugin_dir/temp_edit/package.xml" ]; then
                if ! is_seen "$plugin_dir"; then
                    plugins+=("$plugin_dir")
                    seen+=("$plugin_dir")
                fi
            fi
        fi
    done
    local skip_names=(woltlab-github woltlab-docs woltlab-core woltlab-d-ts tools maintainer docs)
    for dir in "${main_dir}"/*; do
        [ -d "$dir" ] || continue
        local base
        base=$(basename "$dir")
        for skip in "${skip_names[@]}"; do
            [ "$base" = "$skip" ] && continue 2
        done
        if [ -f "$dir/package.xml" ] || [ -f "$dir/temp_edit/package.xml" ]; then
            if ! is_seen "$dir"; then
                plugins+=("$dir")
                seen+=("$dir")
            fi
        fi
    done
    if [ ${#plugins[@]} -eq 0 ]; then
        return 0
    fi
    printf '%s\n' "${plugins[@]}"
}

# Funktion: Plugin-Version aus package.xml lesen (mit Fallbacks)
get_plugin_version() {
    local plugin_dir="$1"
    local version=""
    local package_xml=""
    
    # package.xml MUSS existieren - das ist eine Grundvoraussetzung für WoltLab-Plugins
    # Im basis-plugin (Arbeitsverzeichnis) ist package.xml IMMER vorhanden, da sie Teil des Quellcodes ist
    # Beim Packen werden Dateien nur kopiert, nie gelöscht
    if [ -f "$plugin_dir/package.xml" ]; then
        package_xml="$plugin_dir/package.xml"
    elif [ -f "$plugin_dir/_extracted/package.xml" ]; then
        package_xml="$plugin_dir/_extracted/package.xml"
    else
        # KRITISCHER FEHLER: package.xml fehlt - das darf NIE passieren!
        # Wenn package.xml fehlt, ist das ein schwerwiegender Fehler im Workflow
        echo "KRITISCHER FEHLER: package.xml nicht gefunden in $plugin_dir" >&2
        echo "Jedes WoltLab-Plugin MUSS eine package.xml enthalten!" >&2
        echo "Die package.xml ist Teil des Plugin-Quellcodes und wird niemals gelöscht." >&2
        echo "Bitte überprüfe, ob das Plugin-Verzeichnis korrekt ist." >&2
        echo "unknown"
        return 1
    fi
    
    # Wenn package.xml existiert, MUSS die Version gefunden werden
    # Versuche verschiedene Methoden, um die Version zu extrahieren
    
    # Methode 1: xmllint (am zuverlässigsten, wenn verfügbar)
    if command -v xmllint >/dev/null 2>&1; then
        version=$(xmllint --xpath "//version/text()" "$package_xml" 2>/dev/null | tr -d '[:space:]')
        # Fallback für Namespace-Problem
        if [ -z "$version" ] || [ "$version" = "" ]; then
            version=$(xmllint --xpath "string(//*[local-name()='version'])" "$package_xml" 2>/dev/null | tr -d '[:space:]')
        fi
    fi
    
    # Methode 2: grep mit Perl-Regex (robust)
    if [ -z "$version" ] || [ "$version" = "" ]; then
        version=$(grep -oP '<version>\K[^<]+' "$package_xml" 2>/dev/null | head -1 | tr -d '[:space:]')
    fi
    
    # Methode 3: grep mit Extended-Regex
    if [ -z "$version" ] || [ "$version" = "" ]; then
        version=$(grep -E '<version>' "$package_xml" 2>/dev/null | sed 's/.*<version>\([^<]*\)<\/version>.*/\1/' | head -1 | tr -d '[:space:]')
    fi
    
    # Methode 4: sed-basiert
    if [ -z "$version" ] || [ "$version" = "" ]; then
        version=$(sed -n 's/.*<version>\([^<]*\)<\/version>.*/\1/p' "$package_xml" 2>/dev/null | head -1 | tr -d '[:space:]')
    fi
    
    # Methode 5: awk-basiert
    if [ -z "$version" ] || [ "$version" = "" ]; then
        version=$(awk -F'[<>]' '/<version>/{print $3; exit}' "$package_xml" 2>/dev/null | tr -d '[:space:]')
    fi
    
    # Methode 6: Python (falls verfügbar)
    if [ -z "$version" ] || [ "$version" = "" ]; then
        if command -v python3 >/dev/null 2>&1; then
            version=$(python3 -c "import xml.etree.ElementTree as ET; tree = ET.parse('$package_xml'); root = tree.getroot(); ns = {'w': root.tag.split('}')[0].strip('{') if '}' in root.tag else ''}; v = root.find('.//version', ns) or root.find('.//{*}version'); print(v.text.strip() if v is not None and v.text else '')" 2>/dev/null | tr -d '[:space:]')
        fi
    fi
    
    # Wenn package.xml existiert, aber keine Version gefunden wurde: KRITISCHER FEHLER
    if [ -z "$version" ] || [ "$version" = "" ]; then
        echo "KRITISCHER FEHLER: Version konnte nicht aus $package_xml extrahiert werden" >&2
        echo "Die package.xml existiert, aber enthält keine gültige <version>!" >&2
        echo "Bitte überprüfe die package.xml-Datei manuell." >&2
        echo "unknown"
        return 1
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
