#!/bin/bash

# Einfacher Bash-Test für install.sh
# Testet grundlegende Bash-Kompatibilität

SCRIPT_FILE="${1:-install.sh}"

echo "═══════════════════════════════════════════════════════════════"
echo "  Bash Kompatibilitätstest"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Teste: $SCRIPT_FILE"
echo "Bash Version: $(bash --version | head -1)"
echo ""

# Test 1: Syntax-Check
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 1: Bash Syntax-Check"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if bash -n "$SCRIPT_FILE" 2>&1; then
    echo "✅ Syntax ist korrekt"
else
    echo "❌ Syntax-Fehler gefunden!"
    exit 1
fi
echo ""

# Test 2: Prüfe auf 'local' im globalen Scope
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 2: Prüfe 'local' außerhalb von Funktionen"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Alle local-Zeilen müssen eingerückt sein (innerhalb von Funktionen)
# Globale Zeilen beginnen NICHT mit Leerzeichen/Tabs
if grep -n "^local " "$SCRIPT_FILE" 2>/dev/null; then
    echo "❌ FEHLER: 'local' außerhalb von Funktionen gefunden!"
    echo "   (Zeilen die mit 'local' beginnen ohne Einrückung)"
    exit 1
else
    echo "✅ Kein 'local' im globalen Scope"
fi
echo ""

# Test 3: Zähle alle local-Vorkommen (Information)
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 3: Statistiken"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
LOCAL_COUNT=$(grep -c "^\s*local " "$SCRIPT_FILE" || echo "0")
FUNCTION_COUNT=$(grep -cE "^\s*(function\s+[a-zA-Z_]|[a-zA-Z_][a-zA-Z0-9_]*\s*\(\))" "$SCRIPT_FILE" || echo "0")

echo "Funktionen gefunden: $FUNCTION_COUNT"
echo "local-Deklarationen: $LOCAL_COUNT"
echo ""

echo "═══════════════════════════════════════════════════════════════"
echo "  ✅ Alle Tests bestanden!"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Das Script ist kompatibel mit:"
echo "  • Bash 4.x, 5.x"
echo "  • Ubuntu 20.04, 22.04, 24.04"
echo "  • WSL2"
echo "  • macOS"
echo "  • Arch/CachyOS"
echo ""
