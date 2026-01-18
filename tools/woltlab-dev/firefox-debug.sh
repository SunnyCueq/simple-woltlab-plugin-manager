#!/bin/bash

# Generische Pfade
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TOOLS_DIR="$(dirname "$SCRIPT_DIR")"
DDEV_DIR="$SCRIPT_DIR"

echo "=========================================="
echo "Firefox Debug-Informationen"
echo "=========================================="
echo ""

echo "1. Aktuelle ddev URLs:"
cd "$DDEV_DIR"
ddev describe 2>&1 | grep -A 3 "Project URLs"
echo ""

echo "2. Teste direkte Verbindung (HTTP):"
curl -I http://127.0.0.1:32776 2>&1 | head -5
echo ""

echo "3. Teste direkte Verbindung (HTTPS):"
curl -k -I https://127.0.0.1:32777 2>&1 | head -5
echo ""

echo "4. Teste über Domain (HTTP):"
curl -I http://woltlab.ddev.site 2>&1 | head -5
echo ""

echo "5. Teste über Domain (HTTPS):"
curl -k -I https://woltlab.ddev.site 2>&1 | head -5
echo ""

echo "=========================================="
echo "Firefox-Lösungen:"
echo "=========================================="
echo ""
echo "1. Versuche diese URLs direkt in Firefox:"
echo "   - http://127.0.0.1:32776"
echo "   - https://127.0.0.1:32777"
echo ""
echo "2. Firefox-Einstellungen prüfen:"
echo "   - Öffne: about:preferences#privacy"
echo "   - Scrolle zu 'Zertifikate'"
echo "   - Klicke 'Zertifikate anzeigen'"
echo ""
echo "3. Firefox-Profil zurücksetzen:"
echo "   - Öffne: about:support"
echo "   - Klicke 'Firefox zurücksetzen...'"
echo ""
echo "4. DNS-Cache in Firefox löschen:"
echo "   - Öffne: about:networking#dns"
echo "   - Klicke 'DNS-Cache löschen'"
echo ""
echo "5. Proxy-Einstellungen prüfen:"
echo "   - Öffne: about:preferences#general"
echo "   - Scrolle zu 'Netzwerk-Einstellungen'"
echo "   - Stelle sicher, dass 'Kein Proxy' ausgewählt ist"
echo ""
