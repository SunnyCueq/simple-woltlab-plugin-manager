#!/bin/bash

#################################################################
# WoltLab Development Tools - Zentrales Menü
# 
# Zentrale Übersicht aller verfügbaren Tools
#################################################################

set -e

#=====================================
# KONFIGURATION
#=====================================
readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$TOOLS_DIR")"
STATE_FILE="$TOOLS_DIR/.woltlab-setup-state"

#=====================================
# QUELLEN
#=====================================
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
        echo -e "${BLUE}==========================================${NC}"
        echo -e "${CYAN}WoltLab Development Tools${NC}"
        echo -e "${BLUE}==========================================${NC}"
        echo ""
    }
    ensure_executable() {
        local script_path="$1"
        if [ -f "$script_path" ] && [ ! -x "$script_path" ]; then
            chmod +x "$script_path" 2>/dev/null || return 1
        fi
    }
fi

#=====================================
# EINSTIEG: Setup anbieten wenn noch nicht ausgeführt
#=====================================
readonly SETUP_DONE_FILE="$TOOLS_DIR/.woltlab-setup-done"
if [ ! -f "$SETUP_DONE_FILE" ]; then
    echo ""
    print_info "Setup wurde noch nicht ausgeführt (WoltLab-Core, Docs, GitHub, lokaler Pfad)."
    read -p "Jetzt Setup ausführen? (j/n) [j]: " do_setup
    do_setup=${do_setup:-j}
    if [[ "$do_setup" =~ ^[jJyY] ]]; then
        if [ -x "$TOOLS_DIR/setup-minimal.sh" ]; then
            "$TOOLS_DIR/setup-minimal.sh"
        else
            chmod +x "$TOOLS_DIR/setup-minimal.sh" 2>/dev/null && "$TOOLS_DIR/setup-minimal.sh" || true
        fi
        echo ""
    fi
fi

#=====================================
# HILFSFUNKTIONEN (Menü & Wrapper)
#=====================================
run_tool_script() {
    local script_path="$1"
    shift
    ensure_executable "$script_path" || {
        print_error "Script nicht ausführbar: $script_path"
        return 1
    }
    "$script_path" "$@"
}

print_menu() {
    # Zeige System-Übersicht
    show_system_overview
    
    # Zeige Update-Informationen (optional, nicht aufdringlich)
    # check_updates  # Auskommentiert - kann bei Bedarf aktiviert werden
    
    # Finde verfügbare Plugins
    local plugins=($(find_plugin_directories "$MAIN_DIR"))
    local plugin_count=${#plugins[@]}
    
    print_list "Plugin-Entwicklung (Kern)"
    print_list_item "1)" "${CYAN}Build${NC}                 ${ARROW} Plugin bauen & Version erhöhen"
    print_list_item "2)" "${CYAN}Git Push${NC}              ${ARROW} Committen, pushen & Release erstellen"
    print_list_item "3)" "${CYAN}TypeScript${NC}            ${ARROW} TypeScript kompilieren & .min.js"
    print_list_item "4)" "${CYAN}Unpack${NC}               ${ARROW} Plugin-Paket in temp_edit/ entpacken"
    print_list_item "5)" "${CYAN}Hilfe & Dokumentation${NC} ${ARROW} README & Anleitungen"
    print_list_item "6)" "${CYAN}Plugin Validierung${NC}   ${ARROW} Code-Qualität & Store-Compliance"
    print_list_item "7)" "${CYAN}Setup / Vorbereitung${NC}  ${ARROW} WoltLab-Core, Docs, GitHub, lokaler Pfad"
    print_list_item "8)" "${CYAN}Repo anzeigen / ändern${NC} ${ARROW} Git-Repository (origin) für Push"
    if [ -f "$TOOLS_DIR/manager-push.sh" ]; then
        print_list_item "9)" "${CYAN}Manager Push${NC} (Maintainer)  ${ARROW} Plugin-Manager ins Manager-Repo pushen"
    fi
    print_list_item "L)" "${CYAN}Sprache wechseln${NC}           ${ARROW} Menü-Sprache DE/EN (aktuell: ${WOLTLAB_LANG:-en})"
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
        
        print_list "Gefundene Plugins (${plugin_count})"
        
        # Basis-plugin
        if [ ${#basis_plugins[@]} -gt 0 ]; then
            print_list "Basis-plugin"
            for plugin_path in "${basis_plugins[@]}"; do
                local version=$(get_plugin_version "$plugin_path")
                local name=$(get_plugin_name "$plugin_path")
                local relative_path="${plugin_path#$MAIN_DIR/}"
                print_list_item "•" "${name} ${YELLOW}(v${version})${NC} ${BLUE}[${relative_path}]${NC}"
            done
            echo ""
        fi
        
        # Mein-Plugin
        if [ ${#mein_plugins[@]} -gt 0 ]; then
            print_list "Mein-Plugin"
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
    run_tool_script "$TOOLS_DIR/build.sh" "$@"
    echo ""
    press_zero_to_back || true
}

run_gitpush() {
    echo -e "${YELLOW}${ARROW} Starte Gitpush.sh...${NC}"
    echo ""
    run_tool_script "$TOOLS_DIR/gitpush.sh" "$@"
    echo ""
    press_zero_to_back || true
}

run_manager_push() {
    if [ ! -f "$TOOLS_DIR/manager-push.sh" ]; then
        print_error "manager-push.sh nicht gefunden (nur für Maintainer vorhanden)"
        return 1
    fi
    echo -e "${YELLOW}${ARROW} Starte manager-push.sh...${NC}"
    echo ""
    run_tool_script "$TOOLS_DIR/manager-push.sh" "$@"
    echo ""
    press_zero_to_back || true
}

run_typescript() {
    echo -e "${YELLOW}${ARROW} Starte TypeScript.sh...${NC}"
    echo ""
    run_tool_script "$TOOLS_DIR/typescript.sh" "$@"
    echo ""
    press_zero_to_back || true
}

run_unpack() {
    echo -e "${YELLOW}${ARROW} Starte Unpack...${NC}"
    echo ""
    run_tool_script "$TOOLS_DIR/unpack.sh" "$@"
    echo ""
    press_zero_to_back || true
}

run_help() {
    echo -e "${YELLOW}${ARROW} Zeige Dokumentation...${NC}"
    echo ""
    run_tool_script "$TOOLS_DIR/help.sh"
    echo ""
    press_zero_to_back || true
}

run_setup_minimal() {
    echo -e "${YELLOW}${ARROW} Starte Setup / Vorbereitung...${NC}"
    echo ""
    run_tool_script "$TOOLS_DIR/setup-minimal.sh"
    echo ""
    press_zero_to_back || true
}

run_validate() {
    echo -e "${YELLOW}${ARROW} Starte Plugin Validierung...${NC}"
    echo ""
    run_tool_script "$TOOLS_DIR/validate-plugin.sh" "$@"
    echo ""
    press_zero_to_back || true
}

#=====================================
# HAUPTLOGIK (Menü-Schleife)
#=====================================
while true; do
    print_header
    print_menu
    
    if [ -f "$TOOLS_DIR/manager-push.sh" ]; then
        read -p "Wähle eine Option (0-9 oder L): " choice
    else
        read -p "Wähle eine Option (0-8 oder L): " choice
    fi
    echo ""
    
    case "$choice" in
        1)
            print_header
            print_section "Build - Plugin bauen" "Hauptmenü" "Build"
            
            # Zeige verfügbare Plugins
            plugins=($(find_plugin_directories "$MAIN_DIR"))
            if [ ${#plugins[@]} -gt 0 ]; then
                echo -e "${YELLOW}Verfügbare Plugins:${NC}"
                i=1
                for plugin_path in "${plugins[@]}"; do
                    version=$(get_plugin_version "$plugin_path")
                    name=$(get_plugin_name "$plugin_path")
                    relative_path="${plugin_path#$MAIN_DIR/}"
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
            fp_choice=$(ask_choice_yn "Fortfahren?" 1)
            [ "$fp_choice" = "n" ] || [ "$fp_choice" = "abort" ] && continue
            
            run_build "$build_target" "$version_type"
            ;;
        2)
            print_header
            print_section "Git Push - Commit & Push" "Hauptmenü" "Git Push"
            
            # Zeige verfügbare Plugins
            plugins=($(find_plugin_directories "$MAIN_DIR"))
            if [ ${#plugins[@]} -gt 0 ]; then
                echo -e "${YELLOW}Verfügbare Plugins:${NC}"
                i=1
                for plugin_path in "${plugins[@]}"; do
                    version=$(get_plugin_version "$plugin_path")
                    name=$(get_plugin_name "$plugin_path")
                    relative_path="${plugin_path#$MAIN_DIR/}"
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
            fp_choice=$(ask_choice_yn "Fortfahren?" 1)
            [ "$fp_choice" = "n" ] || [ "$fp_choice" = "abort" ] && continue
            
            if [ -n "$commit_msg" ]; then
                run_gitpush "$push_target" "$commit_msg"
            else
                run_gitpush "$push_target"
            fi
            ;;
        3)
            print_header
            print_section "TypeScript - Kompilieren" "Hauptmenü" "TypeScript"
            
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
                1) 
                    ts_mode="normal"
                    print_header
                    print_section "TypeScript - Kompilieren" "Hauptmenü" "TypeScript" "Kompilieren"
                    ;;
                2) 
                    ts_mode="watch"
                    print_header
                    print_section "TypeScript - Watch Mode" "Hauptmenü" "TypeScript" "Watch"
                    ;;
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
            print_section "Unpack - Plugin-Paket entpacken" "Hauptmenü" "Unpack"
            plugins=($(find_plugin_directories "$MAIN_DIR"))
            if [ ${#plugins[@]} -gt 0 ]; then
                echo -e "${YELLOW}Verfügbare Plugins:${NC}"
                i=1
                for plugin_path in "${plugins[@]}"; do
                    version=$(get_plugin_version "$plugin_path")
                    name=$(get_plugin_name "$plugin_path")
                    relative_path="${plugin_path#$MAIN_DIR/}"
                    echo -e "   ${CYAN}${i})${NC} ${name} ${YELLOW}(v${version})${NC} ${BLUE}[${relative_path}]${NC}"
                    i=$((i + 1))
                done
                echo ""
            fi
            echo -e "${YELLOW}Optionen:${NC}"
            echo -e "  ${CYAN}•${NC} Leer lassen → Erstes Plugin + neuestes Paket"
            echo -e "  ${CYAN}•${NC} <plugin>   → Plugin-Verzeichnis (z.B. basis-plugin)"
            echo -e "  ${CYAN}•${NC} <plugin> <datei.tar.gz> → Spezifisches Paket"
            echo ""
            read -p "Plugin [auto]: " unpack_plugin
            read -p "Paket (optional): " unpack_pkg
            echo ""
            fp_choice=$(ask_choice_yn "Fortfahren?" 1)
            [ "$fp_choice" = "n" ] || [ "$fp_choice" = "abort" ] && continue
            if [ -n "$unpack_pkg" ]; then
                run_unpack "$unpack_plugin" "$unpack_pkg"
            elif [ -n "$unpack_plugin" ]; then
                run_unpack "$unpack_plugin"
            else
                run_unpack
            fi
            ;;
        5)
            print_header
            print_section "Hilfe & Dokumentation" "Hauptmenü" "Hilfe"
            run_help
            ;;
        6)
            print_header
            print_section "Plugin Validierung" "Hauptmenü" "Plugin Validierung"
            plugins=($(find_plugin_directories "$MAIN_DIR"))
            if [ ${#plugins[@]} -gt 0 ]; then
                echo -e "${YELLOW}Verfügbare Plugins:${NC}"
                i=1
                for plugin_path in "${plugins[@]}"; do
                    version=$(get_plugin_version "$plugin_path")
                    name=$(get_plugin_name "$plugin_path")
                    relative_path="${plugin_path#$MAIN_DIR/}"
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
            fp_choice=$(ask_choice_yn "Fortfahren?" 1)
            [ "$fp_choice" = "n" ] || [ "$fp_choice" = "abort" ] && continue
            if [ -n "$validate_target" ]; then
                if [[ "$validate_target" =~ ^/ ]]; then
                    run_validate "$validate_target"
                else
                    run_validate "$MAIN_DIR/$validate_target"
                fi
            else
                run_validate
            fi
            ;;
        7)
            print_header
            print_section "Setup / Vorbereitung" "Hauptmenü" "Setup"
            run_setup_minimal
            ;;
        8)
            print_header
            print_section "Repo anzeigen / ändern" "Hauptmenü" "Repo"
            current_repo=$(get_git_repo_display)
            echo -e "${CYAN}Aktuelles Repository (für Push):${NC}"
            echo -e "   ${YELLOW}${current_repo}${NC}"
            echo ""
            read -p "Repo ändern? (j/n) [n]: " repo_change
            repo_change=${repo_change:-n}
            if [[ "$repo_change" =~ ^[jJyY] ]]; then
                read -p "Neue URL (z. B. https://github.com/user/repo oder git@github.com:user/repo.git): " new_repo_url
                new_repo_url=$(echo "$new_repo_url" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
                if [ -n "$new_repo_url" ]; then
                    env_set "GIT_REPO_URL" "$new_repo_url"
                    print_success "GIT_REPO_URL in Konfiguration gespeichert."
                    if [ -d "$MAIN_DIR/.git" ]; then
                        if git -C "$MAIN_DIR" remote get-url origin >/dev/null 2>&1; then
                            git -C "$MAIN_DIR" remote set-url origin "$new_repo_url"
                            print_success "Git origin aktualisiert."
                        else
                            git -C "$MAIN_DIR" remote add origin "$new_repo_url"
                            print_success "Git origin hinzugefügt."
                        fi
                    fi
                else
                    print_warning "Keine URL eingegeben."
                fi
            fi
            echo ""
            press_zero_to_back || true
            ;;
        9)
            if [ ! -f "$TOOLS_DIR/manager-push.sh" ]; then
                print_error "manager-push.sh nicht gefunden"
                sleep 1
                continue
            fi
            print_header
            print_section "Manager Push (Maintainer)" "Hauptmenü" "Manager Push"
            echo -e "${YELLOW}Plugin-Manager-Stand ins Manager-Repo pushen.${NC}"
            echo ""
            fp_choice=$(ask_choice_yn "Manager ins Repo pushen?" 1)
            [ "$fp_choice" = "n" ] || [ "$fp_choice" = "abort" ] && continue
            run_manager_push
            ;;
        l|L)
            print_header
            print_section "Sprache wechseln" "Hauptmenü" "Sprache"
            echo -e "Aktuelle Einstellung: ${CYAN}${WOLTLAB_LANG:-en}${NC}"
            echo ""
            read -p "Sprache (de/en)? [${WOLTLAB_LANG:-en}]: " lang_choice
            lang_choice="${lang_choice:-${WOLTLAB_LANG:-en}}"
            if [[ "${lang_choice,,}" == de ]]; then
                env_set "WOLTLAB_LANG" "de"
                export WOLTLAB_LANG="de"
                print_success "Sprache auf DE gesetzt (in .env gespeichert)."
            elif [[ "${lang_choice,,}" == en ]]; then
                env_set "WOLTLAB_LANG" "en"
                export WOLTLAB_LANG="en"
                print_success "Sprache auf EN gesetzt (in .env gespeichert)."
            else
                print_warning "Ungültige Eingabe. Nur 'de' oder 'en' möglich."
            fi
            echo ""
            press_zero_to_back || true
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
