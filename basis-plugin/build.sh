#!/bin/bash

#################################################################
# WoltLab Plugin Builder für Shr1nkr
# Pfad: /home/benny/Dokumente/affiliate-plugin/basis-plugin/build.sh
# 
# Usage:
#   ./build.sh patch   → Patch-Version erhöhen (Standard)
#   ./build.sh minor   → Minor-Version erhöhen
#   ./build.sh major   → Major-Version erhöhen
#################################################################

set -e

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${SCRIPT_DIR}"

# Parameter parsen
VERSION_TYPE="${1:-patch}"

# Validierung
if [[ ! "$VERSION_TYPE" =~ ^(patch|minor|major)$ ]]; then
    echo -e "${RED}❌ Fehler: Ungültiger Version-Typ '$VERSION_TYPE'${NC}"
    echo "Verwendung: ./build.sh [patch|minor|major]"
    exit 1
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}WoltLab Plugin Builder - Shr1nkr${NC}"
echo -e "${GREEN}Version-Typ: $VERSION_TYPE${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Prüfe ob temp_edit existiert
if [ ! -d "temp_edit" ]; then
    echo -e "${RED}❌ Fehler: temp_edit Ordner nicht gefunden${NC}"
    echo -e "${YELLOW}  Bitte entpacke zuerst die TARs:${NC}"
    echo -e "${YELLOW}  rm -rf temp_edit && mkdir temp_edit${NC}"
    echo -e "${YELLOW}  tar -xf files.tar -C temp_edit${NC}"
    echo -e "${YELLOW}  tar -xf templates.tar -C temp_edit${NC}"
    echo -e "${YELLOW}  tar -xf acptemplates.tar -C temp_edit${NC}"
    exit 1
fi

# Version aus package.xml lesen
if [ ! -f "package.xml" ]; then
    echo -e "${RED}❌ Fehler: package.xml nicht gefunden${NC}"
    exit 1
fi

CURRENT_VERSION=$(grep -oP '<version>\K[^<]+' "package.xml" 2>/dev/null || echo "")
if [ -z "$CURRENT_VERSION" ]; then
    echo -e "${RED}❌ Fehler: Version nicht in package.xml gefunden${NC}"
    exit 1
fi

echo -e "${YELLOW}Aktuelle Version: $CURRENT_VERSION${NC}"

# Version erhöhen
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR="${VERSION_PARTS[0]}"
MINOR="${VERSION_PARTS[1]}"
PATCH="${VERSION_PARTS[2]}"

case "$VERSION_TYPE" in
    patch)
        PATCH=$((PATCH + 1))
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
esac

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"
TODAY=$(date +%Y-%m-%d)

echo -e "${YELLOW}Neue Version: $NEW_VERSION${NC}\n"

# Version und Datum in package.xml aktualisieren
sed -i "s/<version>${CURRENT_VERSION}<\/version>/<version>${NEW_VERSION}<\/version>/" package.xml
sed -i "s/<date>[^<]*<\/date>/<date>${TODAY}<\/date>/" package.xml

echo -e "${GREEN}✓ package.xml aktualisiert${NC}"

# TARs aus temp_edit neu erstellen
echo -e "${YELLOW}[1/3] Packe TARs aus temp_edit...${NC}"

cd temp_edit

# files.tar erstellen (lib/, acp/, style/, PHP-Dateien, aber keine Templates)
FILES_TO_PACK=""
[ -d "lib" ] && FILES_TO_PACK="${FILES_TO_PACK} lib/"
[ -d "acp" ] && FILES_TO_PACK="${FILES_TO_PACK} acp/"
[ -d "style" ] && FILES_TO_PACK="${FILES_TO_PACK} style/"
[ -f "global.php" ] && FILES_TO_PACK="${FILES_TO_PACK} global.php"
[ -f "index.php" ] && FILES_TO_PACK="${FILES_TO_PACK} index.php"

if [ -n "$FILES_TO_PACK" ]; then
    tar -cf ../files.tar $FILES_TO_PACK
    echo -e "${GREEN}✓ files.tar erstellt${NC}"
else
    echo -e "${YELLOW}⚠ Keine Dateien für files.tar gefunden${NC}"
fi

# templates.tar erstellen (Dateien direkt im Root, keine Verzeichnisse!)
if [ -d "templates" ]; then
    cd templates
    if ls *.tpl 1> /dev/null 2>&1; then
        tar -cf ../../templates.tar *.tpl
        echo -e "${GREEN}✓ templates.tar erstellt (aus templates/*.tpl)${NC}"
    else
        echo -e "${YELLOW}⚠ Keine .tpl Dateien in templates/ gefunden${NC}"
    fi
    cd ..
elif ls *.tpl 1> /dev/null 2>&1; then
    # Templates liegen direkt im temp_edit
    tar -cf ../templates.tar *.tpl
    echo -e "${GREEN}✓ templates.tar erstellt (aus *.tpl)${NC}"
else
    echo -e "${YELLOW}⚠ Keine Templates gefunden${NC}"
fi

# acptemplates.tar erstellen (Dateien direkt im Root, keine Verzeichnisse!)
if [ -d "acptemplates" ]; then
    cd acptemplates
    if ls *.tpl 1> /dev/null 2>&1; then
        tar -cf ../../acptemplates.tar *.tpl
        echo -e "${GREEN}✓ acptemplates.tar erstellt${NC}"
    else
        echo -e "${YELLOW}⚠ Keine .tpl Dateien in acptemplates/ gefunden${NC}"
    fi
    cd ..
else
    echo -e "${YELLOW}⚠ Kein acptemplates/ Ordner gefunden${NC}"
fi

# files_wcf.tar erstellen (JavaScript-Dateien für WCF)
if [ -d "js" ]; then
    tar -cf ../files_wcf.tar js/
    echo -e "${GREEN}✓ files_wcf.tar erstellt${NC}"
else
    echo -e "${YELLOW}⚠ Kein js/ Ordner gefunden für files_wcf.tar${NC}"
fi

cd ..

echo -e "${GREEN}✓ TARs erfolgreich erstellt${NC}\n"

# Package-Name aus package.xml lesen
PACKAGE_NAME=$(grep -oP '<package name="\K[^"]+' "package.xml" | head -1)
if [ -z "$PACKAGE_NAME" ]; then
    echo -e "${RED}❌ Fehler: Package-Name nicht in package.xml gefunden${NC}"
    exit 1
fi

# Finales .tar.gz Paket erstellen
echo -e "${YELLOW}[2/3] Erstelle finales Paket...${NC}"

TAR_GZ_NAME="${PACKAGE_NAME}_v${NEW_VERSION}.tar.gz"

# Temporäres Verzeichnis für Paket-Erstellung
TEMP_PACKAGE_DIR=$(mktemp -d)
trap "rm -rf ${TEMP_PACKAGE_DIR}" EXIT

# Alle Dateien ins temporäre Verzeichnis kopieren
cp package.xml "${TEMP_PACKAGE_DIR}/"
cp *.tar "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true
cp *.xml "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true
# eventListener.xml aus temp_edit/ kopieren, falls vorhanden
[ -f "temp_edit/eventListener.xml" ] && cp temp_edit/eventListener.xml "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true
cp -r language "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true

# .tar.gz erstellen (Dateien direkt ohne ./ Prefix)
cd "${TEMP_PACKAGE_DIR}"
# Alle Dateien explizit auflisten, um ./ Prefix zu vermeiden
tar -czf "${SCRIPT_DIR}/${TAR_GZ_NAME}" *

cd "${SCRIPT_DIR}"

echo -e "${GREEN}✓ Paket erstellt: ${TAR_GZ_NAME}${NC}\n"

# Aufräumen: Nur letzte 5 Versionen behalten
echo -e "${YELLOW}[3/3] Räume alte Pakete auf...${NC}"
KEEP_COUNT=5
PACKAGE_PATTERN="${PACKAGE_NAME}_v*.tar.gz"

if ls ${PACKAGE_PATTERN} 1> /dev/null 2>&1; then
    TOTAL_COUNT=$(ls -t ${PACKAGE_PATTERN} 2>/dev/null | wc -l)
    if [ "$TOTAL_COUNT" -gt "$KEEP_COUNT" ]; then
        OLD_COUNT=$((TOTAL_COUNT - KEEP_COUNT))
        echo -e "${YELLOW}  ${TOTAL_COUNT} Pakete gefunden, entferne ${OLD_COUNT} älteste...${NC}"
        ls -t ${PACKAGE_PATTERN} | tail -n +$((KEEP_COUNT + 1)) | while read -r old_package; do
            rm -v "$old_package"
        done
        echo -e "${GREEN}  ✓ Aufräumen abgeschlossen: ${KEEP_COUNT} Pakete behalten${NC}\n"
    else
        echo -e "${GREEN}  ✓ ${TOTAL_COUNT} Pakete vorhanden (kein Aufräumen nötig)${NC}\n"
    fi
fi

# Auto-Deploy zu DDEV (optional)
WOLTLAB_UPLOADS="/home/benny/Dokumente/affiliate-plugin/woltlab-dev/public/uploads"
if [ -d "$WOLTLAB_UPLOADS" ]; then
    cp "${TAR_GZ_NAME}" "$WOLTLAB_UPLOADS/"
    echo -e "${GREEN}✓ Plugin nach DDEV kopiert: ${WOLTLAB_UPLOADS}/${TAR_GZ_NAME}${NC}"
    echo -e "${YELLOW}  → Installieren Sie es im ACP unter System → Paket-Verwaltung${NC}\n"
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Build abgeschlossen!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Version: ${NEW_VERSION}${NC}"
echo -e "${GREEN}Paket: ${TAR_GZ_NAME}${NC}"
echo -e "${GREEN}========================================${NC}\n"
