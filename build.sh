#!/bin/bash

#################################################################
# WoltLab Flexible Multi-Plugin Builder
# Pfad: /home/benny/Dokumente/affiliate-plugin/build.sh
# 
# Usage:
#   ./build.sh                  → baut beide (basis → mein)
#   ./build.sh both             → baut beide
#   ./build.sh basis            → baut nur basis-plugin
#   ./build.sh mein             → baut nur mein-plugin
#   ./build.sh basis minor      → baut basis-plugin mit Minor-Version erhöhen
#   ./build.sh both patch       → baut beide Plugins mit Patch-Version
#################################################################

set -e

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${SCRIPT_DIR}"

# Plugin-Quellen-Verzeichnisse
BASIS_SOURCE_DIR="${SCRIPT_DIR}/basis-plugin"
MEIN_SOURCE_DIR="${SCRIPT_DIR}/mein-plugin"

# Ziel-Verzeichnisse (fertige Pakete)
BASIS_TARGET_DIR="${SCRIPT_DIR}/basis-plugin"
MEIN_TARGET_DIR="${SCRIPT_DIR}/mein-plugin"

# Parameter parsen
TARGET="${1:-both}"
VERSION_TYPE="${2:-patch}"

# Validierung
if [[ ! "$TARGET" =~ ^(basis|mein|both)$ ]]; then
    echo -e "${RED}❌ Fehler: Ungültiges Ziel '$TARGET'${NC}"
    echo "Verwendung: ./build.sh [basis|mein|both] [patch|minor|major]"
    exit 1
fi

if [[ ! "$VERSION_TYPE" =~ ^(patch|minor|major)$ ]]; then
    echo -e "${RED}❌ Fehler: Ungültiger Version-Typ '$VERSION_TYPE'${NC}"
    echo "Verwendung: ./build.sh [basis|mein|both] [patch|minor|major]"
    exit 1
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}WoltLab Flexible Builder${NC}"
echo -e "${GREEN}Ziel: $TARGET | Version: $VERSION_TYPE${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Funktion: Plugin bauen
build_plugin() {
    local source_dir="$1"
    local target_dir="$2"
    local name="$3"

    if [ ! -d "$source_dir" ]; then
        echo -e "${RED}❌ Fehler: Verzeichnis nicht gefunden: $source_dir${NC}"
    exit 1
fi

echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}Baue $name...${NC}"
echo -e "${YELLOW}========================================${NC}\n"

    cd "$source_dir"

    # Prüfe ob build.sh existiert
    if [ ! -f "./build.sh" ]; then
        echo -e "${RED}❌ Fehler: build.sh nicht gefunden in $source_dir${NC}"
    exit 1
fi

    # Plugin bauen
    ./build.sh "$VERSION_TYPE"

    # Version aus package.xml lesen
    local version="unknown"
    if [ -f "./package.xml" ]; then
        version=$(grep -oP '<version>\K[^<]+' "./package.xml" 2>/dev/null || echo "unknown")
    elif [ -f "./_extracted/package.xml" ]; then
        version=$(grep -oP '<version>\K[^<]+' "./_extracted/package.xml" 2>/dev/null || echo "unknown")
    fi

    # Package-Name aus package.xml lesen
    local package_name=""
    if [ -f "./package.xml" ]; then
        package_name=$(grep -oP '<package name="\K[^"]+' "./package.xml" | head -1)
    elif [ -f "./_extracted/package.xml" ]; then
        package_name=$(grep -oP '<package name="\K[^"]+' "./_extracted/package.xml" | head -1)
    fi

    # Fertige .tar.gz Datei finden und kopieren
    local tar_file=""
    if [ -n "$package_name" ]; then
        # Suche nach Dateien mit Package-Name und Version
        tar_file=$(ls "${package_name}"_v*.tar.gz 2>/dev/null | head -1)
        if [ -z "$tar_file" ]; then
            # Fallback: Suche nach allen .tar.gz Dateien
            tar_file=$(ls *.tar.gz 2>/dev/null | head -1)
        fi
    else
        # Fallback: Suche nach allen .tar.gz Dateien
        tar_file=$(ls *.tar.gz 2>/dev/null | head -1)
    fi

    if [ -n "$tar_file" ] && [ -f "$tar_file" ]; then
        # Ziel-Verzeichnis erstellen falls nicht vorhanden
        mkdir -p "$target_dir"

        # Datei nach Ziel-Verzeichnis kopieren
        cp "$tar_file" "$target_dir/"
        echo -e "${GREEN}✓ $name erfolgreich gebaut: $tar_file (Version: $version)${NC}"
        echo -e "${GREEN}  → Kopiert nach: $target_dir/$tar_file${NC}\n"

        # Aufräumen: Nur letzte 5 Versionen behalten
        cd "$target_dir"
        local keep_count=5
        local package_pattern="${package_name}_v*.tar.gz"
        
        if [ -n "$package_name" ] && ls ${package_pattern} 1> /dev/null 2>&1; then
            local total_count=$(ls -t ${package_pattern} 2>/dev/null | wc -l)
            if [ "$total_count" -gt "$keep_count" ]; then
                local old_count=$((total_count - keep_count))
                echo -e "${YELLOW}  Aufräumen: ${total_count} Pakete gefunden, entferne ${old_count} älteste...${NC}"
                ls -t ${package_pattern} | tail -n +$((keep_count + 1)) | while read -r old_package; do
                    rm -v "$old_package"
                done
                echo -e "${GREEN}  ✓ Aufräumen abgeschlossen: ${keep_count} Pakete behalten${NC}\n"
            fi
        fi
    else
        echo -e "${YELLOW}⚠ Keine .tar.gz Datei gefunden in $source_dir${NC}"
        echo -e "${YELLOW}  Möglicherweise wurde das Plugin nicht korrekt gebaut${NC}\n"
    fi

    cd "${SCRIPT_DIR}"
}

# Build ausführen
case "$TARGET" in
    basis)
        build_plugin "$BASIS_SOURCE_DIR" "$BASIS_TARGET_DIR" "basis-plugin"
        ;;
    mein)
        build_plugin "$MEIN_SOURCE_DIR" "$MEIN_TARGET_DIR" "mein-plugin"
        ;;
    both|"")
        build_plugin "$BASIS_SOURCE_DIR" "$BASIS_TARGET_DIR" "basis-plugin"
        build_plugin "$MEIN_SOURCE_DIR" "$MEIN_TARGET_DIR" "mein-plugin"
        ;;
    *)
        echo -e "${RED}❌ Unbekanntes Ziel: $TARGET${NC}"
        echo "Verwendung: ./build.sh [basis|mein|both] [patch|minor|major]"
        exit 1
        ;;
esac

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Build abgeschlossen!${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Optional: Automatischer Push
if [ -f "${SCRIPT_DIR}/gitpush.sh" ]; then
    read -p "Soll jetzt gitpush.sh ausgeführt werden? (j/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Jj]$ ]]; then
        ./gitpush.sh "$TARGET"
    fi
fi
