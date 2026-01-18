#!/bin/bash

#################################################################
# WoltLab Development Tools - Zentrales Menü
# 
# Zentrale Übersicht aller verfügbaren Tools
#################################################################

set -e

# Verzeichnisse
TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$TOOLS_DIR")"

# Lade gemeinsame Funktionen
if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    # Fallback falls common.sh nicht existiert
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    NC='\033[0m'
    
    print_header() {
        clear
        echo -e "${BLUE}╔═══════════════════════════════════════════════════════╗${NC}"
        echo -e "${BLUE}║                                                       ║${NC}"
        echo -e "${BLUE}║     ${CYAN}WoltLab Development Tools${BLUE}                      ║${NC}"
        echo -e "${BLUE}║                                                       ║${NC}"
        echo -e "${BLUE}╚═══════════════════════════════════════════════════════╝${NC}"
        echo ""
    }
fi

print_menu() {
    # Zeige System-Übersicht
    show_system_overview
    
    # Zeige Update-Informationen (optional, nicht aufdringlich)
    # check_updates  # Auskommentiert - kann bei Bedarf aktiviert werden
    
    # Finde verfügbare Plugins
    local plugins=($(find_plugin_directories "$MAIN_DIR"))
    local plugin_count=${#plugins[@]}
    
    echo -e "${GREEN}Verfügbare Tools:${NC}"
    echo ""
    echo -e "   ${YELLOW}1)${NC} ${CYAN}Build${NC}                 ${ARROW} Plugin bauen & Version erhöhen"
    echo -e "   ${YELLOW}2)${NC} ${CYAN}Git Push${NC}              ${ARROW} Commit & Push mit Release"
    echo -e "   ${YELLOW}3)${NC} ${CYAN}TypeScript${NC}            ${ARROW} Kompilieren & .min.js erstellen"
    echo -e "   ${YELLOW}4)${NC} ${CYAN}DDEV${NC}                  ${ARROW} DDEV starten/verwalten"
    echo -e "   ${YELLOW}5)${NC} ${CYAN}Restore Snapshot${NC}     ${ARROW} WoltLab wiederherstellen"
    echo -e "   ${YELLOW}6)${NC} ${CYAN}Setup${NC}                 ${ARROW} Vollständige Installation"
    echo -e "   ${YELLOW}7)${NC} ${CYAN}WoltLab Download${NC}      ${ARROW} WoltLab Core herunterladen"
    echo -e "   ${YELLOW}8)${NC} ${CYAN}Snapshot Manager${NC}     ${ARROW} Snapshot-Verwaltung"
    echo -e "   ${YELLOW}9)${NC} ${CYAN}Credentials${NC}           ${ARROW} Zugangsdaten-Verwaltung"
    echo -e "   ${YELLOW}10)${NC} ${CYAN}Portainer${NC}            ${ARROW} Container-Management"
    echo -e "   ${YELLOW}11)${NC} ${CYAN}Hilfe & Dokumentation${NC} ${ARROW} README anzeigen"
    echo -e "   ${YELLOW}12)${NC} ${CYAN}Plugin Validierung${NC}    ${ARROW} Security & Store-Compliance prüfen"
    echo -e "   ${YELLOW}13)${NC} ${CYAN}Updates prüfen${NC}        ${ARROW} Verfügbare Updates anzeigen"
    echo ""
    if [ "$plugin_count" -gt 0 ]; then
        # Gruppiere Plugins nach Verzeichnissen
        local basis_plugins=()
        local mein_plugins=()
        local integrieren_plugins=()
        local other_plugins=()
        
        for plugin_path in "${plugins[@]}"; do
            local relative_path="${plugin_path#$MAIN_DIR/}"
            if [[ "$relative_path" == basis-plugin* ]]; then
                basis_plugins+=("$plugin_path")
            elif [[ "$relative_path" == mein-plugin* ]]; then
                mein_plugins+=("$plugin_path")
            elif [[ "$relative_path" == plugins-integrieren* ]]; then
                integrieren_plugins+=("$plugin_path")
            else
                other_plugins+=("$plugin_path")
            fi
        done
        
        echo -e "${BLUE}Gefundene Plugins (${plugin_count}):${NC}"
        echo ""
        
        # Basis-plugin
        if [ ${#basis_plugins[@]} -gt 0 ]; then
            echo -e "${CYAN}Basis-plugin:${NC}"
            for plugin_path in "${basis_plugins[@]}"; do
                local version=$(get_plugin_version "$plugin_path")
                local name=$(get_plugin_name "$plugin_path")
                local relative_path="${plugin_path#$MAIN_DIR/}"
                echo -e "   ${CYAN}•${NC} ${name} ${YELLOW}(v${version})${NC} ${BLUE}[${relative_path}]${NC}"
            done
            echo ""
        fi
        
        # Mein-Plugin
        if [ ${#mein_plugins[@]} -gt 0 ]; then
            echo -e "${CYAN}Mein-Plugin:${NC}"
            for plugin_path in "${mein_plugins[@]}"; do
                local version=$(get_plugin_version "$plugin_path")
                local name=$(get_plugin_name "$plugin_path")
                local relative_path="${plugin_path#$MAIN_DIR/}"
                echo -e "   ${CYAN}•${NC} ${name} ${YELLOW}(v${version})${NC} ${BLUE}[${relative_path}]${NC}"
            done
            echo ""
        fi
        
        # Plugins integrieren
        if [ ${#integrieren_plugins[@]} -gt 0 ]; then
            echo -e "${CYAN}Plugins integrieren:${NC}"
            for plugin_path in "${integrieren_plugins[@]}"; do
                local version=$(get_plugin_version "$plugin_path")
                local name=$(get_plugin_name "$plugin_path")
                local relative_path="${plugin_path#$MAIN_DIR/}"
                echo -e "   ${CYAN}•${NC} ${name} ${YELLOW}(v${version})${NC} ${BLUE}[${relative_path}]${NC}"
            done
            echo ""
        fi
        
        # Andere Plugins (falls vorhanden)
        if [ ${#other_plugins[@]} -gt 0 ]; then
            echo -e "${CYAN}Weitere Plugins:${NC}"
            for plugin_path in "${other_plugins[@]}"; do
                local version=$(get_plugin_version "$plugin_path")
                local name=$(get_plugin_name "$plugin_path")
                local relative_path="${plugin_path#$MAIN_DIR/}"
                echo -e "   ${CYAN}•${NC} ${name} ${YELLOW}(v${version})${NC} ${BLUE}[${relative_path}]${NC}"
            done
            echo ""
        fi
    fi
    echo -e "   ${YELLOW}0)${NC} Beenden"
    echo ""
}

# Scripts ausführen
run_build() {
    echo -e "${YELLOW}${ARROW} Starte Build.sh...${NC}"
    echo ""
    "$TOOLS_DIR/build.sh" "$@"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_gitpush() {
    echo -e "${YELLOW}${ARROW} Starte Gitpush.sh...${NC}"
    echo ""
    "$TOOLS_DIR/gitpush.sh" "$@"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_start_ddev() {
    echo -e "${YELLOW}${ARROW} Starte DDEV...${NC}"
    echo ""
    "$TOOLS_DIR/start-ddev.sh" "$@"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_typescript() {
    echo -e "${YELLOW}${ARROW} Starte TypeScript.sh...${NC}"
    echo ""
    "$TOOLS_DIR/typescript.sh" "$@"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_restore_snapshot() {
    echo -e "${YELLOW}${ARROW} Stelle Snapshot wieder her...${NC}"
    echo ""
    "$TOOLS_DIR/restore-snapshot.sh"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_setup() {
    echo -e "${YELLOW}${ARROW} Starte Setup...${NC}"
    echo ""
    "$TOOLS_DIR/setup.sh"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_download_woltlab() {
    echo -e "${YELLOW}${ARROW} Lade WoltLab Core herunter...${NC}"
    echo ""
    "$TOOLS_DIR/download-woltlab.sh"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_snapshot_manager() {
    echo -e "${YELLOW}${ARROW} Öffne Snapshot Manager...${NC}"
    echo ""
    "$TOOLS_DIR/snapshot-manager.sh"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_credentials() {
    echo -e "${YELLOW}${ARROW} Öffne Credentials Manager...${NC}"
    echo ""
    "$TOOLS_DIR/credentials.sh"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_portainer() {
    echo -e "${YELLOW}${ARROW} Öffne Portainer...${NC}"
    echo ""
    "$TOOLS_DIR/portainer.sh" "$@"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_help() {
    echo -e "${YELLOW}${ARROW} Zeige Dokumentation...${NC}"
    echo ""
    "$TOOLS_DIR/help.sh"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

run_validate() {
    echo -e "${YELLOW}${ARROW} Starte Plugin Validierung...${NC}"
    echo ""
    "$TOOLS_DIR/validate-plugin.sh" "$@"
    echo ""
    read -p "Drücke ENTER um fortzufahren..."
}

# Hauptmenü
while true; do
    print_header
    print_menu
    
    read -p "Wähle eine Option (0-13): " choice
    echo ""
    
    case "$choice" in
        1)
            print_header
            print_section "Build - Plugin bauen"
            
            # Zeige verfügbare Plugins
            local plugins=($(find_plugin_directories "$MAIN_DIR"))
            if [ ${#plugins[@]} -gt 0 ]; then
                echo -e "${YELLOW}Verfügbare Plugins:${NC}"
                local i=1
                for plugin_path in "${plugins[@]}"; do
                    local version=$(get_plugin_version "$plugin_path")
                    local name=$(get_plugin_name "$plugin_path")
                    local relative_path="${plugin_path#$MAIN_DIR/}"
                    echo -e "   ${CYAN}${i})${NC} ${name} ${YELLOW}(v${version})${NC} ${BLUE}[${relative_path}]${NC}"
                    i=$((i + 1))
                done
                echo ""
            fi
            
            echo -e "${YELLOW}Optionen:${NC}"
            echo -e "  ${CYAN}•${NC} Leer lassen → Erstes gefundenes Plugin bauen"
            echo -e "  ${CYAN}•${NC} <name>      → Spezifisches Plugin-Verzeichnis bauen"
            echo -e "  ${CYAN}•${NC} all         → Alle Plugin-Verzeichnisse bauen"
            echo ""
            read -p "Was möchtest du bauen? [auto]: " build_target
            build_target=${build_target:-auto}
            
            echo ""
            echo -e "${YELLOW}Version-Typ:${NC}"
            echo -e "  ${CYAN}•${NC} patch (Standard) → 1.0.0 → 1.0.1"
            echo -e "  ${CYAN}•${NC} minor            → 1.0.0 → 1.1.0"
            echo -e "  ${CYAN}•${NC} major            → 1.0.0 → 2.0.0"
            echo ""
            read -p "Version-Typ? [patch]: " version_type
            version_type=${version_type:-patch}
            
            echo ""
            echo -e "   ${YELLOW}0)${NC} Zurück zum Hauptmenü"
            echo ""
            read -p "Fortfahren? [j]: " continue_choice
            if [ "${continue_choice:-j}" = "0" ]; then
                continue
            fi
            
            run_build "$build_target" "$version_type"
            ;;
        2)
            print_header
            print_section "Git Push - Commit & Push"
            
            # Zeige verfügbare Plugins
            local plugins=($(find_plugin_directories "$MAIN_DIR"))
            if [ ${#plugins[@]} -gt 0 ]; then
                echo -e "${YELLOW}Verfügbare Plugins:${NC}"
                local i=1
                for plugin_path in "${plugins[@]}"; do
                    local version=$(get_plugin_version "$plugin_path")
                    local name=$(get_plugin_name "$plugin_path")
                    local relative_path="${plugin_path#$MAIN_DIR/}"
                    echo -e "   ${CYAN}${i})${NC} ${name} ${YELLOW}(v${version})${NC} ${BLUE}[${relative_path}]${NC}"
                    i=$((i + 1))
                done
                echo ""
            fi
            
            echo -e "${YELLOW}Optionen:${NC}"
            echo -e "  ${CYAN}•${NC} Leer lassen → Auto-Detection (erkennt Änderungen)"
            echo -e "  ${CYAN}•${NC} <name>      → Spezifisches Plugin-Verzeichnis pushen"
            echo -e "  ${CYAN}•${NC} all         → Alle Plugin-Verzeichnisse pushen"
            echo ""
            read -p "Was möchtest du pushen? [auto]: " push_target
            push_target=${push_target:-auto}
            
            echo ""
            echo -e "${YELLOW}Commit-Nachricht:${NC}"
            echo -e "  ${CYAN}•${NC} Leer lassen → Automatische Commit-Nachricht generieren"
            echo -e "  ${CYAN}•${NC} <text>      → Eigene Commit-Nachricht eingeben"
            echo ""
            read -p "Commit-Nachricht? [auto]: " commit_msg
            commit_msg=${commit_msg:-}
            
            echo ""
            echo -e "   ${YELLOW}0)${NC} Zurück zum Hauptmenü"
            echo ""
            read -p "Fortfahren? [j]: " continue_choice
            if [ "${continue_choice:-j}" = "0" ]; then
                continue
            fi
            
            if [ -n "$commit_msg" ]; then
                run_gitpush "$push_target" "$commit_msg"
            else
                run_gitpush "$push_target"
            fi
            ;;
        3)
            print_header
            print_section "TypeScript - Kompilieren"
            
            echo -e "${GREEN}Verfügbare Optionen:${NC}"
            echo ""
            echo -e "   ${YELLOW}1)${NC} ${CYAN}Kompilieren${NC}  ${ARROW} TypeScript kompilieren"
            echo -e "   ${YELLOW}2)${NC} ${CYAN}Watch${NC}        ${ARROW} Watch-Mode (automatische Kompilierung)"
            echo ""
            echo -e "   ${YELLOW}0)${NC} Zurück zum Hauptmenü"
            echo ""
            read -p "Wähle eine Option (0-2): " ts_choice
            echo ""
            
            case "$ts_choice" in
                1) ts_mode="normal" ;;
                2) ts_mode="watch" ;;
                0) continue ;;
                *) 
                    echo -e "${RED}Ungültige Option!${NC}"
                    sleep 1
                    continue
                    ;;
            esac
            
            if [ "$ts_mode" = "watch" ]; then
                run_typescript "watch"
            else
                run_typescript
            fi
            ;;
        4)
            print_header
            print_section "DDEV - Verwalten"
            
            echo -e "${GREEN}Verfügbare Optionen:${NC}"
            echo ""
            echo -e "   ${YELLOW}1)${NC} ${CYAN}Start${NC}      ${ARROW} DDEV starten/Status anzeigen"
            echo -e "   ${YELLOW}2)${NC} ${CYAN}Logs${NC}      ${ARROW} DDEV starten und Logs anzeigen"
            echo -e "   ${YELLOW}3)${NC} ${CYAN}Stop${NC}      ${ARROW} DDEV stoppen"
            echo -e "   ${YELLOW}4)${NC} ${CYAN}Restart${NC}   ${ARROW} DDEV neu starten"
            echo -e "   ${YELLOW}5)${NC} ${CYAN}Status${NC}    ${ARROW} DDEV Status anzeigen"
            echo ""
            echo -e "   ${YELLOW}0)${NC} Zurück zum Hauptmenü"
            echo ""
            read -p "Wähle eine Option (0-5): " ddev_choice
            echo ""
            
            case "$ddev_choice" in
                1) ddev_cmd="start" ;;
                2) ddev_cmd="logs" ;;
                3) ddev_cmd="stop" ;;
                4) ddev_cmd="restart" ;;
                5) ddev_cmd="status" ;;
                0) continue ;;
                *) 
                    echo -e "${RED}Ungültige Option!${NC}"
                    sleep 1
                    continue
                    ;;
            esac
            
            run_start_ddev "$ddev_cmd"
            ;;
        5)
            print_header
            print_section "Restore Snapshot - WoltLab wiederherstellen"
            
            echo -e "${YELLOW}${WARNING} Warnung:${NC} Dies wird die komplette WoltLab-Installation"
            echo -e "         aus dem Snapshot wiederherstellen!"
            echo ""
            echo -e "${YELLOW}Optionen:${NC}"
            echo -e "  ${CYAN}•${NC} j/J → Fortfahren und Snapshot wiederherstellen"
            echo -e "  ${CYAN}•${NC} Leer lassen → Abbrechen"
            echo ""
            echo -e "   ${YELLOW}0)${NC} Zurück zum Hauptmenü"
            echo ""
            read -p "Fortfahren? [N]: " confirm
            if [ "$confirm" = "0" ]; then
                continue
            elif [[ "$confirm" =~ ^[Jj]$ ]]; then
                run_restore_snapshot
            else
                echo -e "${YELLOW}Abgebrochen.${NC}"
                sleep 1
            fi
            ;;
        6)
            print_header
            print_section "Setup - Vollständige Installation"
            run_setup
            ;;
        7)
            print_header
            print_section "WoltLab Download - Core herunterladen"
            run_download_woltlab
            ;;
        8)
            print_header
            print_section "Snapshot Manager - Snapshot-Verwaltung"
            run_snapshot_manager
            ;;
        9)
            print_header
            print_section "Credentials - Zugangsdaten-Verwaltung"
            run_credentials
            ;;
        10)
            print_header
            print_section "Portainer - Container-Management"
            
            echo -e "${GREEN}Verfügbare Optionen:${NC}"
            echo ""
            echo -e "   ${YELLOW}1)${NC} ${CYAN}Start${NC}      ${ARROW} Portainer starten/Status anzeigen"
            echo -e "   ${YELLOW}2)${NC} ${CYAN}Stop${NC}       ${ARROW} Portainer stoppen"
            echo -e "   ${YELLOW}3)${NC} ${CYAN}Restart${NC}    ${ARROW} Portainer neu starten"
            echo -e "   ${YELLOW}4)${NC} ${CYAN}Status${NC}     ${ARROW} Portainer Status anzeigen"
            echo -e "   ${YELLOW}5)${NC} ${CYAN}Open${NC}       ${ARROW} Portainer im Browser öffnen"
            echo ""
            echo -e "   ${YELLOW}0)${NC} Zurück zum Hauptmenü"
            echo ""
            read -p "Wähle eine Option (0-5): " portainer_choice
            echo ""
            
            case "$portainer_choice" in
                1) portainer_cmd="start" ;;
                2) portainer_cmd="stop" ;;
                3) portainer_cmd="restart" ;;
                4) portainer_cmd="status" ;;
                5) portainer_cmd="open" ;;
                0) continue ;;
                *) 
                    echo -e "${RED}Ungültige Option!${NC}"
                    sleep 1
                    continue
                    ;;
            esac
            
            run_portainer "$portainer_cmd"
            ;;
        11)
            print_header
            print_section "Hilfe & Dokumentation"
            run_help
            ;;
        12)
            print_header
            print_section "Plugin Validierung"
            
            # Zeige verfügbare Plugins
            local plugins=($(find_plugin_directories "$MAIN_DIR"))
            if [ ${#plugins[@]} -gt 0 ]; then
                echo -e "${YELLOW}Verfügbare Plugins:${NC}"
                local i=1
                for plugin_path in "${plugins[@]}"; do
                    local version=$(get_plugin_version "$plugin_path")
                    local name=$(get_plugin_name "$plugin_path")
                    local relative_path="${plugin_path#$MAIN_DIR/}"
                    echo -e "   ${CYAN}${i})${NC} ${name} ${YELLOW}(v${version})${NC} ${BLUE}[${relative_path}]${NC}"
                    i=$((i + 1))
                done
                echo ""
            fi
            
            echo -e "${YELLOW}Was wird geprüft:${NC}"
            echo -e "  ${CYAN}•${NC} PHP & XML Syntax"
            echo -e "  ${CYAN}•${NC} Security (SQL-Injection, XSS)"
            echo -e "  ${CYAN}•${NC} Code-Qualität (Debug-Code, Test-Credentials)"
            echo -e "  ${CYAN}•${NC} Plugin Store Compliance (Übersetzungen, Minversion)"
            echo -e "  ${CYAN}•${NC} WoltLab API Best Practices"
            echo ""
            echo -e "${YELLOW}Optionen:${NC}"
            echo -e "  ${CYAN}•${NC} Leer lassen → Aktuelles Verzeichnis prüfen"
            echo -e "  ${CYAN}•${NC} <name>      → Spezifisches Plugin-Verzeichnis prüfen"
            echo ""
            read -p "Welches Plugin soll validiert werden? [aktuelles Verzeichnis]: " validate_target
            
            echo ""
            echo -e "   ${YELLOW}0)${NC} Zurück zum Hauptmenü"
            echo ""
            read -p "Fortfahren? [j]: " continue_choice
            if [ "${continue_choice:-j}" = "0" ]; then
                continue
            fi
            
            if [ -n "$validate_target" ]; then
                # Prüfe ob validate_target bereits absoluter Pfad ist
                if [[ "$validate_target" =~ ^/ ]]; then
                    run_validate "$validate_target"
                else
                    run_validate "$MAIN_DIR/$validate_target"
                fi
            else
                run_validate
            fi
            ;;
        13)
            print_header
            print_section "Updates prüfen"
            show_update_check
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            ;;
        0)
            echo -e "${GREEN}Auf Wiedersehen!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Ungültige Option!${NC}"
            sleep 1
            ;;
    esac
done
