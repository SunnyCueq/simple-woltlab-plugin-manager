#!/bin/bash

# Script zum Erstellen eines Multi-Root Workspace für WoltLab Plugin-Entwicklung
#
# Verwendung: ./setup-workspace.sh
# Das Script fragt nach den benötigten Pfaden und erstellt das Workspace-File

set -e

echo "=== WoltLab Plugin Workspace Setup ==="
echo ""
echo "Dieses Script erstellt ein Multi-Root Workspace für die WoltLab Plugin-Entwicklung."
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

# Plugin-Verzeichnis
echo "📦 Plugin-Verzeichnis"
PLUGIN_DIR=$(ask_path "Pfad zu Ihrem Plugin-Verzeichnis" "$(pwd)")

# WoltLab Core
echo ""
echo "🔧 WoltLab Suite Core"
WOLTLAB_CORE=$(ask_path "Pfad zum WoltLab Core Verzeichnis" "")

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

# Workspace-Dateiname
echo ""
read -p "Workspace-Dateiname [woltlab-plugin-dev.code-workspace]: " WORKSPACE_FILE
WORKSPACE_FILE="${WORKSPACE_FILE:-woltlab-plugin-dev.code-workspace}"

# Erstelle Workspace-File
WORKSPACE_PATH="$(dirname "$PLUGIN_DIR")/$WORKSPACE_FILE"

echo ""
echo "📝 Erstelle Workspace: $WORKSPACE_PATH"

# JSON-Array für Folders
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
echo "Nächste Schritte:"
echo "  1. Öffnen Sie das Workspace in Cursor/VSCode:"
echo "     cursor $WORKSPACE_PATH"
echo "     oder"
echo "     code $WORKSPACE_PATH"
echo ""
echo "  2. Installieren Sie die empfohlenen Extensions"
echo "  3. Neustarten Sie die IDE für Intelephense-Konfiguration"
echo ""

