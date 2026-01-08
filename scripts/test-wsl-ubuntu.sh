#!/bin/bash

# Test Script für WSL2 Ubuntu 24 Kompatibilität
# Copyright (c) 2025 SunnyCueq
# 
# Dieses Script testet das install.sh in einem Ubuntu 24 Container
# um WSL2-Kompatibilität sicherzustellen

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "═══════════════════════════════════════════════════════════════"
echo "  WSL2 Ubuntu 24 Kompatibilitätstest"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Dieses Script testet install.sh in einer Ubuntu 24 Umgebung"
echo "um WSL2-Kompatibilität zu gewährleisten."
echo ""

# Prüfe ob Docker/Podman verfügbar ist
CONTAINER_CMD=""
if command -v docker &> /dev/null; then
    CONTAINER_CMD="docker"
    echo "✓ Docker gefunden"
elif command -v podman &> /dev/null; then
    CONTAINER_CMD="podman"
    echo "✓ Podman gefunden"
else
    echo "❌ Fehler: Weder Docker noch Podman gefunden!"
    echo ""
    echo "Installation:"
    echo "  • Arch/CachyOS: sudo pacman -S docker"
    echo "  • Oder: sudo pacman -S podman"
    echo ""
    echo "Nach Installation:"
    echo "  • Docker: sudo systemctl start docker"
    echo "  • Podman: keine weiteren Schritte nötig"
    exit 1
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test-Optionen"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "1. Schnelltest (Syntax-Check)"
echo "2. Vollständiger Test (Interaktive Installation)"
echo "3. Automatischer Test (Non-Interactive)"
echo ""
read -p "Wähle eine Option (1-3): " -n 1 -r
echo ""
TEST_MODE=$REPLY

case $TEST_MODE in
    1)
        echo ""
        echo "🔍 Führe Syntax-Check aus..."
        echo ""
        
        # Erstelle temporären Container
        CONTAINER_ID=$($CONTAINER_CMD run -d ubuntu:24.04 sleep 3600)
        
        # Kopiere install.sh
        $CONTAINER_CMD cp "$PROJECT_ROOT/install.sh" "$CONTAINER_ID:/tmp/install.sh"
        
        # Bash Syntax-Check
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "Bash Syntax-Check"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        if $CONTAINER_CMD exec "$CONTAINER_ID" bash -n /tmp/install.sh; then
            echo "✅ Syntax-Check erfolgreich!"
        else
            echo "❌ Syntax-Fehler gefunden!"
            $CONTAINER_CMD rm -f "$CONTAINER_ID" &>/dev/null || true
            exit 1
        fi
        
        echo ""
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "ShellCheck (falls verfügbar)"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        
        # Versuche shellcheck zu installieren und auszuführen
        $CONTAINER_CMD exec "$CONTAINER_ID" apt-get update -qq > /dev/null 2>&1
        if $CONTAINER_CMD exec "$CONTAINER_ID" apt-get install -y shellcheck > /dev/null 2>&1; then
            echo ""
            $CONTAINER_CMD exec "$CONTAINER_ID" shellcheck /tmp/install.sh || true
        else
            echo "⚠️  ShellCheck nicht verfügbar"
        fi
        
        # Cleanup
        $CONTAINER_CMD rm -f "$CONTAINER_ID" &>/dev/null || true
        
        echo ""
        echo "✅ Schnelltest abgeschlossen!"
        ;;
        
    2)
        echo ""
        echo "🧪 Starte interaktiven Test..."
        echo ""
        echo "Du wirst in einen Ubuntu 24 Container versetzt."
        echo "Führe dort './install.sh' aus und beantworte die Fragen."
        echo "Beende mit 'exit' wenn du fertig bist."
        echo ""
        read -p "Drücke ENTER um fortzufahren..."
        
        # Starte interaktiven Container
        $CONTAINER_CMD run -it --rm \
            -v "$PROJECT_ROOT:/workspace:ro" \
            -w /tmp/test \
            ubuntu:24.04 \
            bash -c "
                apt-get update -qq
                apt-get install -y bash coreutils > /dev/null 2>&1
                cp /workspace/install.sh /tmp/test/
                cp -r /workspace/scripts /tmp/test/ 2>/dev/null || true
                chmod +x /tmp/test/install.sh
                echo ''
                echo '═══════════════════════════════════════════════════════════════'
                echo '  Ubuntu 24 Test-Umgebung'
                echo '═══════════════════════════════════════════════════════════════'
                echo ''
                echo 'Du bist jetzt in einem Ubuntu 24 Container (WSL2-ähnlich)'
                echo 'Führe aus: ./install.sh'
                echo 'Beende mit: exit'
                echo ''
                bash
            "
        ;;
        
    3)
        echo ""
        echo "🤖 Führe automatischen Test aus..."
        echo ""
        
        # Erstelle Test-Antworten
        TEST_SCRIPT=$(cat <<'TESTEOF'
#!/bin/bash
set -euo pipefail

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Automatischer Test - Bash Version"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
bash --version | head -1
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Syntax-Check"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if bash -n /tmp/test/install.sh; then
    echo "✅ Syntax-Check erfolgreich"
else
    echo "❌ Syntax-Fehler gefunden!"
    exit 1
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test: Prüfe auf häufige Bash-Fehler"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Verwende das simple-bash-test.sh Script
if [ -f /workspace/scripts/simple-bash-test.sh ]; then
    cp /workspace/scripts/simple-bash-test.sh /tmp/test/
    chmod +x /tmp/test/simple-bash-test.sh
    if /tmp/test/simple-bash-test.sh /tmp/test/install.sh > /dev/null 2>&1; then
        echo "✅ Alle Bash-Prüfungen bestanden"
    else
        echo "❌ Bash-Prüfungen fehlgeschlagen!"
        exit 1
    fi
else
    echo "⚠️  simple-bash-test.sh nicht gefunden, überspringe"
fi
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test abgeschlossen"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
TESTEOF
)
        
        # Führe Test aus
        $CONTAINER_CMD run --rm \
            -v "$PROJECT_ROOT:/workspace:ro" \
            ubuntu:24.04 \
            bash -c "
                apt-get update -qq
                apt-get install -y bash coreutils php git tar > /dev/null 2>&1
                
                mkdir -p /tmp/test
                cp /workspace/install.sh /tmp/test/
                cp -r /workspace/scripts /tmp/test/ 2>/dev/null || true
                chmod +x /tmp/test/install.sh
                
                $TEST_SCRIPT
            "
        
        echo ""
        echo "✅ Automatischer Test abgeschlossen!"
        ;;
        
    *)
        echo "❌ Ungültige Option"
        exit 1
        ;;
esac

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "  Test abgeschlossen!"
echo "═══════════════════════════════════════════════════════════════"
echo ""
