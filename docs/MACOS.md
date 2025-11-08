# macOS Installation - Simple WoltLab Plugin Manager

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

## Übersicht

Diese Anleitung führt dich Schritt für Schritt durch die Installation auf macOS.

---

## Voraussetzungen

### 1. Homebrew installieren (empfohlen)

Homebrew ist ein Paket-Manager für macOS, der die Installation von Programmen vereinfacht.

**Installation:**
1. Öffne das Terminal (siehe unten)
2. Führe diesen Befehl aus:
   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```
3. Folge den Anweisungen auf dem Bildschirm

**💡 Alternative:** Falls du Homebrew nicht verwenden möchtest, kannst du die Programme auch manuell installieren (siehe unten).

### 2. Terminal öffnen

**Wo finde ich das Terminal auf Mac?**

**Option A: Über Spotlight (schnellste Methode)**
1. Drücke `Cmd + Leertaste`
2. Tippe "Terminal"
3. Drücke Enter

**Option B: Über Finder**
1. Öffne Finder
2. Gehe zu: Programme → Dienstprogramme
3. Doppelklicke auf "Terminal"

**Option C: Über Launchpad**
1. Öffne Launchpad (F4-Taste oder Wisch-Geste)
2. Tippe "Terminal"
3. Klicke auf Terminal

---

## Installation der Voraussetzungen

### PHP installieren

**Mit Homebrew (empfohlen):**
```bash
brew install php
```

**Manuell:**
1. Gehe zu: https://www.php.net/downloads.php
2. Wähle macOS und lade die neueste Version herunter
3. Installiere PHP nach dem Download

**Prüfen ob PHP installiert ist:**
```bash
php --version
```

**💡 Falls PHP nicht gefunden wird:**
- Stelle sicher, dass PHP im PATH ist
- Starte das Terminal neu
- Prüfe: `which php`

### Git installieren

**Mit Homebrew (empfohlen):**
```bash
brew install git
```

**Manuell:**
1. Gehe zu: https://git-scm.com/download/mac
2. Lade Git für macOS herunter
3. Installiere Git nach dem Download

**Prüfen ob Git installiert ist:**
```bash
git --version
```

**💡 Falls Git nicht gefunden wird:**
- macOS hat manchmal eine ältere Git-Version vorinstalliert
- Installiere die neueste Version über Homebrew oder die offizielle Website

### Cursor oder VSCode installieren

**Cursor (empfohlen):**
1. Gehe zu: https://cursor.sh/
2. Klicke auf "Download for Mac"
3. Öffne die heruntergeladene `.dmg` Datei
4. Ziehe Cursor in den Programme-Ordner
5. Öffne Cursor aus dem Programme-Ordner

**VSCode (Alternative):**
1. Gehe zu: https://code.visualstudio.com/
2. Klicke auf "Download for Mac"
3. Öffne die heruntergeladene `.zip` Datei
4. Ziehe VSCode in den Programme-Ordner
5. Öffne VSCode aus dem Programme-Ordner

**💡 Terminal-Befehle aktivieren:**
Nach der Installation musst du die Terminal-Befehle aktivieren:
- **Cursor:** Öffne Cursor → Einstellungen → Suche nach "Shell Command" → Aktiviere "Install 'cursor' command"
- **VSCode:** Öffne VSCode → Drücke `Cmd + Shift + P` → Tippe "Shell Command" → Wähle "Install 'code' command"

---

## Projekt herunterladen

### Option A: Mit Git (empfohlen)

1. Öffne das Terminal (siehe oben)
2. Navigiere zu einem Verzeichnis deiner Wahl:
   ```bash
   cd ~/Documents
   ```
3. Klone das Repository:
   ```bash
   git clone https://github.com/SunnyCueq/simple-woltlab-plugin-manager.git
   cd simple-woltlab-plugin-manager
   ```

### Option B: Als ZIP-Datei

1. Gehe zu: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
2. Klicke auf "Code" → "Download ZIP"
3. Entpacke die ZIP-Datei (Doppelklick)
4. Öffne ein Terminal im entpackten Ordner:
   - Rechtsklick auf den Ordner → "Services" → "New Terminal at Folder"
   - Oder: Terminal öffnen und `cd` zum Ordner

---

## Installation starten

1. Stelle sicher, dass du im richtigen Verzeichnis bist:
   ```bash
   cd ~/Documents/simple-woltlab-plugin-manager
   # Oder wo auch immer du das Projekt heruntergeladen hast
   ```

2. Starte die Installation:
   ```bash
   ./install.sh
   ```

**💡 Falls der Befehl nicht funktioniert:**
- Stelle sicher, dass die Datei ausführbar ist: `chmod +x install.sh`
- Versuche: `bash install.sh` oder `sh install.sh`

---

## Workspace öffnen

Nach der Installation findest du eine Datei namens `woltlab-plugin-dev.code-workspace`.

**Wo finde ich sie?**
- Meist in `~/Documents/` oder im übergeordneten Verzeichnis deines Plugins

**Wie öffne ich sie?**

**Option A: Über das Terminal**
```bash
cd ~/Documents  # Oder wo die Datei liegt
cursor woltlab-plugin-dev.code-workspace
# Oder:
code woltlab-plugin-dev.code-workspace
```

**Option B: Über Finder**
1. Öffne Finder
2. Navigiere zum Verzeichnis wo die `.code-workspace` Datei liegt
3. Doppelklicke auf die Datei
4. Falls sich nichts tut: Rechtsklick → "Öffnen mit" → Cursor oder VSCode

**💡 Falls der Befehl nicht funktioniert:**
- Stelle sicher, dass Cursor/VSCode installiert ist
- Aktiviere die Terminal-Befehle (siehe oben)
- Versuche die Datei per Doppelklick zu öffnen

---

## macOS-spezifische Tipps

### Pfade auf macOS

- **Home-Verzeichnis:** `~/` oder `/Users/DeinName/`
- **Dokumente:** `~/Documents/`
- **Downloads:** `~/Downloads/`
- **Programme:** `/Applications/`

### Terminal-Tastenkürzel

- **Neues Terminal:** `Cmd + T`
- **Terminal schließen:** `Cmd + W`
- **Terminal beenden:** `Cmd + Q`
- **Befehl abbrechen:** `Ctrl + C`
- **Terminal löschen:** `Cmd + K`

### Berechtigungen

Falls du Probleme mit Berechtigungen hast:
```bash
# Datei ausführbar machen
chmod +x install.sh

# Scripts ausführbar machen
chmod +x scripts/*.sh
```

---

## Troubleshooting

### "Command not found"

**Problem:** Terminal findet einen Befehl nicht

**Lösung:**
1. Prüfe ob das Programm installiert ist: `which php` (oder `which git`, etc.)
2. Stelle sicher, dass der PATH korrekt ist: `echo $PATH`
3. Starte das Terminal neu
4. Bei Homebrew: `brew doctor` ausführen

### "Permission denied"

**Problem:** Datei ist nicht ausführbar

**Lösung:**
```bash
chmod +x dateiname.sh
```

### "Homebrew nicht gefunden"

**Problem:** Homebrew-Befehle funktionieren nicht

**Lösung:**
1. Installiere Homebrew (siehe oben)
2. Prüfe die Installation: `brew doctor`
3. Stelle sicher, dass Homebrew im PATH ist

### Workspace öffnet nicht

**Problem:** Doppelklick auf `.code-workspace` Datei funktioniert nicht

**Lösung:**
1. Rechtsklick → "Öffnen mit" → Cursor oder VSCode
2. Oder: Terminal-Befehle aktivieren und über Terminal öffnen
3. Prüfe ob Cursor/VSCode installiert ist

---

## Nächste Schritte

- **[INSTALLATION.md](INSTALLATION.md)** - Vollständige Installationsanleitung
- **[WORKSPACE-SETUP.md](WORKSPACE-SETUP.md)** - Workspace-Konfiguration
- **[IDE-SETUP-CURSOR.md](IDE-SETUP-CURSOR.md)** - Cursor IDE Setup
- **[IDE-SETUP-VSCODE.md](IDE-SETUP-VSCODE.md)** - VSCode Setup

---

**Letzte Aktualisierung:** 2025-01-08

