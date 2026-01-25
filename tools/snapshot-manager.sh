#!/bin/bash

#################################################################
# WoltLab Development Tools - Snapshot Manager
# 
# Menü für Snapshot-Operationen
#
# @author      Sunny C
# @copyright   2026 Sunny C
#################################################################

set -e

# Verzeichnisse
TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SNAPSHOT_TOOLS_DIR="$TOOLS_DIR/woltlab-snapshot-tools"
SNAPSHOT_DIR="$TOOLS_DIR/woltlab-snapshot"

# Lade gemeinsame Funktionen
if [ -f "$TOOLS_DIR/common.sh" ]; then
    # #region agent log
    echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:21\",\"message\":\"loading_common_sh\",\"data\":{\"tools_dir\":\"$TOOLS_DIR\"},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
    # #endregion
    source "$TOOLS_DIR/common.sh"
    # #region agent log
    echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:24\",\"message\":\"common_sh_loaded\",\"data\":{\"arrow_defined\":\"${ARROW:-undefined}\"},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"A\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
    # #endregion
else
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    NC='\033[0m'
    ARROW="→"
    
    print_header() {
        clear
        echo -e "${YELLOW}╔═══════════════════════════════════════════════════════╗${NC}"
        echo -e "${YELLOW}║                                                       ║${NC}"
        echo -e "${YELLOW}║     ${CYAN}Snapshot Manager${YELLOW}                            ║${NC}"
        echo -e "${YELLOW}║                                                       ║${NC}"
        echo -e "${YELLOW}╚═══════════════════════════════════════════════════════╝${NC}"
        echo ""
    }
    
    print_success() { echo -e "${GREEN}✓ $1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_info() { echo -e "${YELLOW}→ $1${NC}"; }
    print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }
fi

# Funktion: Snapshot erstellen
create_snapshot() {
    print_header
    
    if [ ! -f "$SNAPSHOT_TOOLS_DIR/snapshot.sh" ]; then
        print_error "snapshot.sh nicht gefunden!"
        return 1
    fi

    ensure_executable "$SNAPSHOT_TOOLS_DIR/snapshot.sh" || {
        print_error "Keine Berechtigung für snapshot.sh"
        return 1
    }
    
    print_info "Starte Snapshot-Erstellung..."
    echo ""
    
    cd "$SNAPSHOT_TOOLS_DIR"
    exec ./snapshot.sh
}

# Funktion: Snapshot wiederherstellen
restore_snapshot() {
    # #region agent log
    echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:63\",\"message\":\"restore_snapshot_called\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"B\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
    # #endregion
    print_header
    # #region agent log
    echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:65\",\"message\":\"print_header_completed\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"B\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
    # #endregion
    
    if [ ! -f "$SNAPSHOT_TOOLS_DIR/restore.sh" ]; then
        print_error "restore.sh nicht gefunden!"
        return 1
    fi

    ensure_executable "$SNAPSHOT_TOOLS_DIR/restore.sh" || {
        print_error "Keine Berechtigung für restore.sh"
        return 1
    }
    
    if [ ! -d "$SNAPSHOT_DIR" ] || [ ! -f "$SNAPSHOT_DIR/database.sql.gz" ]; then
        print_error "Snapshot nicht gefunden!"
        print_info "Erstelle zuerst einen Snapshot mit Option 1"
        echo ""
        read -p "Drücke ENTER um fortzufahren..."
        return 1
    fi
    
    print_warning "Achtung: Alle aktuellen Daten werden überschrieben!"
    if ! ask_yes_no "Fortfahren?" "N"; then
        print_info "Abgebrochen"
        return 0
    fi
    
    print_info "Starte Wiederherstellung..."
    echo ""
    
    cd "$SNAPSHOT_TOOLS_DIR"
    exec ./restore.sh
}

# Funktion: Snapshots auflisten
list_snapshots() {
    print_header
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  Verfügbare Snapshots${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    if [ ! -d "$SNAPSHOT_DIR" ]; then
        print_error "Snapshot-Verzeichnis nicht gefunden!"
        return 1
    fi
    
    if [ ! -f "$SNAPSHOT_DIR/database.sql.gz" ]; then
        print_warning "Kein Snapshot gefunden"
        print_info "Erstelle einen Snapshot mit Option 1"
        return 0
    fi
    
    # Zeige Snapshot-Informationen
    if [ -f "$SNAPSHOT_DIR/metadata.txt" ]; then
        print_info "Snapshot-Informationen:${NC}"
        echo ""
        cat "$SNAPSHOT_DIR/metadata.txt"
        echo ""
    fi
    
    # Zeige Dateigrößen
    local db_size=$(du -h "$SNAPSHOT_DIR/database.sql.gz" 2>/dev/null | cut -f1)
    local public_size=$(du -sh "$SNAPSHOT_DIR/public" 2>/dev/null | cut -f1)
    
    print_info "Größen:${NC}"
    echo -e "  Datenbank:  ${GREEN}${db_size}${NC}"
    echo -e "  Public:     ${GREEN}${public_size}${NC}"
    echo ""
    
    # Zeige Dateianzahl
    local file_count=$(find "$SNAPSHOT_DIR/public" -type f 2>/dev/null | wc -l)
    print_info "Dateien:${NC} ${GREEN}${file_count}${NC}"
    echo ""
}

# Funktion: Snapshot löschen
delete_snapshot() {
    print_header
    
    if [ ! -d "$SNAPSHOT_DIR" ] || [ ! -f "$SNAPSHOT_DIR/database.sql.gz" ]; then
        print_error "Kein Snapshot gefunden!"
        return 1
    fi
    
    print_warning "Achtung: Der Snapshot wird unwiderruflich gelöscht!"
    if ! ask_yes_no "Wirklich löschen?" "N"; then
        print_info "Abgebrochen"
        return 0
    fi
    
    print_info "Lösche Snapshot..."
    rm -rf "$SNAPSHOT_DIR"/*
    print_success "Snapshot gelöscht"
}

# Funktion: Snapshot-Status prüfen
check_snapshot_status() {
    print_header
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  Snapshot-Status${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    if [ ! -d "$SNAPSHOT_DIR" ]; then
        print_error "Snapshot-Verzeichnis existiert nicht"
        return 1
    fi
    
    local has_db=false
    local has_public=false
    
    if [ -f "$SNAPSHOT_DIR/database.sql.gz" ]; then
        has_db=true
        print_success "Datenbank-Snapshot vorhanden"
    else
        print_error "Datenbank-Snapshot fehlt"
    fi
    
    if [ -d "$SNAPSHOT_DIR/public" ] && [ "$(find "$SNAPSHOT_DIR/public" -type f | wc -l)" -gt 0 ]; then
        has_public=true
        print_success "Public-Ordner-Snapshot vorhanden"
    else
        print_error "Public-Ordner-Snapshot fehlt"
    fi
    
    echo ""
    
    if [ "$has_db" = true ] && [ "$has_public" = true ]; then
        print_success "Snapshot ist vollständig"
        return 0
    else
        print_warning "Snapshot ist unvollständig"
        print_info "Erstelle einen neuen Snapshot mit Option 1"
        return 1
    fi
}

# Funktion: Menü
show_menu() {
    # #region agent log
    echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:203\",\"message\":\"show_menu_called\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"C\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
    # #endregion
    print_header
    # #region agent log
    echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:205\",\"message\":\"show_menu_header_completed\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"C\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
    # #endregion
    
    echo -e "${GREEN}Verfügbare Optionen:${NC}"
    echo ""
    echo -e "   ${YELLOW}1)${NC} ${CYAN}Snapshot erstellen${NC}          ${ARROW} Einmalig nach Installation"
    echo -e "   ${YELLOW}2)${NC} ${CYAN}Snapshot wiederherstellen${NC}  ${ARROW} Blitzschnelle Wiederherstellung"
    echo -e "   ${YELLOW}3)${NC} ${CYAN}Snapshots auflisten${NC}         ${ARROW} Zeigt verfügbare Snapshots"
    echo -e "   ${YELLOW}4)${NC} ${CYAN}Snapshot löschen${NC}            ${ARROW} Löscht aktuellen Snapshot"
    echo -e "   ${YELLOW}5)${NC} ${CYAN}Status prüfen${NC}               ${ARROW} Prüft Snapshot-Integrität"
    echo ""
    echo -e "   ${YELLOW}0)${NC} Beenden"
    echo ""
}

# Hauptprogramm
# Prüfe ob direkt aufgerufen (z.B. für "create")
if [ "$1" = "create" ]; then
    create_snapshot
    exit 0
fi

while true; do
    # #region agent log
    echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:225\",\"message\":\"menu_loop_iteration\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"D\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
    # #endregion
    show_menu
    # #region agent log
    echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:228\",\"message\":\"show_menu_completed\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"D\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
    # #endregion
    read -p "$(echo -e "${YELLOW}Option wählen${NC}: ")" choice
    # #region agent log
    echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:231\",\"message\":\"choice_read\",\"data\":{\"choice\":\"$choice\"},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"D\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
    # #endregion
    
    case "$choice" in
        1)
            # #region agent log
            echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:232\",\"message\":\"choice_1_selected\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"D\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
            # #endregion
            create_snapshot
            ;;
        2)
            # #region agent log
            echo "{\"timestamp\":$(date +%s),\"location\":\"snapshot-manager.sh:237\",\"message\":\"choice_2_selected\",\"data\":{},\"sessionId\":\"debug-session\",\"runId\":\"run1\",\"hypothesisId\":\"D\"}" >> /home/benny/Dokumente/woltlab-development/basis-plugin/.cursor/debug.log 2>/dev/null || true
            # #endregion
            restore_snapshot
            ;;
        3)
            list_snapshots
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            ;;
        4)
            delete_snapshot
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            ;;
        5)
            check_snapshot_status
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            ;;
        0)
            print_info "Beendet"
            exit 0
            ;;
        *)
            print_error "Ungültige Option"
            sleep 1
            ;;
    esac
done
