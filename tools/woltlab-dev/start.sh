#!/bin/bash

#################################################################
# DDEV Start Script für WoltLab Suite
# Pfad: tools/woltlab-dev/start.sh
# 
# Usage:
#   ./start.sh        → Startet DDEV
#   ./start.sh logs   → Startet DDEV und zeigt Logs
#   ./start.sh stop   → Stoppt DDEV
#   ./start.sh restart → Startet DDEV neu
#################################################################

set -e

# Script-Verzeichnis ermitteln
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Lade gemeinsame Funktionen (inkl. Debug-System)
TOOLS_DIR="$(dirname "$SCRIPT_DIR")"
if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    # Fallback falls common.sh nicht existiert
    echo "FEHLER: common.sh nicht gefunden in $TOOLS_DIR" >&2
    exit 1
fi

# DDEV starten mit gefilterter Ausgabe
ddev_start_quiet() {
    debug_info "ddev_start_quiet" "starting DDEV command (waiting for full startup including router)"
    set +e
    
    # Starte DDEV vollständig - warte auf Router und alle Services
    # Verwende längeren Timeout, da Router-Start Zeit braucht
    if command -v timeout &> /dev/null; then
        debug_debug "ddev_start_quiet" "using timeout command (300s for full startup)"
        # Starte DDEV mit längeren Timeout für vollständigen Start (Router, Services, etc.)
        timeout 300 ddev start 2>&1 | grep --line-buffered -v -E "(Mutagen|upload_dirs|disable-upload-dirs-warning|You have|For faster|If this is intended)" || true
        local exit_code=${PIPESTATUS[0]}
    else
        debug_debug "ddev_start_quiet" "starting without timeout (may take longer)"
        # Starte DDEV direkt - kann lange dauern
        ddev start 2>&1 | grep --line-buffered -v -E "(Mutagen|upload_dirs|disable-upload-dirs-warning|You have|For faster|If this is intended)" || true
        local exit_code=${PIPESTATUS[0]}
    fi
    
    debug_info "ddev_start_quiet" "DDEV start finished with exit_code=$exit_code"
    set -e
    
    # Prüfe ob DDEV vollständig läuft (Container + Router)
    debug_info "ddev_start_check" "checking if DDEV is fully running (containers + router)"
    sleep 3
    
    # Prüfe Container-Status
    local web_running=0
    local db_running=0
    if command -v docker &> /dev/null; then
        web_running=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-web" || echo "0")
        db_running=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-db" || echo "0")
        debug_info "ddev_containers_status" "web=$web_running db=$db_running"
    fi
    
    # Prüfe Router-Status
    local router_running=0
    if command -v docker &> /dev/null; then
        # Zähle Router-Container (zähle Zeilen, nicht Matches)
        router_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -E "ddev-router|traefik" | wc -l | tr -d '[:space:]')
        # Falls leer oder nicht numerisch, setze auf 0
        if [ -z "$router_count" ] || ! echo "$router_count" | grep -qE '^[0-9]+$'; then
            router_running=0
        else
            # Wenn mindestens 1 Container läuft, ist Router aktiv
            router_running=$([ "$router_count" -ge 1 ] && echo "1" || echo "0")
        fi
        debug_info "ddev_router_status" "router=$router_running (count=$router_count)"
    fi
    
    # Prüfe DDEV-Status (beinhaltet Router-Check)
    if check_ddev_running; then
        debug_info "ddev_start_success" "DDEV is fully running (containers + router)"
        return 0
    fi
    
    # Falls Timeout, aber Container laufen - prüfe Router und starte ggf. neu
    if [ $exit_code -eq 124 ]; then
        debug_warning "ddev_start_timeout" "timeout occurred, checking status"
        
        if [ "$web_running" -eq 1 ] && [ "$db_running" -eq 1 ]; then
            debug_info "ddev_containers_ok" "containers are running"
            
            # Prüfe Router
            if [ "$router_running" -eq 0 ]; then
                debug_warning "ddev_router_missing" "router not running, attempting to start"
                print_info "Router läuft nicht, starte Router neu..."
                ddev restart 2>&1 | grep --line-buffered -v -E "(Mutagen|upload_dirs|disable-upload-dirs-warning|You have|For faster|If this is intended)" || true
                sleep 5
                
                # Prüfe erneut
                if check_ddev_running; then
                    debug_info "ddev_router_restarted" "router restarted successfully"
                    return 0
                fi
            else
                debug_info "ddev_router_ok" "router is running"
                # Container + Router laufen, auch bei Timeout ist das OK
                return 0
            fi
        fi
    fi
    
    # Falls Exit-Code 0, aber nicht vollständig bereit - warte noch
    if [ $exit_code -eq 0 ]; then
        if [ "$web_running" -eq 1 ] && [ "$db_running" -eq 1 ]; then
            debug_info "ddev_start_success" "containers running, exit_code=0"
            return 0
        else
            debug_warning "ddev_start_check" "exit_code=0 but containers not running yet, waiting..."
            sleep 10
            if check_ddev_running; then
                return 0
            fi
        fi
    fi
    
    return $exit_code
}

# Prüfe ob DDEV läuft
check_ddev_running() {
    if ddev describe &>/dev/null 2>&1 && ddev describe 2>/dev/null | grep -q "running\|OK"; then
        return 0
    fi
    return 1
}

# Ports und URLs aus DDEV extrahieren
extract_ddev_info() {
    local HTTP_PORT=""
    local HTTPS_PORT=""
    local MYSQL_PORT=""
    local MAIN_URL=""
    
    debug_info "extract_ddev_info" "starting extraction"
    
    # Methode 1: JSON-Output mit jq (wenn verfügbar)
    if command -v jq &> /dev/null; then
        debug_debug "extract_ddev_info" "using jq method"
        local json_output=$(cd "$SCRIPT_DIR" && ddev describe -j 2>/dev/null || true)
        if [ -n "$json_output" ] && ! echo "$json_output" | grep -q "fatal\|error"; then
            HTTP_PORT=$(echo "$json_output" | jq -r '.raw.router_status.http_port // empty' 2>/dev/null || echo "")
            HTTPS_PORT=$(echo "$json_output" | jq -r '.raw.router_status.https_port // empty' 2>/dev/null || echo "")
            MYSQL_PORT=$(echo "$json_output" | jq -r '.raw.dbinfo.published_port // empty' 2>/dev/null || echo "")
            MAIN_URL=$(echo "$json_output" | jq -r '.raw.urls[0] // .raw.primary_url // empty' 2>/dev/null || echo "")
        fi
    fi
    
    # Methode 2: Text-Output mit Regex (Fallback)
    if [ -z "$HTTP_PORT" ] || [ -z "$HTTPS_PORT" ] || [ -z "$MYSQL_PORT" ]; then
        debug_debug "extract_ddev_info" "using text method"
        local describe_output=$(cd "$SCRIPT_DIR" && ddev describe 2>/dev/null || true)
        if [ -n "$describe_output" ] && ! echo "$describe_output" | grep -q "Failed to describe\|fatal\|error"; then
            [ -z "$HTTP_PORT" ] && HTTP_PORT=$(echo "$describe_output" | grep -oP 'web:80 -> 127\.0\.0\.1:\K[0-9]+' | head -1 || echo "$(echo "$describe_output" | grep -oP 'HTTP:\s+http://127\.0\.0\.1:\K[0-9]+' | head -1)")
            [ -z "$HTTPS_PORT" ] && HTTPS_PORT=$(echo "$describe_output" | grep -oP 'web:443 -> 127\.0\.0\.1:\K[0-9]+' | head -1 || echo "$(echo "$describe_output" | grep -oP 'HTTPS:\s+https://127\.0\.0\.1:\K[0-9]+' | head -1)")
            [ -z "$MYSQL_PORT" ] && MYSQL_PORT=$(echo "$describe_output" | grep -oP 'db:3306 -> 127\.0\.0\.1:\K[0-9]+' | head -1 || echo "$(echo "$describe_output" | grep -oP 'MySQL:\s+127\.0\.0\.1:\K[0-9]+' | head -1)")
            [ -z "$MAIN_URL" ] && MAIN_URL=$(echo "$describe_output" | grep -oP 'https://[^\s]+' | head -1 || echo "")
        fi
    fi
    
    # Methode 3: Docker-Port-Extraktion (letzter Fallback für MySQL)
    if [ -z "$MYSQL_PORT" ] && command -v docker &> /dev/null; then
        debug_debug "extract_ddev_info" "falling back to docker method"
        local project_name="woltlab"
        if [ -f "$SCRIPT_DIR/.ddev/config.yaml" ]; then
            project_name=$(grep -oP '^name:\s+\K\S+' "$SCRIPT_DIR/.ddev/config.yaml" 2>/dev/null | head -1 || echo "woltlab")
        fi
        MYSQL_PORT=$(docker ps --format "{{.Ports}}" 2>/dev/null | grep "ddev-${project_name}-db" | grep -oP '127\.0\.0\.1:\K[0-9]+(?=->3306)' | head -1 || echo "")
    fi
    
    # Standardwerte
    MAIN_URL="${MAIN_URL:-https://woltlab.ddev.site}"
    
    echo "${HTTP_PORT:-}|${HTTPS_PORT:-}|${MYSQL_PORT:-}|${MAIN_URL:-}"
}

# Prüfe ob DDEV installiert ist
if ! command -v ddev &> /dev/null; then
    print_error "DDEV ist nicht installiert!"
    echo ""
    echo "Installiere DDEV mit:"
    echo "  curl -fsSL https://ddev.com/install.sh | bash"
    exit 1
fi

# Prüfe ob wir im DDEV-Projekt-Verzeichnis sind
if [ ! -d ".ddev" ]; then
    print_error "Kein DDEV-Projekt gefunden!"
    echo ""
    echo "Aktuelles Verzeichnis: $SCRIPT_DIR"
    echo "Erwartetes Verzeichnis sollte eine .ddev/ Konfiguration enthalten."
    exit 1
fi

# Bereinige alte/ungültige DDEV-Konfigurationen
if command -v ddev &> /dev/null; then
    ddev_list=$(ddev list 2>&1 | grep -i "directory missing\|directory miss" || true)
    if [ -n "$ddev_list" ]; then
        debug_warning "ddev_old_configs" "found old DDEV configurations"
        print_warning "Alte DDEV-Konfigurationen gefunden. Bereinige..."
        ddev stop --unlist woltlab 2>/dev/null || true
        sleep 1
    fi
fi

# Kommando verarbeiten
COMMAND="${1:-start}"

case "$COMMAND" in
    start)
        # Verwende print_header aus common.sh (mit WoltLab-Version)
        tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
        woltlab_version=$(get_woltlab_version "$tools_dir/public" 2>/dev/null || echo "unknown")
        ddev_version=$(get_ddev_version 2>/dev/null || echo "unknown")
        
        title="DDEV"
        [ "$ddev_version" != "unknown" ] && title="DDEV v${ddev_version}"
        if [ "$woltlab_version" != "unknown" ]; then
            major_minor=$(echo "$woltlab_version" | grep -oP '^\K[0-9]+\.[0-9]+' | head -1 || echo "$woltlab_version")
            title="${title} - WoltLab Suite ${major_minor}"
        else
            title="${title} - WoltLab Suite"
        fi
        
        print_header "$title"
        
        # Stelle sicher, dass wir im richtigen Verzeichnis sind
        cd "$SCRIPT_DIR"
        debug_info "ddev_start" "working_directory=$(pwd)"
        
        # Prüfe ob DDEV bereits läuft
        if check_ddev_running; then
            debug_info "ddev_status" "DDEV is already running"
            print_success "DDEV läuft bereits!"
            echo ""
            print_info "Stoppe DDEV für sauberen Neustart..."
            set +e
            ddev stop > /dev/null 2>&1
            stop_exit=$?
            set -e
            if [ $stop_exit -eq 0 ]; then
                print_success "DDEV gestoppt!"
                sleep 2
            else
                print_warning "DDEV konnte nicht gestoppt werden, versuche trotzdem zu starten..."
                debug_warning "ddev_stop_error" "exit_code=$stop_exit"
            fi
            echo ""
        fi
        
        # Starte DDEV
        print_info "Starte DDEV..."
        debug_info "ddev_start" "calling ddev_start_quiet"
        set +e
        echo ""
        print_info "DDEV wird gestartet (dies kann einige Sekunden dauern)..."
        
        # Starte DDEV und warte auf Completion
        ddev_start_quiet
        start_exit=$?
        
        set -e
        
        debug_info "ddev_start_result" "exit_code=$start_exit"
        
        # Prüfe Exit-Code (124 = Timeout, 0 = Erfolg, andere = Fehler)
        if [ $start_exit -eq 124 ]; then
            debug_warning "ddev_start_timeout" "DDEV start timed out after 120s"
            print_warning "DDEV-Start hat länger gedauert als erwartet..."
            print_info "Prüfe DDEV-Status..."
        elif [ $start_exit -ne 0 ]; then
            debug_error "ddev_start_failed" "exit_code=$start_exit"
            print_error "DDEV konnte nicht gestartet werden (Exit-Code: $start_exit)!"
            echo ""
            print_info "Zeige DDEV-Status..."
            ddev describe 2>&1 | head -20 || true
            echo ""
            print_info "Zeige DDEV-Logs..."
            ddev logs 2>&1 | tail -30 || true
            echo ""
            print_info "Tipp: Falls ein 'directory missing' Fehler auftritt:"
            echo "      ${YELLOW}ddev stop --unlist woltlab${NC}"
            echo ""
            exit 1
        fi
        
        # Warte auf DDEV-Bereitschaft (Container müssen vollständig hochgefahren sein)
        print_info "Warte auf DDEV-Bereitschaft..."
        debug_info "ddev_wait" "waiting for containers and router to be ready"
        sleep 5
        
        # Prüfe Router-Status und starte explizit falls nötig
        print_info "Prüfe Router-Status..."
        debug_info "ddev_router_check" "checking router status"
        
        router_running=0
        if command -v docker &> /dev/null; then
            # Zähle Router-Container (zähle Zeilen, nicht Matches)
            router_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -E "ddev-router|traefik" | wc -l | tr -d '[:space:]')
            # Falls leer oder nicht numerisch, setze auf 0
            if [ -z "$router_count" ] || ! echo "$router_count" | grep -qE '^[0-9]+$'; then
                router_running=0
            else
                # Wenn mindestens 1 Container läuft, ist Router aktiv
                router_running=$([ "$router_count" -ge 1 ] && echo "1" || echo "0")
            fi
            debug_info "ddev_router_status" "router_running=$router_running (count=$router_count)"
        fi
        
        # Falls Router nicht läuft, starte ihn explizit
        if [ "$router_running" -eq 0 ] || [ -z "$router_running" ]; then
            debug_warning "ddev_router_missing" "router not running, starting explicitly"
            print_info "Router läuft nicht, starte Router..."
            set +e
            ddev router --start > /dev/null 2>&1
            router_start_exit=$?
            set -e
            debug_info "ddev_router_start" "exit_code=$router_start_exit"
            
            if [ $router_start_exit -eq 0 ]; then
                print_success "Router gestartet!"
                sleep 3  # Warte auf Router-Bereitschaft
            else
                print_warning "Router-Start fehlgeschlagen, versuche alternativen Weg..."
                # Alternativer Weg: ddev restart (startet auch Router)
                set +e
                (ddev restart > /dev/null 2>&1) &
                restart_pid=$!
                set -e
                sleep 5
                # Warte auf Prozess (nicht blockierend)
                wait $restart_pid 2>/dev/null || true
            fi
        else
            debug_info "ddev_router_ok" "router is already running"
        fi
        
        # Prüfe ob DDEV wirklich läuft (mit erweiterten Checks)
        ddev_ready=0
        for i in {1..20}; do
            debug_debug "ddev_ready_check" "attempt=$i"
            
            # Prüfe ob Container laufen
            if command -v docker &> /dev/null; then
                # Zähle Container (zähle Zeilen, nicht Matches)
                web_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep "ddev-woltlab-web" | wc -l | tr -d '[:space:]')
                db_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep "ddev-woltlab-db" | wc -l | tr -d '[:space:]')
                router_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -E "ddev-router|traefik" | wc -l | tr -d '[:space:]')
                
                # Falls leer oder nicht numerisch, setze auf 0
                [ -z "$web_count" ] || ! echo "$web_count" | grep -qE '^[0-9]+$' && web_count=0
                [ -z "$db_count" ] || ! echo "$db_count" | grep -qE '^[0-9]+$' && db_count=0
                [ -z "$router_count" ] || ! echo "$router_count" | grep -qE '^[0-9]+$' && router_count=0
                
                # Wenn mindestens 1 Container läuft, ist er aktiv
                web_running=$([ "$web_count" -ge 1 ] && echo "1" || echo "0")
                db_running=$([ "$db_count" -ge 1 ] && echo "1" || echo "0")
                router_running=$([ "$router_count" -ge 1 ] && echo "1" || echo "0")
                
                debug_debug "ddev_containers" "web=$web_running db=$db_running router=$router_running (counts: web=$web_count db=$db_count router=$router_count)"
                
                if [ "$web_running" -eq 1 ] && [ "$db_running" -eq 1 ] && [ "$router_running" -ge 1 ]; then
                    debug_info "ddev_containers_ready" "all containers including router are running"
                fi
            fi
            
            # Prüfe DDEV-Status (beinhaltet Router-Check)
            if check_ddev_running; then
                ddev_ready=1
                debug_info "ddev_ready" "DDEV is ready after $i attempts (containers + router)"
                break
            fi
            
            # Zeige Fortschritt alle 5 Versuche
            if [ $((i % 5)) -eq 0 ]; then
                print_info "Warte noch... (Versuch $i/20)"
            fi
            
            [ $i -lt 20 ] && sleep 2
        done
        
        if [ $ddev_ready -eq 1 ]; then
            print_success "DDEV gestartet!"
            echo ""
        else
            print_warning "DDEV Status unklar, prüfe Details..."
            debug_warning "ddev_status_uncertain" "status check inconclusive"
            echo ""
            print_info "DDEV Status:"
            ddev describe 2>&1 | head -15 || true
            echo ""
            if ! check_ddev_running; then
                print_error "DDEV läuft nicht! Bitte manuell prüfen: ddev start"
                exit 1
            fi
            print_info "DDEV scheint zu laufen, fahre fort..."
            echo ""
        fi
        
        # Extrahiere Ports und URLs
        print_info "Ermittle Ports und URLs..."
        sleep 2
        
        ddev_info=$(extract_ddev_info)
        HTTP_PORT=$(echo "$ddev_info" | cut -d'|' -f1)
        HTTPS_PORT=$(echo "$ddev_info" | cut -d'|' -f2)
        MYSQL_PORT=$(echo "$ddev_info" | cut -d'|' -f3)
        MAIN_URL=$(echo "$ddev_info" | cut -d'|' -f4)
        
        debug_info "extract_ddev_info_result" "HTTP=$HTTP_PORT HTTPS=$HTTPS_PORT MySQL=$MYSQL_PORT URL=$MAIN_URL"
        
        # Retry falls Ports leer
        if [ -z "$HTTP_PORT" ] || [ "$HTTP_PORT" = "null" ]; then
            debug_debug "extract_ddev_info" "retrying extraction"
            sleep 3
            ddev_info=$(extract_ddev_info)
            HTTP_PORT=$(echo "$ddev_info" | cut -d'|' -f1)
            HTTPS_PORT=$(echo "$ddev_info" | cut -d'|' -f2)
            MYSQL_PORT=$(echo "$ddev_info" | cut -d'|' -f3)
            MAIN_URL=$(echo "$ddev_info" | cut -d'|' -f4)
        fi
        
        # Lade .env Datei
        if [ -f "$SCRIPT_DIR/../.env" ]; then
            source "$SCRIPT_DIR/../.env" 2>/dev/null || true
        fi
        
        # Portainer Status prüfen (nach DDEV-Start, damit Router bereit ist)
        PORTAINER_URL=""
        if command -v docker &> /dev/null && docker info &>/dev/null 2>&1; then
            # Prüfe ob Portainer läuft
            if docker ps --format "{{.Names}}" 2>/dev/null | grep -q "^portainer$"; then
                PORTAINER_PORT="${PORTAINER_PORT:-9000}"
                PORTAINER_URL="http://localhost:${PORTAINER_PORT}"
                debug_info "portainer_status" "Portainer is running on port $PORTAINER_PORT"
            else
                debug_debug "portainer_status" "Portainer is not running"
                # Optional: Hinweis dass Portainer gestartet werden kann
                if [ -f "$SCRIPT_DIR/../portainer.sh" ]; then
                    debug_info "portainer_hint" "Portainer script available, user can start it manually"
                fi
            fi
        fi
        
        # Setze Standardwerte
        MYSQL_PORT="${MYSQL_PORT:-${HEIDISQL_PORT:-3306}}"
        DB_HOST="${DB_HOST:-127.0.0.1}"
        DB_PORT="${MYSQL_PORT:-3306}"
        DB_NAME="${DB_NAME:-db}"
        DB_USER="${DB_USER:-db}"
        DB_PASSWORD="${DB_PASSWORD:-db}"
        HEIDISQL_HOST="${HEIDISQL_HOST:-127.0.0.1}"
        HEIDISQL_PORT="${HEIDISQL_PORT:-${MYSQL_PORT:-3306}}"
        HEIDISQL_USER="${HEIDISQL_USER:-db}"
        HEIDISQL_PASSWORD="${HEIDISQL_PASSWORD:-db}"
        HEIDISQL_DATABASE="${HEIDISQL_DATABASE:-db}"
        WOLTLAB_ADMIN_USERNAME="${WOLTLAB_ADMIN_USERNAME:-Admin}"
        WOLTLAB_ADMIN_EMAIL="${WOLTLAB_ADMIN_EMAIL:-admin@example.com}"
        WOLTLAB_ADMIN_PASSWORD="${WOLTLAB_ADMIN_PASSWORD:-123456}"
        
        # Übersicht anzeigen
        echo -e "${GREEN}==========================================${NC}"
        echo -e "${GREEN}✅ WoltLab Development Environment${NC}"
        echo -e "${GREEN}==========================================${NC}"
        echo ""
        
        echo -e "${CYAN}🌐 Web-Zugänge:${NC}"
        if [ -n "$MAIN_URL" ] && [ "$MAIN_URL" != "null" ]; then
            echo -e "   Frontend:  ${BLUE}${MAIN_URL}${NC}"
            echo -e "   ACP:       ${BLUE}${MAIN_URL}/acp/${NC}"
        else
            echo -e "   Frontend:  ${YELLOW}(URL wird ermittelt...)${NC}"
        fi
        [ -n "$PORTAINER_URL" ] && echo -e "   Portainer: ${BLUE}${PORTAINER_URL}${NC}"
        echo ""
        
        echo -e "${CYAN}👤 WoltLab Admin:${NC}"
        echo -e "   Benutzername: ${BLUE}${WOLTLAB_ADMIN_USERNAME}${NC}"
        echo -e "   E-Mail:       ${BLUE}${WOLTLAB_ADMIN_EMAIL}${NC}"
        echo -e "   Passwort:     ${BLUE}${WOLTLAB_ADMIN_PASSWORD}${NC}"
        echo ""
        
        echo -e "${CYAN}🗄️  Datenbank (MySQL):${NC}"
        echo -e "   Host:     ${BLUE}${DB_HOST}${NC}"
        echo -e "   Port:     ${BLUE}${DB_PORT}${NC}"
        echo -e "   Datenbank: ${BLUE}${DB_NAME}${NC}"
        echo -e "   Benutzer: ${BLUE}${DB_USER}${NC}"
        echo -e "   Passwort: ${BLUE}${DB_PASSWORD}${NC}"
        echo ""
        
        echo -e "${CYAN}🔌 HeidiSQL:${NC}"
        echo -e "   Host:     ${BLUE}${HEIDISQL_HOST}${NC}"
        echo -e "   Port:     ${BLUE}${HEIDISQL_PORT}${NC}"
        echo -e "   Datenbank: ${BLUE}${HEIDISQL_DATABASE}${NC}"
        echo -e "   Benutzer: ${BLUE}${HEIDISQL_USER}${NC}"
        echo -e "   Passwort: ${BLUE}${HEIDISQL_PASSWORD}${NC}"
        echo ""
        
        echo -e "${CYAN}📡 Ports:${NC}"
        [ -n "$HTTP_PORT" ] && [ "$HTTP_PORT" != "null" ] && echo -e "   HTTP:     ${BLUE}http://127.0.0.1:${HTTP_PORT}${NC}" || echo -e "   HTTP:     ${YELLOW}(wird ermittelt...)${NC}"
        [ -n "$HTTPS_PORT" ] && [ "$HTTPS_PORT" != "null" ] && echo -e "   HTTPS:    ${BLUE}https://127.0.0.1:${HTTPS_PORT}${NC}" || echo -e "   HTTPS:    ${YELLOW}(wird ermittelt...)${NC}"
        [ -n "$MYSQL_PORT" ] && [ "$MYSQL_PORT" != "null" ] && echo -e "   MySQL:    ${BLUE}127.0.0.1:${MYSQL_PORT}${NC}" || echo -e "   MySQL:    ${YELLOW}(wird ermittelt...)${NC}"
        echo ""
        echo -e "   ${CYAN}💡 Verfügbare Aktionen:${NC}"
        echo ""
        echo -e "   ${YELLOW}1)${NC} ${CYAN}Logs${NC}        ${ARROW} Zeige DDEV-Logs"
        echo -e "   ${YELLOW}2)${NC} ${CYAN}Stop${NC}        ${ARROW} Stoppe DDEV"
        echo -e "   ${YELLOW}3)${NC} ${CYAN}Restart${NC}     ${ARROW} Starte DDEV neu"
        if [ -n "$PORTAINER_URL" ]; then
            echo -e "   ${YELLOW}4)${NC} ${CYAN}Portainer${NC}   ${ARROW} Portainer verwalten"
        elif [ -f "$SCRIPT_DIR/../portainer.sh" ]; then
            echo -e "   ${YELLOW}4)${NC} ${CYAN}Portainer${NC}   ${ARROW} Portainer starten"
        fi
        echo -e "   ${YELLOW}0)${NC} ${CYAN}Weiter${NC}      ${ARROW} Zurück zum Hauptmenü"
        echo ""
        
        # Optional: SSH-Agent, PHP-Konfiguration und Portainer Hinweise
        has_hints=0
        if [ -f "$SCRIPT_DIR/.ddev/php/woltlab.ini" ] || (command -v ddev &>/dev/null && ddev describe 2>/dev/null | grep -q "ssh-agent") || [ -z "$PORTAINER_URL" ]; then
            echo -e "   ${CYAN}ℹ️  Hinweise:${NC}"
            has_hints=1
            
            if command -v ddev &>/dev/null && ddev describe 2>/dev/null | grep -q "ssh-agent"; then
                echo -e "      ${YELLOW}SSH-Agent:${NC} Falls du SSH-Keys benötigst: ${BLUE}ddev auth ssh${NC}"
            fi
            
            if [ -f "$SCRIPT_DIR/.ddev/php/woltlab.ini" ]; then
                echo -e "      ${YELLOW}PHP-Config:${NC} Custom PHP-Konfiguration wird verwendet"
                echo -e "                  ${BLUE}$SCRIPT_DIR/.ddev/php/woltlab.ini${NC}"
            fi
            
            if [ -z "$PORTAINER_URL" ] && [ -f "$SCRIPT_DIR/../portainer.sh" ]; then
                echo -e "      ${YELLOW}Portainer:${NC} Container-Management starten: ${BLUE}../portainer.sh start${NC}"
                echo -e "                  ${BLUE}Dokumentation: https://docs.portainer.io/${NC}"
            fi
        fi
        
        [ $has_hints -eq 1 ] && echo ""
        
        # Interaktives Menü
        max_option=3
        if [ -n "$PORTAINER_URL" ] || [ -f "$SCRIPT_DIR/../portainer.sh" ]; then
            max_option=4
        fi
        
        read -p "Wähle eine Option (0-${max_option}): " action_choice
        echo ""
        
        case "$action_choice" in
            1)
                print_info "Zeige DDEV-Logs..."
                echo ""
                ddev logs -f || true
                echo ""
                read -p "Drücke ENTER um fortzufahren..."
                ;;
            2)
                print_info "Stoppe DDEV..."
                if ddev stop; then
                    print_success "DDEV erfolgreich gestoppt!"
                else
                    print_error "DDEV konnte nicht gestoppt werden!"
                fi
                echo ""
                read -p "Drücke ENTER um fortzufahren..."
                ;;
            3)
                print_info "Starte DDEV neu..."
                set +e
                ddev stop > /dev/null 2>&1
                set -e
                sleep 2
                cd "$SCRIPT_DIR"
                ddev_start_quiet
                if [ $? -eq 0 ]; then
                    print_success "DDEV neu gestartet!"
                else
                    print_error "DDEV konnte nicht neu gestartet werden!"
                fi
                echo ""
                read -p "Drücke ENTER um fortzufahren..."
                ;;
            4)
                if [ -n "$PORTAINER_URL" ] || [ -f "$SCRIPT_DIR/../portainer.sh" ]; then
                    print_info "Öffne Portainer..."
                    "$SCRIPT_DIR/../portainer.sh" status
                    echo ""
                    read -p "Drücke ENTER um fortzufahren..."
                else
                    print_warning "Ungültige Option!"
                fi
                ;;
            0|"")
                # Weiter zum Hauptmenü (nichts tun)
                ;;
            *)
                print_warning "Ungültige Option!"
                echo ""
                read -p "Drücke ENTER um fortzufahren..."
                ;;
        esac
        ;;
    
    logs)
        print_header "DDEV - Logs"
        print_info "Starte DDEV und zeige Logs..."
        ddev start || true
        echo ""
        print_info "Zeige Logs (Ctrl+C zum Beenden)..."
        echo ""
        ddev logs -f || true
        ;;
    
    stop)
        print_header "DDEV - Stop"
        print_info "Stoppe DDEV..."
        if ddev stop; then
            print_success "DDEV erfolgreich gestoppt!"
        else
            print_error "DDEV konnte nicht gestoppt werden!"
            exit 1
        fi
        ;;
    
    restart)
        print_header "DDEV - Restart"
        print_info "Stoppe DDEV..."
        set +e
        ddev stop > /dev/null 2>&1
        set -e
        sleep 2
        
        print_info "Starte DDEV neu..."
        # Verwende die gleiche Start-Logik wie beim normalen Start
        cd "$SCRIPT_DIR"
        ddev_start_quiet
        start_exit=$?
        
        if [ $start_exit -eq 0 ]; then
            print_success "DDEV neu gestartet!"
            echo ""
            
            # Warte auf vollständige Bereitschaft
            sleep 3
            
            ddev_info=$(extract_ddev_info)
            HTTP_PORT=$(echo "$ddev_info" | cut -d'|' -f1)
            HTTPS_PORT=$(echo "$ddev_info" | cut -d'|' -f2)
            MYSQL_PORT=$(echo "$ddev_info" | cut -d'|' -f3)
            MAIN_URL=$(echo "$ddev_info" | cut -d'|' -f4)
            
            echo -e "   ${CYAN}🌐 URL:${NC} ${BLUE}${MAIN_URL}${NC}"
            echo -e "   ${CYAN}📡 Ports:${NC} HTTP:${HTTP_PORT} HTTPS:${HTTPS_PORT} MySQL:${MYSQL_PORT}"
            echo ""
        else
            print_error "DDEV konnte nicht neu gestartet werden!"
            exit 1
        fi
        ;;
    
    status)
        print_header "DDEV - Status"
        if ddev describe &>/dev/null; then
            ddev describe
        else
            print_error "DDEV läuft nicht oder ist nicht verfügbar!"
            print_info "Starte DDEV mit: ./start.sh"
        fi
        ;;
    
    check)
        print_header "DDEV - Check"
        if check_ddev_running; then
            print_success "DDEV läuft!"
            echo ""
            ddev describe 2>/dev/null | grep -E "(Project|URL|Status)" | head -5 || true
        else
            print_error "DDEV läuft nicht!"
            print_info "Starte DDEV mit: ./start.sh"
            exit 1
        fi
        ;;
    
    *)
        print_error "Unbekanntes Kommando: $COMMAND"
        echo ""
        echo "Verfügbare Kommandos:"
        echo "  start     → Startet DDEV (Standard)"
        echo "  logs      → Startet DDEV und zeigt Logs"
        echo "  stop      → Stoppt DDEV"
        echo "  restart   → Startet DDEV neu"
        echo "  status    → Zeigt DDEV Status"
        echo "  check     → Prüft ob DDEV läuft"
        exit 1
        ;;
esac
