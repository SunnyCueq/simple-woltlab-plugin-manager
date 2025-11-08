#!/bin/bash

# Install-Script für Simple WoltLab Plugin Manager
# Dieses Script richtet die Entwicklungsumgebung für WoltLab Plugin-Entwicklung ein
#
# Verwendung: ./install.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "=== Simple WoltLab Plugin Manager - Installation ==="
echo ""

# Funktion zum Abfragen von Pfaden
ask_path() {
    local prompt="$1"
    local default="$2"
    local path
    
    if [ -n "$default" ]; then
        read -p "$prompt [$default]: " path
        path="${path:-$default}"
    else
        read -p "$prompt: " path
    fi
    
    # Erweitere ~ zu $HOME
    path="${path/#\~/$HOME}"
    
    # Prüfe ob Pfad existiert
    if [ ! -d "$path" ] && [ ! -f "$path" ]; then
        echo "⚠️  Warnung: Pfad existiert nicht: $path"
        read -p "Trotzdem verwenden? (j/n): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
            ask_path "$prompt" "$default"
            return
        fi
    fi
    
    echo "$path"
}

# Prüfe Voraussetzungen
echo "🔍 Prüfe Voraussetzungen..."

# PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP nicht gefunden. Bitte installieren Sie PHP 8.0 oder höher."
    exit 1
fi
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "✓ PHP gefunden: $PHP_VERSION"

# Git
if ! command -v git &> /dev/null; then
    echo "❌ Git nicht gefunden. Bitte installieren Sie Git."
    exit 1
fi
echo "✓ Git gefunden: $(git --version)"

# tar
if ! command -v tar &> /dev/null; then
    echo "❌ tar nicht gefunden. Bitte installieren Sie tar."
    exit 1
fi
echo "✓ tar gefunden"

echo ""
echo "=== Konfiguration ==="
echo ""

# WoltLab Core
echo "🔧 WoltLab Suite Core"
WOLTLAB_CORE=$(ask_path "Pfad zum WoltLab Core Verzeichnis" "")

# Plugin-Verzeichnis
echo ""
echo "📦 Plugin-Verzeichnis"
PLUGIN_DIR=$(ask_path "Pfad zu Ihrem Plugin-Verzeichnis (oder leer für neues Plugin)" "")

# Hauptplugins (optional)
echo ""
echo "📦 Hauptplugins (optional, leer lassen zum Überspringen)"
MAIN_PLUGINS=()
while true; do
    PLUGIN_PATH=$(ask_path "Pfad zu einem Hauptplugin (oder leer zum Beenden)" "")
    if [ -z "$PLUGIN_PATH" ]; then
        break
    fi
    MAIN_PLUGINS+=("$PLUGIN_PATH")
done

# Erstelle Konfigurationsdatei
CONFIG_FILE="$HOME/.woltlab-config"
echo ""
echo "💾 Erstelle Konfigurationsdatei: $CONFIG_FILE"

cat > "$CONFIG_FILE" << EOF
# WoltLab Plugin Development Configuration
# Diese Datei wird von den Scripts verwendet

WOLTLAB_CORE="$WOLTLAB_CORE"
PLUGIN_DIR="$PLUGIN_DIR"
EOF

# Hauptplugins zur Konfiguration hinzufügen
if [ ${#MAIN_PLUGINS[@]} -gt 0 ]; then
    echo "" >> "$CONFIG_FILE"
    echo "# Hauptplugins" >> "$CONFIG_FILE"
    PLUGIN_COUNT=1
    for plugin_path in "${MAIN_PLUGINS[@]}"; do
        echo "MAIN_PLUGIN_$PLUGIN_COUNT=\"$plugin_path\"" >> "$CONFIG_FILE"
        ((PLUGIN_COUNT++))
    done
fi

chmod 600 "$CONFIG_FILE"
echo "✓ Konfiguration gespeichert"

# Kopiere Scripts ins Plugin-Verzeichnis (falls angegeben)
if [ -n "$PLUGIN_DIR" ] && [ -d "$PLUGIN_DIR" ]; then
    echo ""
    echo "📋 Kopiere Scripts ins Plugin-Verzeichnis..."
    cp "$SCRIPT_DIR/scripts/extract-plugin-files.sh" "$PLUGIN_DIR/"
    cp "$SCRIPT_DIR/scripts/update-tars.sh" "$PLUGIN_DIR/"
    cp "$SCRIPT_DIR/scripts/create-release.sh" "$PLUGIN_DIR/"
    chmod +x "$PLUGIN_DIR"/*.sh
    echo "✓ Scripts kopiert"
fi

# Erstelle Workspace
echo ""
echo "📝 Erstelle Workspace..."

# Workspace-Pfad bestimmen
WORKSPACE_FILE="woltlab-plugin-dev.code-workspace"
if [ -n "$PLUGIN_DIR" ]; then
    WORKSPACE_PATH="$(dirname "$PLUGIN_DIR")/$WORKSPACE_FILE"
else
    WORKSPACE_PATH="$HOME/$WORKSPACE_FILE"
fi

# Erstelle Workspace-JSON
FOLDERS_JSON="["
FOLDERS_JSON+="\n    {"
FOLDERS_JSON+="\n      \"name\": \"🎯 Mein Plugin\","
FOLDERS_JSON+="\n      \"path\": \"$PLUGIN_DIR\""
FOLDERS_JSON+="\n    }"

# WoltLab Core hinzufügen
if [ -n "$WOLTLAB_CORE" ]; then
    FOLDERS_JSON+=","
    FOLDERS_JSON+="\n    {"
    FOLDERS_JSON+="\n      \"name\": \"🔧 WoltLab Suite Core (Read-Only Referenz)\","
    FOLDERS_JSON+="\n      \"path\": \"$WOLTLAB_CORE\""
    FOLDERS_JSON+="\n    }"
fi

# Hauptplugins hinzufügen
PLUGIN_COUNT=1
for plugin_path in "${MAIN_PLUGINS[@]}"; do
    FOLDERS_JSON+=","
    FOLDERS_JSON+="\n    {"
    FOLDERS_JSON+="\n      \"name\": \"📦 Hauptplugin $PLUGIN_COUNT (Referenz)\","
    FOLDERS_JSON+="\n      \"path\": \"$plugin_path\""
    FOLDERS_JSON+="\n    }"
    ((PLUGIN_COUNT++))
done

FOLDERS_JSON+="\n  ]"

# Intelephense includePaths
INCLUDE_PATHS="["
if [ -n "$WOLTLAB_CORE" ]; then
    INCLUDE_PATHS+="\n      \"$WOLTLAB_CORE/lib\""
fi
INCLUDE_PATHS+="\n    ]"

# Erstelle Workspace-JSON
cat > "$WORKSPACE_PATH" << EOF
{
  "folders": $FOLDERS_JSON,
  "settings": {
    "files.exclude": {
      "**/node_modules": true,
      "**/.git": true,
      "**/cache/**": true,
      "**/tmp/**": true,
      "**/.DS_Store": true
    },
    "search.exclude": {
      "**/node_modules": true,
      "**/cache": true,
      "**/tmp": true
    },
    "files.watcherExclude": {
      "**/cache/**": true,
      "**/tmp/**": true
    },
    "editor.formatOnSave": false,
    "files.encoding": "utf8",
    "php.suggest.basic": true,
    "php.validate.executablePath": "/usr/bin/php",
    "intelephense.environment.includePaths": $INCLUDE_PATHS,
    "intelephense.environment.phpVersion": "8.4.0",
    "intelephense.diagnostics.undefinedTypes": false,
    "intelephense.diagnostics.undefinedMethods": false,
    "intelephense.diagnostics.undefinedConstants": false,
    "intelephense.diagnostics.undefinedFunctions": false,
    "intelephense.diagnostics.undefinedClasses": false
  },
  "extensions": {
    "recommendations": [
      "bmewburn.vscode-intelephense-client",
      "xdebug.php-debug",
      "EditorConfig.EditorConfig"
    ]
  }
}
EOF

echo "✅ Workspace erstellt: $WORKSPACE_PATH"

echo ""
echo "=== ✅ Installation abgeschlossen! ==="
echo ""
echo "Nächste Schritte:"
echo ""
echo "1. Öffnen Sie das Workspace:"
echo "   cursor $WORKSPACE_PATH"
echo "   oder"
echo "   code $WORKSPACE_PATH"
echo ""
echo "2. Installieren Sie die empfohlenen Extensions in der IDE"
echo ""
echo "3. Falls Sie ein neues Plugin erstellen möchten:"
echo "   - Schauen Sie sich das Beispiel-Plugin an: example-plugin/"
echo "   - Folgen Sie der Anleitung: https://docs.woltlab.com/6.0/getting-started/"
echo ""
echo "4. Scripts verwenden:"
echo "   - ./extract-plugin-files.sh  # TAR-Dateien entpacken"
echo "   - ./update-tars.sh            # TAR-Dateien aktualisieren"
echo "   - ./create-release.sh VERSION # Release erstellen"
echo ""
echo "📚 Dokumentation:"
echo "   - README.md - Übersicht und Schnellstart"
echo "   - docs/INSTALLATION.md - Detaillierte Installationsanleitung"
echo "   - docs/WORKSPACE-SETUP.md - Workspace-Konfiguration"
echo ""

