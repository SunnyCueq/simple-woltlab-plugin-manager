# Installation - Simple WoltLab Plugin Manager

**Letzte Aktualisierung:** 2025-01-08  
**Status:** Aktuell

**Letzte Änderung:** Initiale Version
- Grund: Erstellung der Installationsanleitung

---

Diese Anleitung führt Sie Schritt für Schritt durch die Installation und Einrichtung des Simple WoltLab Plugin Managers.

## Voraussetzungen

Bevor Sie beginnen, stellen Sie sicher, dass folgende Software installiert ist:

### Erforderlich

- **PHP 8.0 oder höher**
  ```bash
  php --version
  ```
  
- **Git**
  ```bash
  git --version
  ```
  
- **tar** (meist vorinstalliert)
  ```bash
  tar --version
  ```

### Optional (aber empfohlen)

- **Cursor IDE** oder **VSCode**
- **WoltLab Suite Core** (für Referenz und Auto-Completion)

### WoltLab Suite Core herunterladen

Für die Plugin-Entwicklung benötigen Sie eine lokale Kopie des WoltLab Suite Core.

**Aktuelle Version:**
- Download: [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- Direkter Download (aktuellste Version): [woltlab-suite-6.1.14.zip](https://assets.woltlab.com/release/woltlab-suite-6.1.14.zip)

**Ältere Versionen:**
Falls Sie mit einer älteren Version arbeiten möchten, können Sie spezifische Versionen herunterladen:
- Beispiel: [woltlab-suite-6.0.22.zip](https://assets.woltlab.com/release/woltlab-suite-6.0.22.zip)
- URL-Pattern: `https://assets.woltlab.com/release/woltlab-suite-{VERSION}.zip`

**Systemüberprüfung:**
Bevor Sie WoltLab Suite installieren, empfehlen wir die Verwendung des Systemüberprüfungs-Skripts:
- Download: [test.php](https://www.woltlab.com/media/302-test-php/)
- Laden Sie die Datei auf Ihren Server hoch und rufen Sie sie im Browser auf
- Das Skript prüft alle Systemvoraussetzungen für WoltLab Suite

## Schritt 1: Repository klonen

```bash
git clone https://github.com/SunnyCueq/simple-woltlab-plugin-manager.git
cd simple-woltlab-plugin-manager
```

## Schritt 2: Installations-Script ausführen

Das Installations-Script führt Sie interaktiv durch die Einrichtung:

```bash
./install.sh
```

Das Script fragt Sie nach:

1. **WoltLab Core Verzeichnis:** Pfad zu Ihrer WoltLab Suite Core Installation
2. **Plugin-Verzeichnis:** Pfad zu Ihrem Plugin-Verzeichnis (oder leer für neues Plugin)
3. **Hauptplugins:** Optionale Pfade zu Hauptplugins, die als Referenz dienen

### Beispiel-Antworten

```
WoltLab Core Verzeichnis: /home/benny/Dokumente/woltlab core
Plugin-Verzeichnis: /home/benny/Dokumente/mein-plugin
Hauptplugin 1: /home/benny/Dokumente/basis-plugin
```

## Schritt 3: Workspace öffnen

Nach der Installation wird ein Workspace-File erstellt. Öffnen Sie es in Ihrer IDE:

```bash
# Cursor
cursor woltlab-plugin-dev.code-workspace

# VSCode
code woltlab-plugin-dev.code-workspace
```

## Schritt 4: IDE-Extensions installieren

Die empfohlenen Extensions werden automatisch vorgeschlagen:

- **Intelephense** - PHP Auto-Completion
- **Xdebug** - PHP Debugging
- **EditorConfig** - Code-Formatierung

Installieren Sie diese Extensions und starten Sie die IDE neu.

## Schritt 5: Scripts ins Plugin-Verzeichnis kopieren

Falls Sie ein bestehendes Plugin haben, wurden die Scripts bereits kopiert. Falls nicht:

```bash
cp scripts/*.sh /pfad/zu/ihrem/plugin/
chmod +x /pfad/zu/ihrem/plugin/*.sh
```

## Schritt 6: Konfiguration prüfen

Die Konfiguration wird in `~/.woltlab-config` gespeichert. Sie können sie jederzeit bearbeiten:

```bash
cat ~/.woltlab-config
```

## Verzeichnisstruktur

Nach der Installation sollte Ihre Verzeichnisstruktur etwa so aussehen:

```
~/Dokumente/
├── woltlab core/              # WoltLab Suite Core
├── mein-plugin/              # Ihr Plugin
│   ├── extract-plugin-files.sh
│   ├── update-tars.sh
│   ├── create-release.sh
│   └── ...
└── woltlab-plugin-dev.code-workspace  # Workspace-File
```

## Nächste Schritte

- **[WORKSPACE-SETUP.md](WORKSPACE-SETUP.md)** - Workspace-Konfiguration im Detail
- **[IDE-SETUP-CURSOR.md](IDE-SETUP-CURSOR.md)** - Cursor IDE Setup
- **[IDE-SETUP-VSCODE.md](IDE-SETUP-VSCODE.md)** - VSCode Setup
- **[LINUX-CACHYOS.md](LINUX-CACHYOS.md)** - CachyOS-spezifische Anleitung

## Troubleshooting

### "PHP nicht gefunden"

Installieren Sie PHP:
```bash
# CachyOS/Arch
sudo pacman -S php

# Ubuntu/Debian
sudo apt install php

# macOS
brew install php
```

### "Git nicht gefunden"

Installieren Sie Git:
```bash
# CachyOS/Arch
sudo pacman -S git

# Ubuntu/Debian
sudo apt install git

# macOS
brew install git
```

### "Workspace öffnet nicht"

Prüfen Sie, ob der Pfad korrekt ist:
```bash
ls -la woltlab-plugin-dev.code-workspace
```

Falls das File nicht existiert, führen Sie `./install.sh` erneut aus.

### "Intelephense zeigt keine Auto-Completion"

1. Prüfen Sie die `intelephense.environment.includePaths` im Workspace
2. Starten Sie die IDE neu
3. Löschen Sie den Intelephense-Cache: `rm -rf ~/.cache/intelephense/`

## Weitere Hilfe

- Siehe [README.md](../README.md) für eine Übersicht
- Öffnen Sie ein Issue auf GitHub bei Problemen
- Kontaktieren Sie die Community

