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

# DDEV starten - optimierte Version die nicht auf Router wartet
ddev_start_quiet() {
    debug_info "ddev_start_quiet" "starting DDEV containers directly (not waiting for router) at $(date +%s)"
    set +e
    
    # Starte Container direkt mit docker compose (schneller, wartet nicht auf Router)
    debug_info "ddev_start_quiet" "starting containers with docker compose"
    cd "$SCRIPT_DIR"
    
    # Starte Container im Hintergrund - wartet nicht auf Router
    ddev start --skip-hooks 2>&1 | grep --line-buffered -v -E "(Mutagen|upload_dirs|disable-upload-dirs-warning|You have|For faster|If this is intended)" &
    local ddev_pid=$!
    
    # Warte maximal 30 Sekunden auf Container-Start
    local wait_time=0
    local max_wait=30
    local containers_ready=0
    
    while [ $wait_time -lt $max_wait ]; do
        sleep 1
        wait_time=$((wait_time + 1))
        
        # Prüfe ob Container laufen
        if command -v docker &> /dev/null; then
            # #region agent log
            local docker_output=$(docker ps --format "{{.Names}}" 2>/dev/null || echo "")
            echo "{\"timestamp\":$(date +%s),\"location\":\"start.sh:54\",\"message\":\"docker_ps_output\",\"data\":{\"output\":\"$docker_output\"},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
            # #endregion
            
            local web_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-web" 2>/dev/null | tr -d '\n\r ' || echo "0")
            local db_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-db" 2>/dev/null | tr -d '\n\r ' || echo "0")
            
            # Bereinige Werte: entferne alle Zeichen außer Ziffern
            web_count=$(echo "$web_count" | tr -d '\n\r ' | grep -oE '^[0-9]+$' || echo "0")
            db_count=$(echo "$db_count" | tr -d '\n\r ' | grep -oE '^[0-9]+$' || echo "0")
            
            # #region agent log
            echo "{\"timestamp\":$(date +%s),\"location\":\"start.sh:57\",\"message\":\"count_values_before_check\",\"data\":{\"web_count\":\"$web_count\",\"db_count\":\"$db_count\",\"web_count_hex\":\"$(echo -n \"$web_count\" | xxd -p | tr -d '\n')\",\"db_count_hex\":\"$(echo -n \"$db_count\" | xxd -p | tr -d '\n')\",\"web_count_length\":${#web_count},\"db_count_length\":${#db_count}},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A,B,C\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
            # #endregion
            
            if [ "$web_count" -ge 1 ] && [ "$db_count" -ge 1 ]; then
                containers_ready=1
                debug_info "ddev_start_quiet" "containers are running after ${wait_time}s"
                break
            fi
        fi
    done
    
    # Wenn Container laufen, ist das OK - ddev start kann im Hintergrund weiterlaufen
    if [ $containers_ready -eq 1 ]; then
        debug_info "ddev_start_quiet" "containers started successfully, ddev start continues in background"
        local exit_code=0
    else
        debug_warning "ddev_start_quiet" "containers not ready after ${max_wait}s, checking ddev process"
        # Prüfe ob ddev start noch läuft
        if kill -0 $ddev_pid 2>/dev/null; then
            debug_info "ddev_start_quiet" "ddev start still running, waiting a bit more"
            sleep 5
            # Prüfe erneut
            local web_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-web" 2>/dev/null | tr -d '\n\r ' || echo "0")
            local db_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-db" 2>/dev/null | tr -d '\n\r ' || echo "0")
            
            # Bereinige Werte: entferne alle Zeichen außer Ziffern
            web_count=$(echo "$web_count" | tr -d '\n\r ' | grep -oE '^[0-9]+$' || echo "0")
            db_count=$(echo "$db_count" | tr -d '\n\r ' | grep -oE '^[0-9]+$' || echo "0")
            
            # #region agent log
            echo "{\"timestamp\":$(date +%s),\"location\":\"start.sh:78\",\"message\":\"count_values_retry\",\"data\":{\"web_count\":\"$web_count\",\"db_count\":\"$db_count\",\"web_count_hex\":\"$(echo -n \"$web_count\" | xxd -p | tr -d '\n')\",\"db_count_hex\":\"$(echo -n \"$db_count\" | xxd -p | tr -d '\n')\",\"web_count_length\":${#web_count},\"db_count_length\":${#db_count}},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A,B,C\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
            # #endregion
            
            if [ "$web_count" -ge 1 ] && [ "$db_count" -ge 1 ]; then
                exit_code=0
            else
                exit_code=1
            fi
        else
            # ddev start ist beendet
            wait $ddev_pid 2>/dev/null
            exit_code=$?
        fi
    fi
    
    debug_info "ddev_start_quiet" "DDEV start finished with exit_code=$exit_code"
    set -e
    return $exit_code
    
    # Prüfe ob DDEV vollständig läuft (Container + Router)
    debug_info "ddev_start_check" "checking if DDEV is fully running (containers + router)"
    sleep 1  # Reduziert von 3s auf 1s
    
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
                sleep 2  # Reduziert von 5s auf 2s
                
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
            sleep 2  # Reduziert von 10s auf 2s
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
        debug_info "ddev_start_before" "about to call ddev_start_quiet at $(date +%s)"
        ddev_start_quiet
        start_exit=$?
        debug_info "ddev_start_after" "ddev_start_quiet returned with exit_code=$start_exit at $(date +%s)"
        
        set -e
        
        debug_info "ddev_start_result" "exit_code=$start_exit"
        
        # Prüfe Exit-Code (124 = Timeout, 0 = Erfolg, andere = Fehler)
        if [ $start_exit -eq 124 ]; then
            debug_warning "ddev_start_timeout" "DDEV start timed out after 60s - checking if containers are running"
            print_info "DDEV-Start hat länger gedauert (Timeout nach 60s)..."
            print_info "Prüfe ob Container laufen..."
            
            # Prüfe schnell ob Container laufen
            containers_running=0
            if command -v docker &> /dev/null; then
                web_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-web" 2>/dev/null | tr -d '\n\r ' || echo "0")
                db_count=$(docker ps --format "{{.Names}}" 2>/dev/null | grep -c "ddev-woltlab-db" 2>/dev/null | tr -d '\n\r ' || echo "0")
                
                # Bereinige Werte: entferne alle Zeichen außer Ziffern
                web_count=$(echo "$web_count" | tr -d '\n\r ' | grep -oE '^[0-9]+$' || echo "0")
                db_count=$(echo "$db_count" | tr -d '\n\r ' | grep -oE '^[0-9]+$' || echo "0")
                
                # #region agent log
                echo "{\"timestamp\":$(date +%s),\"location\":\"start.sh:331\",\"message\":\"count_values_timeout_check\",\"data\":{\"web_count\":\"$web_count\",\"db_count\":\"$db_count\",\"web_count_hex\":\"$(echo -n \"$web_count\" | xxd -p | tr -d '\n')\",\"db_count_hex\":\"$(echo -n \"$db_count\" | xxd -p | tr -d '\n')\",\"web_count_length\":${#web_count},\"db_count_length\":${#db_count}},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A,B,C\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
                # #endregion
                
                if [ "$web_count" -ge 1 ] && [ "$db_count" -ge 1 ]; then
                    containers_running=1
                    debug_info "ddev_containers_running" "containers are running despite timeout"
                    print_success "Container laufen! Fahre fort..."
                else
                    debug_warning "ddev_containers_not_running" "containers not running after timeout"
                    print_warning "Container laufen noch nicht. Bitte manuell prüfen: ddev start"
                fi
            fi
            
            # Wenn Container laufen, fahre fort (Router wird später geprüft)
            if [ $containers_running -eq 1 ]; then
                start_exit=0  # Setze auf Erfolg, damit wir fortfahren
            fi
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
        
        # Container laufen bereits - Router separat im Hintergrund starten (NICHT warten!)
        debug_info "ddev_wait" "containers are running, starting router in background"
        
        # Starte Router im Hintergrund - NICHT warten!
        print_info "Starte Router im Hintergrund..."
        (
            cd "$SCRIPT_DIR"
            ddev router start > /dev/null 2>&1 || {
                # Fallback: Router über ddev start (aber im Hintergrund)
                ddev start > /dev/null 2>&1 || true
            }
        ) &
        router_pid=$!
        debug_info "ddev_router_background" "router starting in background (pid=$router_pid)"
        
        # KEIN SLEEP - Router läuft im Hintergrund, wir fahren sofort fort!
        
        # Container laufen bereits - Router läuft im Hintergrund
        # KEINE WARTESCHLEIFE mehr - Container sind bereit!
        ddev_ready=1
        debug_info "ddev_ready" "containers are running, router starting in background - proceeding immediately"
        
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
        # Kein Sleep mehr - Ports sollten sofort verfügbar sein nach ddev start
        
        ddev_info=$(extract_ddev_info)
        HTTP_PORT=$(echo "$ddev_info" | cut -d'|' -f1)
        HTTPS_PORT=$(echo "$ddev_info" | cut -d'|' -f2)
        MYSQL_PORT=$(echo "$ddev_info" | cut -d'|' -f3)
        MAIN_URL=$(echo "$ddev_info" | cut -d'|' -f4)
        
        debug_info "extract_ddev_info_result" "HTTP=$HTTP_PORT HTTPS=$HTTPS_PORT MySQL=$MYSQL_PORT URL=$MAIN_URL"
        
        # Retry falls Ports leer
        if [ -z "$HTTP_PORT" ] || [ "$HTTP_PORT" = "null" ]; then
            debug_debug "extract_ddev_info" "retrying extraction"
            sleep 1  # Reduziert von 3s auf 1s
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
        
        # Dockge Status prüfen (nach DDEV-Start, damit Router bereit ist)
        DOCKGE_URL=""
        if command -v docker &> /dev/null && docker info &>/dev/null 2>&1; then
            # Prüfe ob Dockge läuft
            if docker ps --format "{{.Names}}" 2>/dev/null | grep -q "^dockge$"; then
                DOCKGE_PORT="${DOCKGE_PORT:-5001}"
                DOCKGE_URL="http://localhost:${DOCKGE_PORT}"
                debug_info "dockge_status" "Dockge is running on port $DOCKGE_PORT"
            else
                debug_debug "dockge_status" "Dockge is not running"
                # Optional: Hinweis dass Dockge gestartet werden kann
                if [ -f "$SCRIPT_DIR/../dockge.sh" ]; then
                    debug_info "dockge_hint" "Dockge script available, user can start it manually"
                fi
            fi
        fi
        
        # Setze Standardwerte
        MYSQL_PORT="${MYSQL_PORT:-3306}"
        DB_HOST="${DB_HOST:-127.0.0.1}"
        DB_PORT="${MYSQL_PORT:-3306}"
        DB_NAME="${DB_NAME:-db}"
        DB_USER="${DB_USER:-db}"
        DB_PASSWORD="${DB_PASSWORD:-db}"
        # phpMyAdmin ist über DDEV verfügbar (keine separate Konfiguration nötig)
        WOLTLAB_ADMIN_USERNAME="${WOLTLAB_ADMIN_USERNAME:-Admin}"
        WOLTLAB_ADMIN_EMAIL="${WOLTLAB_ADMIN_EMAIL:-admin@example.com}"
        WOLTLAB_ADMIN_PASSWORD="${WOLTLAB_ADMIN_PASSWORD:-123456}"
        
        # Übersicht anzeigen
        print_section "WoltLab Development Environment" "Hauptmenü" "DDEV"
        
        print_list "🌐 Web-Zugänge"
        if [ -n "$MAIN_URL" ] && [ "$MAIN_URL" != "null" ]; then
            print_list_item "•" "Frontend: ${BLUE}${MAIN_URL}${NC}"
            print_list_item "•" "ACP: ${BLUE}${MAIN_URL}/acp/${NC}"
        else
            print_list_item "•" "Frontend: ${YELLOW}(URL wird ermittelt...)${NC}"
        fi
        [ -n "$DOCKGE_URL" ] && print_list_item "•" "Dockge: ${BLUE}${DOCKGE_URL}${NC}"
        echo ""
        
        print_list "👤 WoltLab Admin"
        print_list_item "•" "Benutzername: ${BLUE}${WOLTLAB_ADMIN_USERNAME}${NC}"
        print_list_item "•" "E-Mail: ${BLUE}${WOLTLAB_ADMIN_EMAIL}${NC}"
        print_list_item "•" "Passwort: ${BLUE}${WOLTLAB_ADMIN_PASSWORD}${NC}"
        echo ""
        
        print_list "🗄️  Datenbank (MySQL)"
        print_list_item "•" "Host: ${BLUE}${DB_HOST}${NC}"
        print_list_item "•" "Port: ${BLUE}${DB_PORT}${NC}"
        print_list_item "•" "Datenbank: ${BLUE}${DB_NAME}${NC}"
        print_list_item "•" "Benutzer: ${BLUE}${DB_USER}${NC}"
        print_list_item "•" "Passwort: ${BLUE}${DB_PASSWORD}${NC}"
        echo ""
        
        print_list "🔌 phpMyAdmin"
        print_list_item "•" "URL: ${BLUE}https://woltlab.ddev.site/phpmyadmin${NC}"
        print_list_item "•" "Benutzer: ${BLUE}${MYSQL_USER:-db}${NC}"
        print_list_item "•" "Passwort: ${BLUE}${MYSQL_PASSWORD:-db}${NC}"
        print_list_item "•" "Datenbank: ${BLUE}${MYSQL_DATABASE:-db}${NC}"
        echo ""
        
        print_list "📡 Ports"
        if [ -n "$HTTP_PORT" ] && [ "$HTTP_PORT" != "null" ]; then
            print_list_item "•" "HTTP: ${BLUE}http://127.0.0.1:${HTTP_PORT}${NC}"
        else
            print_list_item "•" "HTTP: ${YELLOW}(wird ermittelt...)${NC}"
        fi
        if [ -n "$HTTPS_PORT" ] && [ "$HTTPS_PORT" != "null" ]; then
            print_list_item "•" "HTTPS: ${BLUE}https://127.0.0.1:${HTTPS_PORT}${NC}"
        else
            print_list_item "•" "HTTPS: ${YELLOW}(wird ermittelt...)${NC}"
        fi
        if [ -n "$MYSQL_PORT" ] && [ "$MYSQL_PORT" != "null" ]; then
            print_list_item "•" "MySQL: ${BLUE}127.0.0.1:${MYSQL_PORT}${NC}"
        else
            print_list_item "•" "MySQL: ${YELLOW}(wird ermittelt...)${NC}"
        fi
        echo ""
        
        print_list "💡 Verfügbare Aktionen"
        echo ""
        print_list_item "1)" "${CYAN}Logs${NC}        ${ARROW} Zeige DDEV-Logs"
        print_list_item "2)" "${CYAN}Stop${NC}        ${ARROW} Stoppe DDEV"
        print_list_item "3)" "${CYAN}Restart${NC}     ${ARROW} Starte DDEV neu"
        if [ -n "$DOCKGE_URL" ]; then
            print_list_item "4)" "${CYAN}Dockge${NC}      ${ARROW} Dockge verwalten"
        elif [ -f "$SCRIPT_DIR/../dockge.sh" ]; then
            print_list_item "4)" "${CYAN}Dockge${NC}      ${ARROW} Dockge starten"
        fi
        print_list_item "0)" "${CYAN}Weiter${NC}      ${ARROW} Zurück zum Hauptmenü"
        echo ""
        
        # Optional: SSH-Agent, PHP-Konfiguration und Dockge Hinweise
        has_hints=0
        if [ -f "$SCRIPT_DIR/.ddev/php/woltlab.ini" ] || (command -v ddev &>/dev/null && ddev describe 2>/dev/null | grep -q "ssh-agent") || [ -z "$DOCKGE_URL" ]; then
            print_list "ℹ️  Hinweise"
            has_hints=1
            
            if command -v ddev &>/dev/null && ddev describe 2>/dev/null | grep -q "ssh-agent"; then
                print_list_item "•" "${YELLOW}SSH-Agent:${NC} Falls du SSH-Keys benötigst: ${BLUE}ddev auth ssh${NC}"
            fi
            
            if [ -f "$SCRIPT_DIR/.ddev/php/woltlab.ini" ]; then
                print_list_item "•" "${YELLOW}PHP-Config:${NC} Custom PHP-Konfiguration wird verwendet"
                print_list_item " " "${BLUE}$SCRIPT_DIR/.ddev/php/woltlab.ini${NC}" 6
            fi
            
            if [ -z "$DOCKGE_URL" ] && [ -f "$SCRIPT_DIR/../dockge.sh" ]; then
                print_list_item "•" "${YELLOW}Dockge:${NC} Container-Management starten: ${BLUE}../dockge.sh start${NC}"
                print_list_item " " "${BLUE}Dokumentation: https://dockge.kuma.pet/${NC}" 6
            fi
        fi
        
        [ $has_hints -eq 1 ] && echo ""
        
        # Interaktives Menü
        max_option=3
        if [ -n "$DOCKGE_URL" ] || [ -f "$SCRIPT_DIR/../dockge.sh" ]; then
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
                if [ -n "$DOCKGE_URL" ] || [ -f "$SCRIPT_DIR/../dockge.sh" ]; then
                    print_info "Öffne Dockge..."
                    "$SCRIPT_DIR/../dockge.sh" status
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
