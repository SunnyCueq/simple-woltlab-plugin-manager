#!/bin/bash

# Simple WoltLab Plugin Manager - Plugin Template Generator
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
# This project is open source under the MIT License, but the copyright
# attribution must be preserved in all copies and substantial portions.
#
# Erstellt eine Plugin-Grundstruktur basierend auf dem Package-Identifier
#
# Verwendung: ./create-plugin.sh PACKAGE_IDENTIFIER [TARGET_DIR]
#   PACKAGE_IDENTIFIER: z.B. com.example.myplugin
#   TARGET_DIR: Ziel-Verzeichnis (optional, Standard: aktuelles Verzeichnis)
#
# Beispiel: ./create-plugin.sh com.example.myplugin

set -e

# Parameter prüfen
if [ -z "$1" ]; then
    echo "❌ Fehler: Package-Identifier fehlt!"
    echo ""
    echo "Verwendung: $0 PACKAGE_IDENTIFIER [TARGET_DIR]"
    echo "Beispiel: $0 com.example.myplugin"
    echo ""
    echo "Package-Identifier Format: com.domain.pluginname"
    exit 1
fi

PACKAGE_IDENTIFIER="$1"
TARGET_DIR="${2:-$(pwd)}"

# Validierung: Package-Identifier-Format
if [[ ! "$PACKAGE_IDENTIFIER" =~ ^com\.[a-z0-9]+\.[a-z0-9]+(\.[a-z0-9]+)*$ ]]; then
    echo "❌ Fehler: Ungültiges Package-Identifier-Format!"
    echo ""
    echo "Erwartetes Format: com.domain.pluginname"
    echo "Beispiele:"
    echo "  - com.example.myplugin"
    echo "  - com.sunnyc.wcf.buttonBox"
    echo ""
    exit 1
fi

# Extrahiere Plugin-Name aus Identifier
PLUGIN_NAME=$(echo "$PACKAGE_IDENTIFIER" | sed 's/.*\.//')
PLUGIN_DIR="$TARGET_DIR/$PACKAGE_IDENTIFIER"

# Prüfe ob Verzeichnis bereits existiert
if [ -d "$PLUGIN_DIR" ]; then
    echo "⚠️  Warnung: Verzeichnis existiert bereits: $PLUGIN_DIR"
    read -p "Möchtest du es überschreiben? (j/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
        echo "Abgebrochen."
        exit 0
    fi
    rm -rf "$PLUGIN_DIR"
fi

echo "═══════════════════════════════════════════════════════════════"
echo "  Plugin-Template Generator"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Package-Identifier: $PACKAGE_IDENTIFIER"
echo "Plugin-Name: $PLUGIN_NAME"
echo "Ziel-Verzeichnis: $PLUGIN_DIR"
echo ""

# Erstelle Verzeichnisstruktur
echo "📁 Erstelle Verzeichnisstruktur..."
mkdir -p "$PLUGIN_DIR/files/lib"
mkdir -p "$PLUGIN_DIR/templates"
mkdir -p "$PLUGIN_DIR/language"

# Erstelle package.xml
echo "📝 Erstelle package.xml..."
cat > "$PLUGIN_DIR/package.xml" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.woltlab.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.woltlab.com http://www.woltlab.com/XSD/2019/package.xsd" name="$PACKAGE_IDENTIFIER">
	<packageinformation>
		<!-- $PACKAGE_IDENTIFIER -->
		<packagename>$PLUGIN_NAME</packagename>
		<packagedescription>Description for $PLUGIN_NAME</packagedescription>
		<version>1.0.0</version>
		<date>$(date +%Y-%m-%d)</date>
	</packageinformation>
	<authorinformation>
		<author>Your Name</author>
		<authorurl>http://www.example.com</authorurl>
	</authorinformation>
	<requiredpackages>
		<requiredpackage minversion="6.0.0">com.woltlab.wcf</requiredpackage>
	</requiredpackages>
	<excludedpackages>
		<excludedpackage version="7.0.0 Alpha 1">com.woltlab.wcf</excludedpackage>
	</excludedpackages>
	<instructions type="install">
		<instruction type="file" />
		<instruction type="template" />
		<instruction type="page" />
	</instructions>
</package>
EOF

# Erstelle page.xml (optional)
echo "📝 Erstelle page.xml..."
cat > "$PLUGIN_DIR/page.xml" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<data xmlns="http://www.woltlab.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.woltlab.com http://www.woltlab.com/XSD/2019/page.xsd">
	<import>
		<page identifier="com.example.${PLUGIN_NAME}.page" controller="ExamplePage" menuItem="com.example.${PLUGIN_NAME}.menu">
			<name language="en">Example Page</name>
			<name language="de">Beispiel-Seite</name>
		</page>
	</import>
</data>
EOF

# Erstelle Beispiel-PHP-Klasse
echo "📝 Erstelle Beispiel-PHP-Klasse..."
mkdir -p "$PLUGIN_DIR/files/lib/page"
cat > "$PLUGIN_DIR/files/lib/page/ExamplePage.class.php" <<'EOFPHP'
<?php

namespace wcf\page;

use wcf\system\WCF;

/**
 * Example page.
 *
 * @author	Your Name
 * @copyright	2025 Your Name
 * @license	MIT License <http://opensource.org/licenses/MIT>
 * @package	com.example.myplugin
 */
class ExamplePage extends AbstractPage {
	/**
	 * @inheritDoc
	 */
	public $neededModules = [];
	
	/**
	 * @inheritDoc
	 */
	public $neededPermissions = [];
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		// Your code here
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign([
			// Your variables here
		]);
	}
}
EOFPHP

# Erstelle Beispiel-Template
echo "📝 Erstelle Beispiel-Template..."
cat > "$PLUGIN_DIR/templates/example.tpl" <<'EOFTEMPLATE'
{* Template for Example Page *}

<div class="section">
	<header class="sectionHeader">
		<h2 class="sectionTitle">{lang}com.example.myplugin.page.title{/lang}</h2>
	</header>
	
	<div class="sectionContent">
		<p>This is an example template.</p>
	</div>
</div>
EOFTEMPLATE

# Erstelle README.md
echo "📝 Erstelle README.md..."
cat > "$PLUGIN_DIR/README.md" <<EOF
# $PLUGIN_NAME

**Package-Identifier:** \`$PACKAGE_IDENTIFIER\`

## Beschreibung

Beschreibe hier dein Plugin.

## Installation

1. Lade das Plugin-Package herunter
2. Installiere es über das ACP → Pakete → Paket installieren

## Entwicklung

Dieses Plugin wurde mit dem Simple WoltLab Plugin Manager erstellt.

### Verzeichnisstruktur

\`\`\`
$PACKAGE_IDENTIFIER/
├── package.xml          # Plugin-Manifest
├── page.xml             # Seiten-Definitionen
├── files/               # PHP-Dateien
│   └── lib/
│       └── page/
│           └── ExamplePage.class.php
├── templates/           # Templates
│   └── example.tpl
└── language/           # Sprachdateien
\`\`\`

## Weitere Informationen

- [WoltLab Dokumentation](https://docs.woltlab.com/6.0/)
- [Simple WoltLab Plugin Manager](https://github.com/SunnyCueq/simple-woltlab-plugin-manager)
EOF

echo ""
echo "✅ Plugin-Grundstruktur erstellt!"
echo ""
echo "📁 Verzeichnis: $PLUGIN_DIR"
echo ""
echo "📝 Erstellte Dateien:"
echo "   ✓ package.xml"
echo "   ✓ page.xml"
echo "   ✓ files/lib/page/ExamplePage.class.php"
echo "   ✓ templates/example.tpl"
echo "   ✓ README.md"
echo ""
echo "🚀 Nächste Schritte:"
echo "   1. Bearbeite package.xml und passe die Metadaten an"
echo "   2. Passe die PHP-Klassen in files/lib/ an"
echo "   3. Erstelle deine Templates in templates/"
echo "   4. Füge Sprachdateien in language/ hinzu"
echo "   5. Verwende ./create-release.sh um ein Package zu erstellen"
echo ""
echo "📚 Dokumentation:"
echo "   - WoltLab: https://docs.woltlab.com/6.0/getting-started/"
echo "   - Plugin Manager: https://github.com/SunnyCueq/simple-woltlab-plugin-manager"
echo ""

