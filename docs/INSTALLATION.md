# Installation - Simple WoltLab Plugin Manager

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

**Letzte Aktualisierung:** 2025-11-08  
**Status:** Aktuell

---

Diese Anleitung führt dich Schritt für Schritt durch die Installation und Einrichtung des Simple WoltLab Plugin Managers. Alles wird genau erklärt – auch wenn du noch nie mit Terminal-Befehlen gearbeitet hast.

---

## 📥 Was du brauchst (Download-Links)

**Bevor du startest, lade dir diese Programme herunter:**

### 1. Git (zum Herunterladen des Projekts)

- **Download:** https://git-scm.com/downloads
- Wähle dein Betriebssystem (Windows, Mac, Linux)
- Installiere Git nach dem Download
- **💡 Alternative:** Falls du Git nicht installieren möchtest, kannst du das Projekt auch als ZIP-Datei herunterladen (siehe unten)

### 2. Eine IDE (Code-Editor - wähle einen)

- **Cursor** (empfohlen): https://cursor.sh/
  - Download und installieren
  - Kostenlos und sehr benutzerfreundlich
- **VSCode** (Alternative): https://code.visualstudio.com/
  - Download und installieren
  - Kostenlos, sehr verbreitet

### 3. PHP (wird automatisch geprüft)

- **Download:** https://www.php.net/downloads.php
- Oder installiere es über deinen Paket-Manager (siehe unten)
- **💡 Keine Sorge:** Das Install-Script prüft automatisch, ob PHP installiert ist

### 4. WoltLab Suite Core (wird während Installation abgefragt)

- **Download:** https://www.woltlab.com/de/woltlab-suite-download/
- Direkter Download: [woltlab-suite-6.1.14.zip](https://assets.woltlab.com/release/woltlab-suite-6.1.14.zip)
- **💡 Tipp:** Du kannst den Core auch später herunterladen – das Install-Script erinnert dich daran

---

## 🚀 Schritt-für-Schritt Installation

### Schritt 1: Terminal öffnen

**Wo öffne ich ein Terminal?**

**Windows:**
- Drücke `Windows + R`
- Tippe `cmd` oder `powershell`
- Drücke Enter

**Mac:**
- Drücke `Cmd + Leertaste`
- Tippe "Terminal"
- Drücke Enter

**Linux:**
- Drücke `Ctrl + Alt + T`
- Oder suche nach "Terminal" im Menü

**💡 Alternative: Terminal in der IDE**
- Nach der Installation kannst du auch das Terminal in Cursor/VSCode verwenden
- Drücke `Ctrl + ~` (Windows/Linux) oder `Cmd + ~` (Mac)
- Oder: Menü → Terminal → Neues Terminal

### Schritt 2: Projekt herunterladen

**Option A: Mit Git (empfohlen)**

1. Öffne ein Terminal (siehe Schritt 1)
2. Navigiere zu einem Verzeichnis deiner Wahl:
   ```bash
   # Windows
   cd C:\Users\DeinName\Documents
   
   # Mac/Linux
   cd ~/Documents
   ```
3. Klone das Repository:
   ```bash
   git clone https://github.com/SunnyCueq/simple-woltlab-plugin-manager.git
   cd simple-woltlab-plugin-manager
   ```

**💡 Falls Git nicht funktioniert:**
- Stelle sicher, dass Git installiert ist: `git --version`
- Falls nicht: Installiere Git (siehe Download-Links oben)
- Oder verwende Option B

**Option B: Als ZIP-Datei (ohne Git)**

1. Gehe zu: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
2. Klicke auf "Code" → "Download ZIP"
3. Entpacke die ZIP-Datei (Doppelklick)
4. Öffne ein Terminal im entpackten Ordner:
   - **Windows:** Rechtsklick im Ordner → "Git Bash here" oder "PowerShell here"
   - **Mac:** Rechtsklick im Ordner → "Services" → "New Terminal at Folder"
   - **Linux:** Rechtsklick im Ordner → "Terminal öffnen"

### Schritt 3: Installation starten

**Wichtig:** Du musst im richtigen Verzeichnis sein!

1. Prüfe ob du im richtigen Verzeichnis bist:
   ```bash
   # Du solltest "simple-woltlab-plugin-manager" sehen
   ls
   # Oder auf Windows:
   dir
   ```

2. Falls nicht, navigiere dorthin:
   ```bash
   cd simple-woltlab-plugin-manager
   ```

3. Starte die Installation:
   ```bash
   ./install.sh
   ```

**💡 Falls der Befehl nicht funktioniert:**

**Windows:**
```bash
bash install.sh
# Oder:
sh install.sh
```

**Mac/Linux:**
```bash
# Stelle sicher, dass die Datei ausführbar ist:
chmod +x install.sh
./install.sh
```

**Was passiert jetzt?**

Das Script führt dich automatisch durch alles:
- ✅ Es prüft, ob alle benötigten Programme installiert sind (PHP, Git, etc.)
- ✅ Falls etwas fehlt, versucht es automatisch zu installieren
- ✅ Es fragt dich nach den benötigten Pfaden (WoltLab Core, Plugin-Verzeichnis)
- ✅ Es erstellt automatisch die Konfigurationsdateien
- ✅ Es richtet deine Entwicklungsumgebung ein
- ✅ Es kopiert alle benötigten Scripts an die richtigen Stellen

**Du musst nur die Fragen beantworten – der Rest passiert automatisch!**

### Schritt 4: Workspace öffnen

**Nach der Installation findest du eine Datei namens `woltlab-plugin-dev.code-workspace`**

**Wo finde ich diese Datei?**

- Meist im übergeordneten Verzeichnis deines Plugins
- Oder in deinem Home-Verzeichnis:
  - **Windows:** `C:\Users\DeinName\`
  - **Mac/Linux:** `~/` oder `/Users/DeinName/`

**Wie öffne ich sie?**

**Option A: Über das Terminal (einfachste Methode)**

1. Öffne ein Terminal (siehe Schritt 1)
2. Navigiere zum Verzeichnis wo die `.code-workspace` Datei liegt:
   ```bash
   # Beispiel - passe den Pfad an
   cd ~/Documents
   # Oder auf Windows:
   cd C:\Users\DeinName\Documents
   ```
3. Führe einen dieser Befehle aus:

```bash
# Mit Cursor (empfohlen)
cursor woltlab-plugin-dev.code-workspace

# Oder mit VSCode
code woltlab-plugin-dev.code-workspace
```

**💡 Falls der Befehl nicht funktioniert:**
- Stelle sicher, dass Cursor/VSCode installiert ist
- Aktiviere die Terminal-Befehle in der IDE:
  - **Cursor:** Einstellungen → Suche nach "Shell Command" → Aktiviere "Install 'cursor' command"
  - **VSCode:** Drücke `Ctrl + Shift + P` (Windows/Linux) oder `Cmd + Shift + P` (Mac) → Tippe "Shell Command" → Wähle "Install 'code' command"
- Oder verwende Option B

**Option B: Über den Datei-Explorer/Finder**

1. Öffne den Datei-Explorer (Windows) oder Finder (Mac)
2. Navigiere zum Verzeichnis wo die `.code-workspace` Datei liegt
3. **Doppelklicke** auf die Datei `woltlab-plugin-dev.code-workspace`
4. Falls sich nichts tut:
   - **Windows:** Rechtsklick → "Öffnen mit" → Cursor oder VSCode
   - **Mac:** Rechtsklick → "Öffnen mit" → Cursor oder VSCode

**💡 Falls die Datei nicht gefunden wird:**
- Prüfe ob die Installation erfolgreich war
- Suche nach der Datei: `find ~ -name "woltlab-plugin-dev.code-workspace"` (Mac/Linux)
- Führe `./install.sh` erneut aus

### Schritt 5: IDE-Extensions installieren

**Nach dem Öffnen des Workspaces:**

1. Die IDE wird automatisch Extensions vorschlagen
2. Klicke auf "Install" bei diesen Extensions:
   - **Intelephense** - PHP Auto-Completion (wichtig!)
   - **Xdebug** - PHP Debugging (optional)
   - **EditorConfig** - Code-Formatierung (optional)

3. Starte die IDE neu (schließen und wieder öffnen)

**💡 Falls Extensions nicht vorgeschlagen werden:**
- Öffne die Extensions-Ansicht: `Ctrl + Shift + X` (Windows/Linux) oder `Cmd + Shift + X` (Mac)
- Suche nach "Intelephense" und installiere es manuell

### Schritt 6: Scripts ins Plugin-Verzeichnis kopieren

**Falls du ein bestehendes Plugin hast:**

Die Scripts wurden bereits automatisch kopiert! Du kannst sie direkt verwenden.

**Falls du ein neues Plugin erstellen möchtest:**

1. Erstelle ein neues Verzeichnis für dein Plugin:
   ```bash
   mkdir ~/Documents/mein-plugin
   # Oder auf Windows:
   mkdir C:\Users\DeinName\Documents\mein-plugin
   ```

2. Kopiere die Scripts:
   ```bash
   cp scripts/*.sh ~/Documents/mein-plugin/
   chmod +x ~/Documents/mein-plugin/*.sh
   ```

**💡 Tipp:** Siehe [PLUGIN-NAMING.md](PLUGIN-NAMING.md) für die korrekte Benennung deines Plugins.

---

## ✅ Installation abgeschlossen!

**Was wurde eingerichtet?**

- ✅ Konfigurationsdatei: `~/.woltlab-config` (oder `C:\Users\DeinName\.woltlab-config` auf Windows)
- ✅ Workspace-File: `woltlab-plugin-dev.code-workspace`
- ✅ Scripts im Plugin-Verzeichnis (falls angegeben)
- ✅ IDE-Extensions installiert

**Nächste Schritte:**

1. **Plugin erstellen:** Siehe [example-plugin/](../example-plugin/) für ein Beispiel
2. **Workspace verstehen:** Siehe [WORKSPACE-SETUP.md](WORKSPACE-SETUP.md)
3. **Entwickler-Werkzeuge:** Siehe [DEVELOPER-TOOLS.md](DEVELOPER-TOOLS.md)

---

## 🔧 Voraussetzungen installieren

### PHP installieren

**Windows:**
- Download: https://www.php.net/downloads.php
- Wähle "Windows Downloads"
- Installiere PHP nach dem Download

**Mac:**
```bash
brew install php
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install php
```

**Linux (Arch/CachyOS):**
```bash
sudo pacman -S php
```

**Prüfen ob PHP installiert ist:**
```bash
php --version
```

### Git installieren

**Windows:**
- Download: https://git-scm.com/download/win
- Installiere Git nach dem Download

**Mac:**
```bash
brew install git
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt install git
```

**Linux (Arch/CachyOS):**
```bash
sudo pacman -S git
```

**Prüfen ob Git installiert ist:**
```bash
git --version
```

---

## 📁 Verzeichnisstruktur

Nach der Installation sollte deine Verzeichnisstruktur etwa so aussehen:

```
~/Documents/                    # Oder C:\Users\DeinName\Documents
├── woltlab core/              # WoltLab Suite Core
├── mein-plugin/                # Dein Plugin
│   ├── extract-plugin-files.sh
│   ├── update-tars.sh
│   ├── create-release.sh
│   └── ...
└── woltlab-plugin-dev.code-workspace  # Workspace-File
```

---

## 🐛 Troubleshooting

### "PHP nicht gefunden"

**Problem:** Das Script findet PHP nicht

**Lösung:**
1. Installiere PHP (siehe oben)
2. Prüfe ob PHP installiert ist: `php --version`
3. Falls nicht gefunden, starte das Terminal neu
4. Führe `./install.sh` erneut aus

**💡 Alternative:** Das Script versucht automatisch PHP zu installieren, falls möglich

### "Git nicht gefunden"

**Problem:** Das Script findet Git nicht

**Lösung:**
1. Installiere Git (siehe oben)
2. Prüfe ob Git installiert ist: `git --version`
3. Falls nicht gefunden, starte das Terminal neu
4. Führe `./install.sh` erneut aus

**💡 Alternative:** Lade das Projekt als ZIP-Datei herunter (siehe Schritt 2, Option B)

### "Workspace öffnet nicht"

**Problem:** Der Befehl `cursor` oder `code` funktioniert nicht

**Lösung:**
1. Stelle sicher, dass Cursor/VSCode installiert ist
2. Aktiviere die Terminal-Befehle (siehe Schritt 4)
3. Versuche die Datei per Doppelklick zu öffnen (siehe Schritt 4, Option B)
4. Prüfe ob die Datei existiert: `ls -la woltlab-plugin-dev.code-workspace` (Mac/Linux) oder `dir woltlab-plugin-dev.code-workspace` (Windows)

### "Intelephense zeigt keine Auto-Completion"

**Problem:** PHP Auto-Completion funktioniert nicht

**Lösung:**
1. Prüfe ob Intelephense installiert ist (Extensions-Ansicht)
2. Prüfe die `intelephense.environment.includePaths` im Workspace
3. Stelle sicher, dass der WoltLab Core-Pfad korrekt ist
4. Starte die IDE neu
5. Löschen den Intelephense-Cache:
   ```bash
   rm -rf ~/.cache/intelephense/
   # Oder auf Windows:
   rmdir /s C:\Users\DeinName\.cache\intelephense
   ```

### "Permission denied" (Mac/Linux)

**Problem:** Datei ist nicht ausführbar

**Lösung:**
```bash
chmod +x install.sh
chmod +x scripts/*.sh
```

### "Das Script fragt nach sudo-Passwort"

**Problem:** Automatische Installation benötigt Admin-Rechte

**Lösung:**
- Das ist normal – das Script versucht Programme automatisch zu installieren
- Gib dein Passwort ein (wird nicht angezeigt, das ist normal)
- Falls du das nicht möchtest, installiere die Programme manuell (siehe oben)

### "Installation fehlgeschlagen in Zeile X"

**Problem:** Das Script ist unerwartet fehlgeschlagen

**Lösung:**
1. Prüfe die Log-Datei:
   ```bash
   cat /tmp/woltlab-install-YYYYMMDD-HHMMSS.log
   ```
   (Der genaue Pfad wird im Fehler angezeigt)

2. Häufige Ursachen:
   - Fehlende Schreibrechte → Prüfe Berechtigungen für das Zielverzeichnis
   - Fehlende Abhängigkeiten → Installiere PHP, Git, tar manuell
   - Ungültige Pfade → Prüfe ob die angegebenen Pfade korrekt sind

3. Führe das Script erneut aus nach Behebung des Problems

### "WoltLab Core Struktur unvollständig"

**Problem:** Der angegebene WoltLab Core Pfad ist ungültig

**Lösung:**
- Prüfe ob folgende Pfade existieren:
  - `lib/` - WoltLab Bibliotheken
  - `wcf/` - WoltLab Community Framework
  - `wcf/global.php` - Hauptdatei
  - `lib/system/` - System-Klassen
- Lade den Core neu herunter falls Dateien fehlen: https://www.woltlab.com/de/woltlab-suite-download/
- Entpacke den Core vollständig

### "Maximale Anzahl von Versuchen erreicht"

**Problem:** Du hast 3x einen ungültigen Pfad eingegeben

**Lösung:**
- Das Script bricht ab um Endlosschleifen zu vermeiden
- Starte das Script erneut mit `./install.sh`
- Bereite die korrekten Pfade vor dem Start vor
- Prüfe Pfade mit `ls /pfad/zum/verzeichnis` vor der Eingabe

### "Konnte Workspace nicht erstellen"

**Problem:** Workspace-Datei konnte nicht geschrieben werden

**Lösung:**
1. Prüfe Schreibrechte:
   ```bash
   # Für Plugin-Verzeichnis Workspace:
   ls -ld $(dirname /pfad/zu/deinem/plugin/)

   # Für Home-Verzeichnis Workspace:
   ls -ld $HOME
   ```

2. Erstelle Verzeichnis manuell falls nötig:
   ```bash
   mkdir -p $(dirname /pfad/zum/workspace/)
   ```

3. Führe das Script erneut aus

### "Scripts konnten nicht kopiert werden"

**Problem:** Build-Scripts konnten nicht ins Plugin-Verzeichnis kopiert werden

**Lösung:**
- Prüfe Schreibrechte für Plugin-Verzeichnis:
  ```bash
  ls -ld /pfad/zu/deinem/plugin/
  ```
- Prüfe ob Scripts im Toolkit vorhanden sind:
  ```bash
  ls -l scripts/
  ```
- Kopiere Scripts manuell falls nötig:
  ```bash
  cp scripts/*.sh /pfad/zu/deinem/plugin/
  chmod +x /pfad/zu/deinem/plugin/*.sh
  ```

---

## 📚 Weitere Hilfe

- **[README.md](../README.md)** - Übersicht und Schnellstart
- **[WORKSPACE-SETUP.md](WORKSPACE-SETUP.md)** - Workspace-Konfiguration im Detail
- **[IDE-SETUP-CURSOR.md](IDE-SETUP-CURSOR.md)** - Cursor IDE Setup
- **[IDE-SETUP-VSCODE.md](IDE-SETUP-VSCODE.md)** - VSCode Setup
- **[MACOS.md](MACOS.md)** - macOS-spezifische Anleitung
- **[LINUX-CACHYOS.md](LINUX-CACHYOS.md)** - CachyOS-spezifische Anleitung
- **[WINDOWS-WSL.md](WINDOWS-WSL.md)** - Windows WSL Setup

**Bei Problemen:**
- Öffne ein [Issue auf GitHub](https://github.com/SunnyCueq/simple-woltlab-plugin-manager/issues)
- Kontaktiere die WoltLab Community

---

**Letzte Aktualisierung:** 2025-11-08
