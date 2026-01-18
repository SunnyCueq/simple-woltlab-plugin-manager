#!/bin/bash
# Test-Script: Prüft alle Pfade in den Tools

set -e

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$TOOLS_DIR")"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=== Pfad-Validierung ==="
echo ""

ERRORS=0

# Test 1: Verzeichnisse im Hauptverzeichnis
echo "1. Hauptverzeichnis-Verzeichnisse:"
test -d "$MAIN_DIR/basis-plugin" && echo -e "  ${GREEN}✓${NC} basis-plugin" || { echo -e "  ${RED}✗${NC} basis-plugin fehlt"; ERRORS=$((ERRORS+1)); }
test -d "$MAIN_DIR/mein-plugin" && echo -e "  ${GREEN}✓${NC} mein-plugin" || { echo -e "  ${RED}✗${NC} mein-plugin fehlt"; ERRORS=$((ERRORS+1)); }
test -d "$MAIN_DIR/woltlab-core" && echo -e "  ${GREEN}✓${NC} woltlab-core" || { echo -e "  ${YELLOW}⚠${NC} woltlab-core fehlt (optional)"; }
echo ""

# Test 2: Verzeichnisse im tools-Ordner
echo "2. Tools-Verzeichnisse:"
test -d "$TOOLS_DIR/woltlab-dev" && echo -e "  ${GREEN}✓${NC} tools/woltlab-dev" || { echo -e "  ${RED}✗${NC} tools/woltlab-dev fehlt"; ERRORS=$((ERRORS+1)); }
test -d "$TOOLS_DIR/woltlab-snapshot" && echo -e "  ${GREEN}✓${NC} tools/woltlab-snapshot" || { echo -e "  ${RED}✗${NC} tools/woltlab-snapshot fehlt"; ERRORS=$((ERRORS+1)); }
test -d "$TOOLS_DIR/woltlab-snapshot-tools" && echo -e "  ${GREEN}✓${NC} tools/woltlab-snapshot-tools" || { echo -e "  ${RED}✗${NC} tools/woltlab-snapshot-tools fehlt"; ERRORS=$((ERRORS+1)); }
echo ""

# Test 3: Scripts
echo "3. Scripts:"
test -f "$TOOLS_DIR/build.sh" && echo -e "  ${GREEN}✓${NC} build.sh" || { echo -e "  ${RED}✗${NC} build.sh fehlt"; ERRORS=$((ERRORS+1)); }
test -f "$TOOLS_DIR/gitpush.sh" && echo -e "  ${GREEN}✓${NC} gitpush.sh" || { echo -e "  ${RED}✗${NC} gitpush.sh fehlt"; ERRORS=$((ERRORS+1)); }
test -f "$TOOLS_DIR/start-ddev.sh" && echo -e "  ${GREEN}✓${NC} start-ddev.sh" || { echo -e "  ${RED}✗${NC} start-ddev.sh fehlt"; ERRORS=$((ERRORS+1)); }
test -f "$TOOLS_DIR/restore-snapshot.sh" && echo -e "  ${GREEN}✓${NC} restore-snapshot.sh" || { echo -e "  ${RED}✗${NC} restore-snapshot.sh fehlt"; ERRORS=$((ERRORS+1)); }
test -f "$TOOLS_DIR/tools.sh" && echo -e "  ${GREEN}✓${NC} tools.sh" || { echo -e "  ${RED}✗${NC} tools.sh fehlt"; ERRORS=$((ERRORS+1)); }
test -f "$TOOLS_DIR/woltlab-dev/start.sh" && echo -e "  ${GREEN}✓${NC} woltlab-dev/start.sh" || { echo -e "  ${RED}✗${NC} woltlab-dev/start.sh fehlt"; ERRORS=$((ERRORS+1)); }
test -f "$TOOLS_DIR/woltlab-snapshot-tools/restore.sh" && echo -e "  ${GREEN}✓${NC} woltlab-snapshot-tools/restore.sh" || { echo -e "  ${RED}✗${NC} woltlab-snapshot-tools/restore.sh fehlt"; ERRORS=$((ERRORS+1)); }
test -f "$TOOLS_DIR/woltlab-snapshot-tools/snapshot.sh" && echo -e "  ${GREEN}✓${NC} woltlab-snapshot-tools/snapshot.sh" || { echo -e "  ${RED}✗${NC} woltlab-snapshot-tools/snapshot.sh fehlt"; ERRORS=$((ERRORS+1)); }
echo ""

# Test 4: Pfade in restore.sh (wenn von tools/restore-snapshot.sh aufgerufen)
echo "4. Pfade in restore.sh (relativ zu tools/woltlab-snapshot-tools/):"
RESTORE_TOOLS_DIR="$TOOLS_DIR/woltlab-snapshot-tools"
RESTORE_MAIN_DIR="$(dirname "$RESTORE_TOOLS_DIR")"
RESTORE_SNAPSHOT_DIR="$RESTORE_TOOLS_DIR/../woltlab-snapshot"
RESTORE_PUBLIC_DIR="$RESTORE_TOOLS_DIR/../woltlab-dev/public"

# Normalisiere Pfade
RESTORE_SNAPSHOT_DIR=$(cd "$RESTORE_SNAPSHOT_DIR" 2>/dev/null && pwd || echo "")
RESTORE_PUBLIC_DIR=$(cd "$(dirname "$RESTORE_PUBLIC_DIR")" 2>/dev/null && pwd && echo "/public" || echo "")

if [ -d "$RESTORE_SNAPSHOT_DIR" ]; then
    echo -e "  ${GREEN}✓${NC} SNAPSHOT_DIR: $RESTORE_SNAPSHOT_DIR"
else
    echo -e "  ${RED}✗${NC} SNAPSHOT_DIR: $RESTORE_SNAPSHOT_DIR (fehlt)"; ERRORS=$((ERRORS+1));
fi

if [ -d "$(dirname "$RESTORE_PUBLIC_DIR")" ]; then
    echo -e "  ${GREEN}✓${NC} PUBLIC_DIR: $RESTORE_PUBLIC_DIR"
else
    echo -e "  ${RED}✗${NC} PUBLIC_DIR: $RESTORE_PUBLIC_DIR (fehlt)"; ERRORS=$((ERRORS+1));
fi
echo ""

# Test 5: Pfade in build.sh
echo "5. Pfade in build.sh:"
BUILD_TOOLS_DIR="$TOOLS_DIR"
BUILD_MAIN_DIR="$(dirname "$BUILD_TOOLS_DIR")"
BUILD_BASIS_DIR="$BUILD_MAIN_DIR/basis-plugin"
BUILD_MEIN_DIR="$BUILD_MAIN_DIR/mein-plugin"

if [ -d "$BUILD_BASIS_DIR" ]; then
    echo -e "  ${GREEN}✓${NC} BASIS_SOURCE_DIR: $BUILD_BASIS_DIR"
else
    echo -e "  ${RED}✗${NC} BASIS_SOURCE_DIR: $BUILD_BASIS_DIR (fehlt)"; ERRORS=$((ERRORS+1));
fi

if [ -d "$BUILD_MEIN_DIR" ]; then
    echo -e "  ${GREEN}✓${NC} MEIN_SOURCE_DIR: $BUILD_MEIN_DIR"
else
    echo -e "  ${RED}✗${NC} MEIN_SOURCE_DIR: $BUILD_MEIN_DIR (fehlt)"; ERRORS=$((ERRORS+1));
fi
echo ""

# Zusammenfassung
echo "=== Zusammenfassung ==="
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ Alle Pfade sind korrekt!${NC}"
    exit 0
else
    echo -e "${RED}✗ $ERRORS Fehler gefunden!${NC}"
    exit 1
fi
