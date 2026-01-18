#!/bin/bash

echo "=========================================="
echo "Firefox Verbindungstest für woltlab.ddev.site"
echo "=========================================="
echo ""

echo "1. Teste DNS-Auflösung..."
nslookup woltlab.ddev.site 2>&1 | head -5
echo ""

echo "2. Teste HTTP-Verbindung..."
curl -I http://woltlab.ddev.site 2>&1 | head -5
echo ""

echo "3. Teste HTTPS-Verbindung..."
curl -k -I https://woltlab.ddev.site 2>&1 | head -5
echo ""

echo "4. Alternative URLs:"
echo "   HTTP:  http://127.0.0.1:32776"
echo "   HTTPS: https://127.0.0.1:32777"
echo ""

echo "5. Firefox-Tipps:"
echo "   - Öffne: about:preferences#privacy"
echo "   - Scrolle zu 'Zertifikate' → 'Zertifikate anzeigen'"
echo "   - Oder verwende HTTP statt HTTPS"
echo ""
