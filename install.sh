#!/bin/bash

# Simple WoltLab Plugin Manager - Installation Script
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
# This project is open source under the MIT License, but the copyright
# attribution must be preserved in all copies and substantial portions.
#
# This script sets up the development environment for WoltLab plugin development.
# Usage: ./install.sh

# Fehlerbehandlung: Nur bei unerwarteten Fehlern abbrechen
set -euo pipefail

# Fehler-Handler
trap 'error_handler $? $LINENO' ERR

# Logging-Variablen
LOG_FILE="/tmp/woltlab-install-$(date +%Y%m%d-%H%M%S).log"
VERBOSE=false

# Fehler-Handler Funktion
error_handler() {
    local exit_code=$1
    local line_number=$2
    echo ""
    echo "❌ FEHLER: Installation fehlgeschlagen in Zeile $line_number (Exit-Code: $exit_code)"
    echo "   Log-Datei: $LOG_FILE"
    echo "   Bitte prüfe die Log-Datei für Details."
    echo ""
    echo "Häufige Probleme:"
    echo "  • Fehlende Berechtigungen: Prüfe ob du Schreibrechte hast"
    echo "  • Fehlende Abhängigkeiten: Installiere PHP, Git, tar"
    echo "  • Ungültige Pfade: Prüfe ob die angegebenen Pfade korrekt sind"
    echo ""
    exit 1
}

# Logging-Funktion
log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"

    if [ "$VERBOSE" = true ] || [ "$level" = "ERROR" ] || [ "$level" = "WARNING" ]; then
        echo "$message"
    fi
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

log "INFO" "Installation gestartet"
log "INFO" "Script-Verzeichnis: $SCRIPT_DIR"

echo "═══════════════════════════════════════════════════════════════"
echo "  Simple WoltLab Plugin Manager - Installation"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Dieses Script führt dich Schritt für Schritt durch die Installation."
echo "Du musst nur die Fragen beantworten – der Rest passiert automatisch!"
echo ""
echo "Drücke STRG+C, um die Installation abzubrechen."
echo ""
read -p "Drücke ENTER, um fortzufahren... "
echo ""

# Funktion zum Validieren von Pfaden
validate_path() {
    local path="$1"
    local type="${2:-any}"  # any, file, directory
    local must_exist="${3:-false}"

    # Erweitere ~ zu $HOME
    path="${path/#\~/$HOME}"

    # Prüfe ob Pfad leer ist
    if [ -z "$path" ]; then
        return 1
    fi

    # Prüfe ob Pfad existiert (falls erforderlich)
    if [ "$must_exist" = "true" ]; then
        if [ "$type" = "file" ] && [ ! -f "$path" ]; then
            log "WARNING" "Datei existiert nicht: $path"
            return 1
        elif [ "$type" = "directory" ] && [ ! -d "$path" ]; then
            log "WARNING" "Verzeichnis existiert nicht: $path"
            return 1
        elif [ "$type" = "any" ] && [ ! -e "$path" ]; then
            log "WARNING" "Pfad existiert nicht: $path"
            return 1
        fi
    fi

    # Prüfe ob Pfad absolute oder relative ist
    if [[ ! "$path" = /* ]] && [[ ! "$path" = ~* ]]; then
        # Relativer Pfad - konvertiere zu absolutem Pfad
        path="$(cd "$(dirname "$path")" 2>/dev/null && pwd)/$(basename "$path")" || path="$PWD/$path"
    fi

    echo "$path"
    return 0
}

# Funktion zum Abfragen von Pfaden mit Validierung
ask_path() {
    local prompt="$1"
    local default="$2"
    local allow_empty="${3:-false}"
    local max_retries=3
    local retry_count=0
    local path

    while [ $retry_count -lt $max_retries ]; do
        if [ -n "$default" ]; then
            read -p "$prompt [$default]: " path
            path="${path:-$default}"
        else
            read -p "$prompt: " path
        fi

        # Leer erlaubt?
        if [ -z "$path" ]; then
            if [ "$allow_empty" = "true" ]; then
                echo ""
                return 0
            else
                echo "⚠️  Pfad darf nicht leer sein. Bitte versuche es erneut."
                ((retry_count++))
                continue
            fi
        fi

        # Erweitere ~ zu $HOME
        path="${path/#\~/$HOME}"

        # Validiere Pfad
        if [ ! -d "$path" ] && [ ! -f "$path" ]; then
            echo "⚠️  Warnung: Pfad existiert nicht: $path"
            log "WARNING" "Benutzer gab nicht-existierenden Pfad an: $path"

            read -p "Trotzdem verwenden? (j/n): " -n 1 -r
            echo
            if [[ $REPLY =~ ^[JjYy]$ ]]; then
                log "INFO" "Benutzer akzeptierte nicht-existierenden Pfad: $path"
                echo "$path"
                return 0
            else
                ((retry_count++))
                if [ $retry_count -lt $max_retries ]; then
                    echo "Bitte gib einen anderen Pfad an (Versuch $((retry_count + 1))/$max_retries):"
                fi
                continue
            fi
        fi

        # Pfad existiert - validiert
        log "INFO" "Pfad validiert: $path"
        echo "$path"
        return 0
    done

    # Max Retries erreicht
    echo "❌ Maximale Anzahl von Versuchen erreicht. Installation abgebrochen."
    log "ERROR" "Maximale Anzahl von Pfad-Eingabe-Versuchen erreicht"
    exit 1
}

# Funktion zum Validieren des WoltLab Cores
validate_woltlab_core() {
    local core_path="$1"

    if [ -z "$core_path" ]; then
        return 1
    fi

    log "INFO" "Validiere WoltLab Core: $core_path"

    # Prüfe ob Verzeichnis existiert
    if [ ! -d "$core_path" ]; then
        log "ERROR" "WoltLab Core Verzeichnis existiert nicht: $core_path"
        echo "❌ Verzeichnis existiert nicht: $core_path"
        return 1
    fi

    # Prüfe kritische Verzeichnisse und Dateien
    local required_paths=(
        "lib"
        "wcf"
        "wcf/global.php"
    )

    local missing_paths=()

    for req_path in "${required_paths[@]}"; do
        if [ ! -e "$core_path/$req_path" ]; then
            missing_paths+=("$req_path")
        fi
    done

    if [ ${#missing_paths[@]} -gt 0 ]; then
        log "ERROR" "WoltLab Core Struktur unvollständig. Fehlende Pfade: ${missing_paths[*]}"
        echo "❌ Ungültige WoltLab Core Struktur!"
        echo "   Fehlende Pfade:"
        for missing in "${missing_paths[@]}"; do
            echo "     • $missing"
        done
        echo ""
        echo "   Erwartete Struktur:"
        echo "     • lib/ - WoltLab Bibliotheken"
        echo "     • wcf/ - WoltLab Community Framework"
        echo "     • wcf/global.php - Hauptdatei"
        echo ""
        return 1
    fi

    # Prüfe ob lib/system Verzeichnis existiert (enthält wichtige Klassen)
    if [ -d "$core_path/lib/system" ]; then
        log "INFO" "WoltLab Core Struktur validiert: $core_path"
        echo "✅ WoltLab Core Struktur validiert"
        return 0
    else
        log "WARNING" "lib/system Verzeichnis fehlt in: $core_path"
        echo "⚠️  lib/system Verzeichnis fehlt - Core könnte unvollständig sein"
        return 1
    fi
}

# Funktion zum Erkennen des Betriebssystems
detect_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
        OS_LIKE=$ID_LIKE
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        OS="linux"
    else
        OS="unknown"
    fi

    log "INFO" "Betriebssystem erkannt: $OS"
}

# Funktion zum automatischen Installieren von Paketen
install_package() {
    local package="$1"
    local os="$2"
    
    echo ""
    echo "🔧 Versuche automatische Installation von $package..."
    
    case "$os" in
        arch|cachyos|manjaro)
            if command -v sudo &> /dev/null && command -v pacman &> /dev/null; then
                echo "   Verwende: sudo pacman -S --noconfirm $package"
                if sudo pacman -S --noconfirm "$package" 2>/dev/null; then
                    echo "✓ $package erfolgreich installiert"
                    return 0
                fi
            fi
            ;;
        debian|ubuntu|raspbian)
            if command -v sudo &> /dev/null && command -v apt-get &> /dev/null; then
                echo "   Aktualisiere Paketliste..."
                sudo apt-get update -qq > /dev/null 2>&1
                echo "   Verwende: sudo apt-get install -y $package"
                if sudo apt-get install -y "$package" > /dev/null 2>&1; then
                    echo "✓ $package erfolgreich installiert"
                    return 0
                fi
            fi
            ;;
        macos)
            if command -v brew &> /dev/null; then
                echo "   Verwende: brew install $package"
                if brew install "$package" > /dev/null 2>&1; then
                    echo "✓ $package erfolgreich installiert"
                    return 0
                fi
            fi
            ;;
    esac
    
    return 1
}

# Funktion zum Prüfen und Installieren einer Voraussetzung
check_and_install() {
    local command="$1"
    local package="$2"
    local os="$3"
    local manual_instructions="$4"
    
    if command -v "$command" &> /dev/null; then
        return 0
    fi
    
    echo "❌ $command nicht gefunden!"
    
    # Versuche automatische Installation
    if install_package "$package" "$os"; then
        # Nachprüfung
        if command -v "$command" &> /dev/null; then
            echo "✅ $command erfolgreich installiert und verifiziert"
            return 0
        else
            echo "⚠️  Installation durchgeführt, aber $command nicht gefunden"
        fi
    fi
    
    # Falls automatische Installation fehlgeschlagen
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Manuelle Installation erforderlich"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "$manual_instructions"
    echo ""
        read -p "Drücke ENTER, nachdem du $command installiert hast, oder STRG+C zum Abbrechen... "
    echo ""
    
    # Erneute Prüfung
    if command -v "$command" &> /dev/null; then
        echo "✅ $command gefunden!"
        return 0
    else
        echo "❌ $command immer noch nicht gefunden. Bitte installiere es manuell."
        exit 1
    fi
}

# Funktion zum Herunterladen und Entpacken des WoltLab Cores
download_woltlab_core() {
    local target_dir="$1"
    local version="${2:-6.1.14}"
    
    local download_url="https://assets.woltlab.com/release/woltlab-suite-${version}.zip"
    local zip_file="/tmp/woltlab-suite-${version}.zip"
    
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "WoltLab Core Download"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "Version: $version"
    echo "Download-URL: $download_url"
    echo "Ziel-Verzeichnis: $target_dir"
    echo ""
    
    # Prüfe ob curl verfügbar ist
    if ! command -v curl &> /dev/null; then
        echo "❌ curl nicht gefunden. Bitte installiere curl für den Download."
        echo ""
        check_and_install "curl" "curl" "$OS" "Installiere curl:\n  • Arch/CachyOS: sudo pacman -S curl\n  • Ubuntu/Debian: sudo apt install curl\n  • macOS: brew install curl"
    fi
    
    # Prüfe ob unzip verfügbar ist
    if ! command -v unzip &> /dev/null; then
        echo "❌ unzip nicht gefunden. Bitte installiere unzip für das Entpacken."
        echo ""
        check_and_install "unzip" "unzip" "$OS" "Installiere unzip:\n  • Arch/CachyOS: sudo pacman -S unzip\n  • Ubuntu/Debian: sudo apt install unzip\n  • macOS: brew install unzip"
    fi
    
    read -p "Möchtest du den WoltLab Core jetzt herunterladen? (j/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
        return 1
    fi
    
    # Erstelle Ziel-Verzeichnis
    mkdir -p "$target_dir"
    
    # Download
    echo ""
    echo "📥 Lade WoltLab Core herunter..."
    if curl -L -o "$zip_file" "$download_url" --progress-bar; then
        echo "✓ Download abgeschlossen"
    else
        echo "❌ Download fehlgeschlagen"
        return 1
    fi
    
    # Entpacken
    echo ""
    echo "📦 Entpacke WoltLab Core..."
    if unzip -q "$zip_file" -d "$target_dir"; then
        echo "✓ Entpacken abgeschlossen"
        
        # Finde das Hauptverzeichnis (meist woltlab-suite-X.X.X)
        local extracted_dir=$(find "$target_dir" -maxdepth 1 -type d -name "woltlab-suite-*" | head -1)
        if [ -n "$extracted_dir" ] && [ "$extracted_dir" != "$target_dir" ]; then
            # Verschiebe Inhalt ins Ziel-Verzeichnis
            echo "📁 Organisiere Verzeichnisstruktur..."
            mv "$extracted_dir"/* "$target_dir/" 2>/dev/null || true
            rmdir "$extracted_dir" 2>/dev/null || true
        fi
        
        # Validierung mit verbesserter Funktion
        if validate_woltlab_core "$target_dir"; then
            echo "   Pfad: $target_dir"
            rm -f "$zip_file"
            log "INFO" "WoltLab Core erfolgreich heruntergeladen und installiert: $target_dir"
            return 0
        else
            echo "⚠️  WoltLab Core entpackt, aber Validierung fehlgeschlagen"
            echo "   Bitte prüfe: $target_dir"
            log "ERROR" "WoltLab Core Validierung fehlgeschlagen nach Download: $target_dir"
            return 1
        fi
    else
        echo "❌ Entpacken fehlgeschlagen"
        return 1
    fi
}

# Betriebssystem erkennen
detect_os

# Prüfe Voraussetzungen
echo "═══════════════════════════════════════════════════════════════"
echo "  Schritt 1: Voraussetzungen prüfen"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Das Script prüft jetzt, ob alle benötigten Programme installiert sind..."
echo ""

# PHP
MANUAL_PHP="Installiere PHP 8.0 oder höher:\n  • Arch/CachyOS: sudo pacman -S php\n  • Ubuntu/Debian: sudo apt install php\n  • macOS: brew install php\n  • Windows WSL: sudo apt install php\n\nNach der Installation:\n  1. Prüfe die Installation: php --version\n  2. Stelle sicher, dass PHP 8.0+ installiert ist\n  3. Führe dieses Script erneut aus"

if check_and_install "php" "php" "$OS" "$MANUAL_PHP"; then
    PHP_VERSION=$(php -r 'echo PHP_VERSION;')
    echo "✓ PHP gefunden: $PHP_VERSION"
    
    # Prüfe PHP-Version
    PHP_MAJOR=$(echo "$PHP_VERSION" | cut -d. -f1)
    PHP_MINOR=$(echo "$PHP_VERSION" | cut -d. -f2)
    if [ "$PHP_MAJOR" -lt 8 ]; then
        echo "⚠️  Warnung: PHP Version $PHP_VERSION ist zu alt. PHP 8.0+ wird empfohlen."
    fi
fi

# Git
MANUAL_GIT="Installiere Git:\n  • Arch/CachyOS: sudo pacman -S git\n  • Ubuntu/Debian: sudo apt install git\n  • macOS: brew install git\n  • Windows WSL: sudo apt install git\n\nNach der Installation:\n  1. Prüfe die Installation: git --version\n  2. Führe dieses Script erneut aus"

if check_and_install "git" "git" "$OS" "$MANUAL_GIT"; then
    echo "✓ Git gefunden: $(git --version | cut -d' ' -f3)"
fi

# tar
MANUAL_TAR="Installiere tar:\n  • Arch/CachyOS: sudo pacman -S tar\n  • Ubuntu/Debian: sudo apt install tar\n  • macOS: tar ist normalerweise vorinstalliert\n  • Windows WSL: sudo apt install tar\n\nNach der Installation:\n  1. Prüfe die Installation: tar --version\n  2. Führe dieses Script erneut aus"

if check_and_install "tar" "tar" "$OS" "$MANUAL_TAR"; then
    echo "✓ tar gefunden"
fi

echo ""
echo "✅ Alle Voraussetzungen erfüllt!"
echo ""
read -p "Drücke ENTER, um fortzufahren... "
echo ""

# Konfiguration
echo "═══════════════════════════════════════════════════════════════"
echo "  Schritt 2: Konfiguration"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Jetzt wirst du nach den benötigten Pfaden gefragt."
echo "Du kannst Pfade mit ~ für dein Home-Verzeichnis angeben."
echo ""

# WoltLab Core
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "WoltLab Suite Core"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Der WoltLab Suite Core ist die Basis-Software, für die du Plugins entwickelst."
echo "Du benötigst eine lokale Kopie als Referenz für Auto-Completion."
echo ""
echo "Optionen:"
echo "  1. Automatischer Download: Das Script kann den Core automatisch herunterladen"
echo "  2. Manueller Pfad: Gib den Pfad zu einem bereits vorhandenen Core an"
echo "  3. Überspringen: Lass leer, um später hinzuzufügen"
echo ""
WOLTLAB_CORE=$(ask_path "Pfad zum WoltLab Core Verzeichnis (oder leer lassen)" "" "true")

# Automatischer Download falls leer
if [ -z "$WOLTLAB_CORE" ]; then
    echo ""
    read -p "Möchtest du den WoltLab Core automatisch herunterladen? (j/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[JjYy]$ ]]; then
        DEFAULT_CORE_DIR="$HOME/Documents/woltlab-core"
        echo ""
        CORE_DIR=$(ask_path "Ziel-Verzeichnis für WoltLab Core" "$DEFAULT_CORE_DIR" "false")

        if download_woltlab_core "$CORE_DIR"; then
            WOLTLAB_CORE="$CORE_DIR"
        else
            echo ""
            echo "⚠️  Download fehlgeschlagen. Du kannst den Core später manuell hinzufügen."
            echo "   Download-Seite: https://www.woltlab.com/de/woltlab-suite-download/"
            log "WARNING" "WoltLab Core Download fehlgeschlagen"
            WOLTLAB_CORE=""
        fi
    fi
elif [ -n "$WOLTLAB_CORE" ]; then
    # Validiere den angegebenen WoltLab Core Pfad
    echo ""
    if ! validate_woltlab_core "$WOLTLAB_CORE"; then
        echo ""
        read -p "Trotzdem verwenden? (j/n): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
            WOLTLAB_CORE=""
            log "INFO" "Benutzer hat ungültigen WoltLab Core Pfad abgelehnt"
        else
            log "WARNING" "Benutzer verwendet ungültigen WoltLab Core Pfad: $WOLTLAB_CORE"
        fi
    fi
fi

# Plugin-Verzeichnis
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Plugin-Verzeichnis"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Gib den Pfad zu deinem Plugin-Verzeichnis an."
echo ""
echo "  • Falls du bereits ein Plugin hast: Gib den Pfad an"
echo "  • Falls du ein neues Plugin erstellen möchtest: Lass leer"
echo "    (Du kannst später ein Plugin erstellen oder das Beispiel-Plugin verwenden)"
echo ""
PLUGIN_DIR=$(ask_path "Pfad zu deinem Plugin-Verzeichnis (oder leer lassen)" "" "true")

# Erstelle Plugin-Verzeichnis falls es nicht existiert
if [ -n "$PLUGIN_DIR" ] && [ ! -d "$PLUGIN_DIR" ]; then
    echo ""
    read -p "Verzeichnis existiert nicht. Soll es erstellt werden? (j/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[JjYy]$ ]]; then
        if mkdir -p "$PLUGIN_DIR" 2>/dev/null; then
            echo "✓ Verzeichnis erstellt: $PLUGIN_DIR"
            log "INFO" "Plugin-Verzeichnis erstellt: $PLUGIN_DIR"
        else
            echo "❌ Fehler: Konnte Verzeichnis nicht erstellen: $PLUGIN_DIR"
            log "ERROR" "Konnte Plugin-Verzeichnis nicht erstellen: $PLUGIN_DIR"
            PLUGIN_DIR=""
        fi

        if [ -n "$PLUGIN_DIR" ]; then
            echo ""
            echo "📝 Wichtige Informationen für dein Plugin:"
            echo "   • Dein Plugin-Verzeichnis: $PLUGIN_DIR"
            echo "   • Erstelle dort: package.xml, page.xml, files/, templates/"
            echo "   • Siehe Beispiel-Plugin: $SCRIPT_DIR/example-plugin/"
            echo "   • Dokumentation: https://docs.woltlab.com/6.0/getting-started/"
            echo ""
            read -p "Drücke ENTER, um fortzufahren... "
        fi
    else
        PLUGIN_DIR=""
        log "INFO" "Benutzer hat Plugin-Verzeichnis Erstellung abgelehnt"
    fi
fi

# Hauptplugins (optional)
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Hauptplugins (optional)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Hauptplugins sind andere Plugins, die du als Referenz verwenden möchtest."
echo "Dies ist optional – du kannst diesen Schritt überspringen."
echo ""
echo "Beispiele:"
echo "  • Basis-Plugins, die du häufig als Vorlage verwendest"
echo "  • Offizielle WoltLab Plugins zum Lernen"
echo ""
MAIN_PLUGINS=()
while true; do
    PLUGIN_PATH=$(ask_path "Pfad zu einem Hauptplugin (oder leer lassen, um zu beenden)" "" "true")
    if [ -z "$PLUGIN_PATH" ]; then
        break
    fi
    MAIN_PLUGINS+=("$PLUGIN_PATH")
    echo "✓ Hauptplugin hinzugefügt: $PLUGIN_PATH"
    log "INFO" "Hauptplugin hinzugefügt: $PLUGIN_PATH"
done

# Erstelle Konfigurationsdatei
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "  Schritt 3: Konfiguration speichern"
echo "═══════════════════════════════════════════════════════════════"
echo ""

CONFIG_FILE="$HOME/.woltlab-config"
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
echo ""

# Kopiere Scripts ins Plugin-Verzeichnis (falls angegeben)
if [ -n "$PLUGIN_DIR" ] && [ -d "$PLUGIN_DIR" ]; then
    echo "═══════════════════════════════════════════════════════════════"
    echo "  Schritt 4: Scripts kopieren"
    echo "═══════════════════════════════════════════════════════════════"
    echo ""
    echo "📋 Kopiere Build-Scripts ins Plugin-Verzeichnis..."

    # Prüfe ob Script-Dateien existieren
    scripts_to_copy=(
        "extract-plugin-files.sh"
        "update-tars.sh"
        "create-release.sh"
    )

    copy_success=true
    for script in "${scripts_to_copy[@]}"; do
        if [ -f "$SCRIPT_DIR/scripts/$script" ]; then
            if cp "$SCRIPT_DIR/scripts/$script" "$PLUGIN_DIR/" 2>/dev/null; then
                chmod +x "$PLUGIN_DIR/$script" 2>/dev/null || true
                echo "  ✓ $script"
                log "INFO" "Script kopiert: $script → $PLUGIN_DIR/"
            else
                echo "  ❌ Fehler beim Kopieren von $script"
                log "ERROR" "Fehler beim Kopieren von $script nach $PLUGIN_DIR/"
                copy_success=false
            fi
        else
            echo "  ⚠️  $script nicht gefunden in $SCRIPT_DIR/scripts/"
            log "WARNING" "Script nicht gefunden: $SCRIPT_DIR/scripts/$script"
        fi
    done

    if [ "$copy_success" = true ]; then
        echo ""
        echo "✓ Alle Scripts erfolgreich kopiert nach: $PLUGIN_DIR"
    else
        echo ""
        echo "⚠️  Einige Scripts konnten nicht kopiert werden"
    fi
    echo ""
fi

# Erstelle Workspace
echo "═══════════════════════════════════════════════════════════════"
echo "  Schritt 5: Workspace erstellen"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "📝 Erstelle Multi-Root Workspace für deine IDE..."
echo ""

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

# Erstelle Workspace-JSON mit Fehlerbehandlung
if cat > "$WORKSPACE_PATH" 2>/dev/null << EOF
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
then
    echo "✅ Workspace erstellt: $WORKSPACE_PATH"
    log "INFO" "Workspace erfolgreich erstellt: $WORKSPACE_PATH"
else
    echo "❌ Fehler: Konnte Workspace nicht erstellen: $WORKSPACE_PATH"
    echo "   Bitte prüfe die Schreibberechtigungen für: $(dirname "$WORKSPACE_PATH")"
    log "ERROR" "Konnte Workspace nicht erstellen: $WORKSPACE_PATH"
fi
echo ""

echo "═══════════════════════════════════════════════════════════════"
echo "  ✅ Installation erfolgreich abgeschlossen!"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "🎉 Alles ist eingerichtet! Hier sind die nächsten Schritte:"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. Entwicklungsumgebung öffnen"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Öffne das erstellte Workspace-File:"
echo ""
echo "   cursor $WORKSPACE_PATH"
echo "   # oder"
echo "   code $WORKSPACE_PATH"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2. IDE-Extensions installieren"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Die IDE wird automatisch Extensions vorschlagen:"
echo "  • Intelephense - PHP Auto-Completion"
echo "  • Xdebug - PHP Debugging"
echo "  • EditorConfig - Code-Formatierung"
echo ""
echo "Installiere diese Extensions und starte die IDE neu."
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3. Plugin-Entwicklung starten"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
if [ -n "$PLUGIN_DIR" ] && [ -d "$PLUGIN_DIR" ]; then
    echo "Dein Plugin-Verzeichnis: $PLUGIN_DIR"
    echo ""
    echo "Die Build-Scripts wurden bereits kopiert. Du kannst sie verwenden:"
    echo "  • ./extract-plugin-files.sh  # TAR-Dateien entpacken"
    echo "  • ./update-tars.sh            # TAR-Dateien aktualisieren"
    echo "  • ./create-release.sh VERSION # Release erstellen"
    echo ""
else
    echo "Falls du ein neues Plugin erstellen möchtest:"
    echo "  • Schau dir das Beispiel-Plugin an: example-plugin/"
    echo "  • Folge der Anleitung: https://docs.woltlab.com/6.0/getting-started/"
    echo ""
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4. Hilfe & Dokumentation"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📚 Weitere Informationen:"
echo "  • README.md - Übersicht und Schnellstart"
echo "  • docs/INSTALLATION.md - Detaillierte Installationsanleitung"
echo "  • docs/WORKSPACE-SETUP.md - Workspace-Konfiguration"
echo ""
echo "❓ Bei Problemen:"
echo "  • Schau in die Dokumentation in docs/"
echo "  • Prüfe die Log-Datei: $LOG_FILE"
echo "  • Öffne ein Issue auf GitHub"
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "Viel Erfolg bei der Plugin-Entwicklung! 🚀"
echo "═══════════════════════════════════════════════════════════════"
echo ""
log "INFO" "Installation erfolgreich abgeschlossen"

