#!/usr/bin/env bash

#################################################################
# WoltLab Plugin Unpacker
# Pfad: tools/unpack.sh
# 
# Usage:
#   ./tools/unpack.sh [plugin] [package.tar.gz]
#   ./tools/unpack.sh              → Erstes Plugin + neuestes Paket
#   ./tools/unpack.sh basis-plugin → Spezifisches Plugin
#   ./tools/unpack.sh basis-plugin package_v1.0.0.tar.gz → Spezifisches Paket
#
# Entpackt ein WoltLab Plugin-Paket korrekt in temp_edit/
#################################################################

set -e

#=====================================
# KONFIGURATION
#=====================================
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$SCRIPT_DIR")"

#=====================================
# QUELLEN
#=====================================
if [ -f "$SCRIPT_DIR/common.sh" ]; then
    source "$SCRIPT_DIR/common.sh"
else
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    NC='\033[0m'
    
    print_section() {
        local title="$1"
        shift
        local breadcrumbs=("$@")
        if [ ${#breadcrumbs[@]} -gt 0 ]; then
            echo -e "${BLUE}Navigation:${NC} ${CYAN}${breadcrumbs[*]}${NC}"
            echo ""
        fi
        echo -e "${CYAN}==========================================${NC}"
        echo -e "${CYAN}${title}${NC}"
        echo -e "${CYAN}==========================================${NC}"
        echo ""
    }
    
    print_success() { echo -e "${GREEN}✓ $1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }
    print_info() { echo -e "${CYAN}ℹ $1${NC}"; }
fi

#=====================================
# HAUPTLOGIK
#=====================================
PLUGIN_TARGET="${1:-}"
PACKAGE_FILE="${2:-}"

# Suche nach Plugin-Verzeichnissen
# Bei explizitem Ziel (Plugin + Paket): Verzeichnis reicht (erster Lauf ohne temp_edit möglich)
# Ohne explizites Ziel: nur Verzeichnisse mit temp_edit/package.xml oder package.xml
if [ -n "$PLUGIN_TARGET" ]; then
    if [ ! -d "${MAIN_DIR}/${PLUGIN_TARGET}" ]; then
        print_error "Plugin-Verzeichnis nicht gefunden: ${PLUGIN_TARGET}"
        exit 1
    fi
    PROJECT_ROOT="$(cd "${MAIN_DIR}/${PLUGIN_TARGET}" && pwd)"
else
    # Erstes Plugin-Verzeichnis mit temp_edit/package.xml oder package.xml finden
    PROJECT_ROOT=""
    for plugin_dir in "${MAIN_DIR}"/*; do
        if [ -d "$plugin_dir" ]; then
            if [ -f "$plugin_dir/temp_edit/package.xml" ] || [ -f "$plugin_dir/package.xml" ]; then
                PROJECT_ROOT="$(cd "$plugin_dir" && pwd)"
                break
            fi
        fi
    done
    
    if [ -z "$PROJECT_ROOT" ]; then
        print_error "Kein Plugin-Verzeichnis mit temp_edit/package.xml oder package.xml gefunden"
        exit 1
    fi
fi

cd "${PROJECT_ROOT}"

PLUGIN_NAME=$(basename "$PROJECT_ROOT")
print_section "WoltLab Plugin Unpacker" "Hauptmenue" "Unpack"
print_info "Plugin: ${PLUGIN_NAME}"
echo ""

# Suche nach Paket-Datei
if [ -z "$PACKAGE_FILE" ]; then
    PACKAGE_FILE="$(swpm_find_latest_package "$MAIN_DIR" "$PROJECT_ROOT" || true)"
    if [ -z "$PACKAGE_FILE" ] || [ ! -f "$PACKAGE_FILE" ]; then
        print_error "Keine .tar.gz Datei gefunden (gesucht: releases/$(swpm_plugin_release_label "$PROJECT_ROOT")/ und Plugin-Root)"
        exit 1
    fi
    print_info "Verwende neuestes Paket: ${PACKAGE_FILE}"
elif [ ! -f "$PACKAGE_FILE" ]; then
    # Relativer Name: zuerst releases/<plugin>/, dann Plugin-Roots unter MAIN_DIR
    FOUND_PACKAGE=""
    RELEASE_DIR="$(swpm_release_dir "$MAIN_DIR" "$PROJECT_ROOT")"
    if [ -f "${RELEASE_DIR}/${PACKAGE_FILE}" ]; then
        FOUND_PACKAGE="${RELEASE_DIR}/${PACKAGE_FILE}"
    else
        for search_dir in "${MAIN_DIR}/releases"/* "${MAIN_DIR}"/*; do
            if [ -d "$search_dir" ] && [ -f "${search_dir}/${PACKAGE_FILE}" ]; then
                FOUND_PACKAGE="${search_dir}/${PACKAGE_FILE}"
                break
            fi
        done
    fi

    if [ -n "$FOUND_PACKAGE" ]; then
        PACKAGE_FILE="$FOUND_PACKAGE"
        print_info "Paket gefunden in: ${FOUND_PACKAGE}"
    else
        print_error "Paket-Datei nicht gefunden: ${PACKAGE_FILE}"
        print_warning "Durchsucht: ${RELEASE_DIR}/ und ${MAIN_DIR}/*/"
        exit 1
    fi
fi

print_info "Paket: ${PACKAGE_FILE}"
echo ""

# temp_edit bereinigen
if [ -d "temp_edit" ]; then
    print_info "Bereinige temp_edit..."
    rm -rf temp_edit
fi

mkdir -p temp_edit

# Haupt-Paket entpacken (.tar.gz mit -xzf, .tar mit -xf)
print_info "[1/5] Entpacke Haupt-Paket..."
if [[ "$PACKAGE_FILE" == *.gz ]]; then
    tar -xzf "$PACKAGE_FILE" -C temp_edit
else
    tar -xf "$PACKAGE_FILE" -C temp_edit
fi
print_success "Haupt-Paket entpackt"

cd temp_edit

# files.tar entpacken
if [ -f "files.tar" ]; then
    print_info "[2/5] Entpacke files.tar..."
    FILE_COUNT=$(tar -tf files.tar 2>/dev/null | wc -l)
    if [ "$FILE_COUNT" -eq 0 ]; then
        print_warning "files.tar ist leer"
    fi
    tar -xf files.tar
    print_success "files.tar entpackt (${FILE_COUNT} Datei(en))"
else
    print_warning "files.tar nicht gefunden"
fi

# templates.tar entpacken (WoltLab-Norm: nach templates/, nicht lose ins Root)
if [ -f "templates.tar" ]; then
    print_info "[3/5] Entpacke templates.tar → templates/..."
    TEMPLATE_COUNT=$(tar -tf templates.tar 2>/dev/null | wc -l)
    mkdir -p templates
    tar -xf templates.tar -C templates
    print_success "templates.tar entpackt nach templates/ (${TEMPLATE_COUNT} Datei(en))"
else
    print_warning "templates.tar nicht gefunden"
fi

# acptemplates.tar entpacken (WICHTIG: In acptemplates/ Ordner!)
if [ -f "acptemplates.tar" ]; then
    print_info "[4/5] Entpacke acptemplates.tar..."
    ACP_TEMPLATE_COUNT=$(tar -tf acptemplates.tar 2>/dev/null | wc -l)
    mkdir -p acptemplates
    tar -xf acptemplates.tar -C acptemplates
    print_success "acptemplates.tar entpackt (${ACP_TEMPLATE_COUNT} Datei(en))"
else
    print_warning "acptemplates.tar nicht gefunden"
fi

# files_wcf.tar entpacken
if [ -f "files_wcf.tar" ]; then
    print_info "[5/5] Entpacke files_wcf.tar..."
    JS_FILE_COUNT=$(tar -tf files_wcf.tar 2>/dev/null | wc -l)
    if [ "$JS_FILE_COUNT" -eq 0 ]; then
        print_warning "files_wcf.tar ist leer"
    fi
    tar -xf files_wcf.tar
    print_success "files_wcf.tar entpackt (${JS_FILE_COUNT} Datei(en))"
else
    print_warning "files_wcf.tar nicht gefunden"
fi

# TAR-Dateien entfernen
rm -f *.tar

# WICHTIG: Templates die für ACP benötigt werden müssen in acptemplates/ liegen
# Prüfe templateListener.xml für ACP-Template-Referenzen
if [ -f "templateListener.xml" ]; then
    print_info "Pruefe ACP-Template-Referenzen..."
    
    # Finde alle Template-Referenzen die für ACP (admin environment) sind
    # Pattern: <environment>admin</environment> ... <templatecode>...file='TEMPLATE_NAME'...
    # Verwende Python für zuverlässiges XML-Parsing
    python3 << 'PYTHON_SCRIPT'
import re
import sys

try:
    with open('templateListener.xml', 'r') as f:
        content = f.read()
    
    # Finde alle templatelistener Blöcke mit environment=admin
    # Pattern: <templatelistener>...</templatelistener> mit <environment>admin</environment>
    pattern = r'<templatelistener[^>]*>.*?<environment>admin</environment>.*?file=[\'"]([^\'"]+)[\'"].*?</templatelistener>'
    matches = re.findall(pattern, content, re.DOTALL)
    
    for template_file in matches:
        # Entferne führende __ falls vorhanden, füge .tpl hinzu
        if not template_file.endswith('.tpl'):
            template_file = template_file + '.tpl'
        
        # Frontend-Quelle: templates/ (kanonisch) oder Root (Legacy)
        import os
        candidates = [
            template_file,
            f'templates/{template_file}',
        ]
        for src in candidates:
            if os.path.exists(src) and not os.path.exists(f'acptemplates/{template_file}'):
                print(src)
                break
except Exception as e:
    pass
PYTHON_SCRIPT
    
    ACP_TEMPLATES=$(python3 << 'PYTHON_SCRIPT'
import re
import os

try:
    with open('templateListener.xml', 'r') as f:
        content = f.read()
    
    # Finde alle templatelistener Blöcke mit environment=admin
    pattern = r'<templatelistener[^>]*>.*?<environment>admin</environment>.*?file=[\'"]([^\'"]+)[\'"].*?</templatelistener>'
    matches = re.findall(pattern, content, re.DOTALL)
    
    for template_file in matches:
        if not template_file.endswith('.tpl'):
            template_file = template_file + '.tpl'
        
        for src in (template_file, f'templates/{template_file}'):
            if os.path.exists(src) and not os.path.exists(f'acptemplates/{template_file}'):
                print(src)
                break
except:
    pass
PYTHON_SCRIPT
)
    
    if [ -n "$ACP_TEMPLATES" ]; then
        while IFS= read -r src_path; do
            template_file=$(basename "$src_path")
            if [ -f "$src_path" ] && [ ! -f "acptemplates/$template_file" ]; then
                print_info "Kopiere $src_path nach acptemplates/ (wird für ACP benötigt)"
                cp "$src_path" "acptemplates/$template_file"
            fi
        done <<< "$ACP_TEMPLATES"
    fi
fi

cd ..

# Validierung: Prüfe ob alle erwarteten Verzeichnisse vorhanden sind
print_info "[VALIDIERUNG] Pruefe entpackte Struktur..."
VALIDATION_ERRORS=0

# Prüfe ob package.xml vorhanden ist
if [ ! -f "temp_edit/package.xml" ]; then
    print_error "package.xml fehlt im entpackten Paket!"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "package.xml gefunden"
fi

# Prüfe ob kritische Verzeichnisse vorhanden sind (falls erwartet)
if [ -d "temp_edit/js" ]; then
    JS_COUNT=$(find temp_edit/js -name "*.js" 2>/dev/null | wc -l)
    print_success "js/ Verzeichnis vorhanden (${JS_COUNT} Datei(en))"
else
    print_warning "js/ Verzeichnis nicht gefunden [kann optional sein]"
fi

# Prüfe ob lib/ oder acp/ vorhanden sind (falls erwartet)
if [ -d "temp_edit/lib" ] || [ -d "temp_edit/acp" ]; then
    print_success "Plugin-Verzeichnisse (lib/ oder acp/) vorhanden"
else
    print_warning "Keine Plugin-Verzeichnisse (lib/ oder acp/) gefunden [kann optional sein]"
fi

# Frontend-Templates: kanonisch templates/, Legacy Root-*.tpl
if [ -d "temp_edit/templates" ]; then
    TPL_COUNT=$(find temp_edit/templates -maxdepth 1 -name "*.tpl" 2>/dev/null | wc -l)
    print_success "templates/ vorhanden (${TPL_COUNT} Datei(en))"
elif ls temp_edit/*.tpl 1>/dev/null 2>&1; then
    print_warning "Root-*.tpl gefunden — nach templates/ verschieben (WoltLab-Norm)"
fi

# Prüfe ob XML-Dateien vorhanden sind (PIP-XMLs bleiben im Root)
XML_COUNT=$(find temp_edit -maxdepth 1 -name "*.xml" 2>/dev/null | wc -l)
if [ "$XML_COUNT" -gt 0 ]; then
    print_success "${XML_COUNT} XML-Datei(en) im Root gefunden (PIP-XMLs)"
else
    print_warning "Keine XML-Dateien im Root gefunden [kann optional sein]"
fi

if [ "$VALIDATION_ERRORS" -gt 0 ]; then
    print_error "Validierung fehlgeschlagen: ${VALIDATION_ERRORS} Fehler gefunden"
    exit 1
fi

print_success "Entpacken abgeschlossen!"
print_info "Verzeichnis: temp_edit/"
echo ""
