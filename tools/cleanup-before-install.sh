#!/bin/bash
# Bereinigt die Datenbank vor Plugin-Installation
# Verwendung: ./cleanup-before-install.sh

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$TOOLS_DIR/woltlab-dev"

echo "Bereinige Datenbank vor Plugin-Installation..."

# Lösche alle shrinkr-Einträge
ddev mysql -e "DELETE FROM wcf1_package_installation_file_log WHERE filename = 'app.config.inc.php' AND application = 'shrinkr';" 2>&1
ddev mysql -e "DELETE FROM wcf1_package_installation_file_log WHERE application = 'shrinkr';" 2>&1
ddev mysql -e "DELETE FROM wcf1_package WHERE package LIKE '%shrinkr%' OR package LIKE '%sunnyc.wsc.shrinkr%';" 2>&1

# Lösche Datei auf Dateisystem
ddev exec "rm -f /var/www/html/public/shrinkr/app.config.inc.php 2>&1" > /dev/null 2>&1

echo "✓ Datenbank bereinigt"
