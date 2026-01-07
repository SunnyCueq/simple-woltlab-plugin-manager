#!/bin/bash

#################################################################
# WoltLab Package Builder Script
# Package: info.benjaro.urlshort.affiliate
# Author: Sunny C.
# 
# Integriert Vorteile aus simple-woltlab-plugin-manager:
# - Automatische package.xml-Validierung
# - Package-Struktur-Erkennung
# - TypeScript-Support (eigene Erweiterung)
# - Automatische Versionserhöhung
#
# Usage:
#   ./build.sh          # Patch-Version erhöhen (1.0.0 -> 1.0.1)
#   ./build.sh patch    # Patch-Version erhöhen (1.0.0 -> 1.0.1)
#   ./build.sh minor    # Minor-Version erhöhen (1.0.0 -> 1.1.0)
#   ./build.sh major    # Major-Version erhöhen (1.0.0 -> 2.0.0)
#################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Package information
PACKAGE_NAME="info.benjaro.urlshort.affiliate"
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BUILD_DIR="${SCRIPT_DIR}/_build_temp"
SRC_DIR="${SCRIPT_DIR}/_extracted"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}WoltLab Package Builder${NC}"
echo -e "${GREEN}Package: ${PACKAGE_NAME}${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Validierung 1: Prüfe ob package.xml existiert
if [ ! -f "${SCRIPT_DIR}/package.xml" ]; then
    echo -e "${RED}❌ Fehler: package.xml nicht gefunden${NC}"
    exit 1
fi

# Validierung 2: XML-Syntax prüfen (aus simple-woltlab-plugin-manager)
echo -e "${YELLOW}[0/8] Validating package.xml...${NC}"
if command -v xmllint &> /dev/null; then
    if ! xmllint --noout "${SCRIPT_DIR}/package.xml" 2>/dev/null; then
        echo -e "${RED}✗ package.xml hat XML-Syntax-Fehler!${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ XML-Syntax OK${NC}"
else
    echo -e "${YELLOW}⚠ xmllint nicht gefunden, überspringe XML-Validierung${NC}"
fi

# Read version from package.xml
CURRENT_VERSION=$(grep -oP '<version>\K[^<]+' "${SCRIPT_DIR}/package.xml")
echo -e "${YELLOW}Current Version: ${CURRENT_VERSION}${NC}"

# Auto-increment version
# Parameter: patch (default), minor, major
VERSION_TYPE="${1:-patch}"

increment_version() {
    local version=$1
    local type=$2
    
    # Split version into parts
    IFS='.' read -ra VERSION_PARTS <<< "$version"
    local major=${VERSION_PARTS[0]:-0}
    local minor=${VERSION_PARTS[1]:-0}
    local patch=${VERSION_PARTS[2]:-0}
    
    case "$type" in
        major)
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        minor)
            minor=$((minor + 1))
            patch=0
            ;;
        patch|*)
            patch=$((patch + 1))
            ;;
    esac
    
    echo "${major}.${minor}.${patch}"
}

NEW_VERSION=$(increment_version "$CURRENT_VERSION" "$VERSION_TYPE")

# Update version in package.xml
if [ "$CURRENT_VERSION" != "$NEW_VERSION" ]; then
    echo -e "${YELLOW}Incrementing version: ${CURRENT_VERSION} → ${NEW_VERSION} (${VERSION_TYPE})${NC}"
    
    # Update version in package.xml (works with tabs/spaces)
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        sed -i '' "s/<version>${CURRENT_VERSION}<\/version>/<version>${NEW_VERSION}<\/version>/" "${SCRIPT_DIR}/package.xml"
    else
        # Linux
        sed -i "s/<version>${CURRENT_VERSION}<\/version>/<version>${NEW_VERSION}<\/version>/" "${SCRIPT_DIR}/package.xml"
    fi
    
    # Update date to today
    TODAY=$(date +%Y-%m-%d)
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        sed -i '' "s/<date>[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}<\/date>/<date>${TODAY}<\/date>/" "${SCRIPT_DIR}/package.xml"
    else
        # Linux
        sed -i "s/<date>[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}<\/date>/<date>${TODAY}<\/date>/" "${SCRIPT_DIR}/package.xml"
    fi
    
    echo -e "${GREEN}✓ Version updated in package.xml${NC}"
else
    echo -e "${YELLOW}⚠ Version unchanged${NC}"
fi

VERSION=$NEW_VERSION
echo ""

# Clean build directory
echo -e "${YELLOW}[1/8] Cleaning build directory...${NC}"
rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}"

# Compile TypeScript to JavaScript
echo -e "${YELLOW}[2/8] Compiling TypeScript to JavaScript...${NC}"

# Check if TypeScript files exist
TS_COUNT=0
if [ -d "${SRC_DIR}/ts" ]; then
    TS_COUNT=$(find "${SRC_DIR}/ts" -name "*.ts" 2>/dev/null | wc -l)
fi

if [ "$TS_COUNT" -eq 0 ]; then
    echo -e "${YELLOW}⚠ No TypeScript files found, skipping compilation${NC}"
elif [ -f "${SCRIPT_DIR}/tsconfig.json" ]; then
    # Try global tsc first, then npx tsc (local installation)
    if command -v tsc &> /dev/null; then
        TSC_CMD="tsc"
    elif command -v npx &> /dev/null && [ -f "${SCRIPT_DIR}/node_modules/.bin/tsc" ]; then
        TSC_CMD="npx tsc"
    elif [ -f "${SCRIPT_DIR}/node_modules/.bin/tsc" ]; then
        TSC_CMD="${SCRIPT_DIR}/node_modules/.bin/tsc"
    else
        echo -e "${RED}❌ TypeScript compiler not found!${NC}"
        echo -e "${RED}   Install with: npm install typescript${NC}"
        echo -e "${RED}   Build aborted - JavaScript files might be outdated!${NC}"
        exit 1
    fi

    if [ -n "$TSC_CMD" ]; then
        # Compile from _extracted/ts/ to _extracted/files/js/
        $TSC_CMD --project "${SCRIPT_DIR}/tsconfig.json"
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ TypeScript compiled successfully (from _extracted/ts/ to _extracted/files/js/)${NC}"

            # Validierung 3: Prüfe ob JavaScript-Dateien existieren
            JS_COUNT=$(find "${SRC_DIR}/files/js" -name "*.js" 2>/dev/null | wc -l)

            if [ "$TS_COUNT" -gt 0 ] && [ "$JS_COUNT" -eq 0 ]; then
                echo -e "${RED}❌ TypeScript-Dateien gefunden, aber keine JavaScript-Dateien!${NC}"
                echo -e "${RED}   Compilation scheint fehlgeschlagen zu sein.${NC}"
                exit 1
            fi

            echo -e "${GREEN}✓ Validation: ${TS_COUNT} TypeScript → ${JS_COUNT} JavaScript files${NC}"
        else
            echo -e "${RED}❌ TypeScript compilation failed!${NC}"
            echo -e "${RED}   Build aborted - fix TypeScript errors first!${NC}"
            exit 1
        fi
    fi
else
    echo -e "${YELLOW}⚠ tsconfig.json not found, skipping TypeScript compilation${NC}"
    echo -e "${YELLOW}   Warning: If TypeScript files exist, JavaScript might be outdated!${NC}"
fi

# Create TAR archives
echo -e "${YELLOW}[3/8] Creating files.tar (Frontend Assets)...${NC}"
if [ -d "${SRC_DIR}/files" ]; then
    cd "${SRC_DIR}/files"
    # Include JavaScript (js/) from _extracted/files/js/
    tar -cf "${BUILD_DIR}/files.tar" ./*
    cd "${SCRIPT_DIR}"
    echo -e "${GREEN}✓ files.tar created (JavaScript from _extracted/files/js/ included as files/js/)${NC}"
    
    # Also include TypeScript from _extracted/ts/ into files.tar as ts/
    if [ -d "${SRC_DIR}/ts" ]; then
        cd "${SRC_DIR}"
        # Add TypeScript (WoltLab standard: both ts/ and files/js/ in package)
        tar --append -f "${BUILD_DIR}/files.tar" ts/
        cd "${SCRIPT_DIR}"
        echo -e "${GREEN}✓ TypeScript (ts/) added to files.tar${NC}"
    fi
else
    echo -e "${RED}✗ Directory ${SRC_DIR}/files not found${NC}"
fi

echo -e "${YELLOW}[4/8] Creating files_urlshort.tar (Application Files)...${NC}"
if [ -d "${SRC_DIR}/files_urlshort" ]; then
    cd "${SRC_DIR}/files_urlshort"
    # WICHTIG: Verwende . statt ./* um auch versteckte Dateien zu inkludieren
    # Und stelle sicher, dass Pfade relativ sind (ohne führendes ./)
    tar -cf "${BUILD_DIR}/files_urlshort.tar" --transform 's,^\./,,' .
    cd "${SCRIPT_DIR}"
    echo -e "${GREEN}✓ files_urlshort.tar created${NC}"
else
    echo -e "${RED}✗ Directory ${SRC_DIR}/files_urlshort not found${NC}"
fi

echo -e "${YELLOW}[4.5/8] Creating files_wcf.tar (WCF Files - Uninstall Script)...${NC}"
if [ -d "${SRC_DIR}/files_wcf" ]; then
    cd "${SRC_DIR}/files_wcf"
    # WICHTIG: Verwende . statt ./* um auch versteckte Dateien zu inkludieren
    # Und stelle sicher, dass Pfade relativ sind (ohne führendes ./)
    tar -cf "${BUILD_DIR}/files_wcf.tar" --transform 's,^\./,,' .
    cd "${SCRIPT_DIR}"
    echo -e "${GREEN}✓ files_wcf.tar created${NC}"
else
    echo -e "${YELLOW}⚠ Directory ${SRC_DIR}/files_wcf not found (optional)${NC}"
fi

echo -e "${YELLOW}[5/8] Creating templates_urlshort.tar (Templates)...${NC}"
if [ -d "${SRC_DIR}/templates_urlshort" ]; then
    cd "${SRC_DIR}/templates_urlshort"
    # WICHTIG: Verwende . statt ./* um auch versteckte Dateien zu inkludieren
    # Und stelle sicher, dass Pfade relativ sind (ohne führendes ./)
    tar -cf "${BUILD_DIR}/templates_urlshort.tar" --transform 's,^\./,,' .
    cd "${SCRIPT_DIR}"
    echo -e "${GREEN}✓ templates_urlshort.tar created${NC}"
else
    echo -e "${RED}✗ Directory ${SRC_DIR}/templates_urlshort not found${NC}"
fi

echo -e "${YELLOW}[6/8] Creating acptemplates_urlshort.tar (ACP Templates)...${NC}"
if [ -d "${SRC_DIR}/acptemplates_urlshort" ]; then
    cd "${SRC_DIR}/acptemplates_urlshort"
    # WICHTIG: Verwende . statt ./* um auch versteckte Dateien zu inkludieren
    # Und stelle sicher, dass Pfade relativ sind (ohne führendes ./)
    tar -cf "${BUILD_DIR}/acptemplates_urlshort.tar" --transform 's,^\./,,' .
    cd "${SCRIPT_DIR}"
    echo -e "${GREEN}✓ acptemplates_urlshort.tar created${NC}"
else
    echo -e "${RED}✗ Directory ${SRC_DIR}/acptemplates_urlshort not found${NC}"
fi

# Copy TAR archives to root directory
echo -e "${YELLOW}[7/8] Copying TAR archives to root...${NC}"
cp "${BUILD_DIR}"/*.tar "${SCRIPT_DIR}/" 2>/dev/null || true
echo -e "${GREEN}✓ TAR archives copied to root${NC}"

# Sync language/ from _extracted/ to root (für Bearbeitung)
echo -e "${YELLOW}[8/8] Syncing language/ from _extracted/ to root...${NC}"
if [ -d "${SRC_DIR}/language" ]; then
    # Kopiere language/ von _extracted/ nach root (für Package)
    # Root hat die finale Package-Struktur, _extracted/ ist für Bearbeitung
    rm -rf "${SCRIPT_DIR}/language"
    cp -r "${SRC_DIR}/language" "${SCRIPT_DIR}/language"
    echo -e "${GREEN}✓ language/ synced from _extracted/ to root${NC}"
else
    echo -e "${YELLOW}⚠ language/ not found in _extracted/, using root version${NC}"
fi

# Create final package from root directory
echo -e "${YELLOW}[9/8] Creating final package archive...${NC}"
cd "${SCRIPT_DIR}"
PACKAGE_FILE="${PACKAGE_NAME}_v${VERSION}.tar.gz"

# Package structure: *.xml, *.tar, and language/ (from root)
# Root hat die finale Package-Struktur (language/ ist hier)
tar -czf "${PACKAGE_FILE}" \
    *.xml \
    *.tar \
    language/ 2>/dev/null || \
tar -czf "${PACKAGE_FILE}" \
    *.xml \
    *.tar

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Build completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Package: ${PACKAGE_FILE}${NC}"
echo -e "${GREEN}Location: ${SCRIPT_DIR}/${PACKAGE_FILE}${NC}"
echo -e "${GREEN}Version: ${VERSION}${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Show package contents (aus simple-woltlab-plugin-manager)
echo -e "${YELLOW}Package contents:${NC}"
tar -tf "${PACKAGE_FILE}" | head -20
if [ $(tar -tf "${PACKAGE_FILE}" | wc -l) -gt 20 ]; then
    echo "... (and more files)"
fi

# Cleanup old packages: Keep only 5 most recent packages
echo -e "\n${YELLOW}[Cleanup] Cleaning up old packages...${NC}"
KEEP_COUNT=5
PACKAGE_PATTERN="${PACKAGE_NAME}_v*.tar.gz"

if ls ${PACKAGE_PATTERN} 1> /dev/null 2>&1; then
    TOTAL_COUNT=$(ls -t ${PACKAGE_PATTERN} 2>/dev/null | wc -l)
    if [ "$TOTAL_COUNT" -gt "$KEEP_COUNT" ]; then
        OLD_COUNT=$((TOTAL_COUNT - KEEP_COUNT))
        echo -e "${YELLOW}Found ${TOTAL_COUNT} packages, removing ${OLD_COUNT} oldest...${NC}"
        ls -t ${PACKAGE_PATTERN} | tail -n +$((KEEP_COUNT + 1)) | while read -r old_package; do
            rm -v "$old_package"
        done
        echo -e "${GREEN}✓ Cleanup completed: ${KEEP_COUNT} packages kept${NC}"
    else
        echo -e "${GREEN}✓ No cleanup needed: ${TOTAL_COUNT} packages (≤ ${KEEP_COUNT} limit)${NC}"
    fi
else
    echo -e "${YELLOW}⚠ No packages found matching pattern${NC}"
fi

# Auto-push nach Build (falls gitpush.sh existiert)
echo ""
if [ -f "./gitpush.sh" ]; then
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}Automatischer Git Push...${NC}"
    echo -e "${YELLOW}========================================${NC}"
    ./gitpush.sh "v${VERSION} - Auto-commit nach Build"
else
    echo -e "${YELLOW}⚠ gitpush.sh nicht gefunden - überspringe Auto-Push${NC}"
fi
