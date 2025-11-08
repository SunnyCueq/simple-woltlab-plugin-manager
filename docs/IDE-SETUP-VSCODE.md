# IDE Setup - VSCode

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

**Letzte Aktualisierung:** 2025-01-08  
**Status:** Aktuell

---

Diese Anleitung erklärt, wie du VSCode für die WoltLab Plugin-Entwicklung einrichtest. Schritt für Schritt, einfach erklärt.

---

## 📥 VSCode installieren

**Falls du VSCode noch nicht hast:**

1. **Download:** https://code.visualstudio.com/
2. Klicke auf "Download for [dein Betriebssystem]"
3. Installiere VSCode nach dem Download
4. Öffne VSCode

**💡 Tipp:** VSCode ist kostenlos und sehr verbreitet.

---

## ✅ Voraussetzungen

Bevor du startest, stelle sicher dass:

- ✅ VSCode installiert ist
- ✅ Workspace erstellt wurde (siehe [WORKSPACE-SETUP.md](WORKSPACE-SETUP.md))
- ✅ WoltLab Core verfügbar ist (wird während Installation abgefragt)

**Falls noch nicht erledigt:** Führe zuerst `./install.sh` aus (siehe [INSTALLATION.md](INSTALLATION.md))

---

## 🚀 Schritt 1: Workspace öffnen

**Wie öffne ich den Workspace?**

**Option A: Über das Terminal (empfohlen)**

1. Öffne ein Terminal (siehe [INSTALLATION.md](INSTALLATION.md))
2. Navigiere zum Verzeichnis wo die `.code-workspace` Datei liegt:
   ```bash
   cd ~/Documents  # Beispiel - passe den Pfad an
   ```
3. Führe aus:
   ```bash
   code woltlab-plugin-dev.code-workspace
   ```

**💡 Falls der Befehl nicht funktioniert:**
- Aktiviere die Terminal-Befehle in VSCode:
  1. Öffne VSCode
  2. Drücke `Ctrl + Shift + P` (Windows/Linux) oder `Cmd + Shift + P` (Mac)
  3. Tippe: "Shell Command: Install 'code' command in PATH"
  4. Drücke Enter
- Oder verwende Option B

**Option B: Über das VSCode-Menü**

1. Öffne VSCode
2. Gehe zu: **File → Open Workspace from File...**
3. Wähle die Datei `woltlab-plugin-dev.code-workspace`
4. Klicke auf "Öffnen"

**Option C: Per Doppelklick**

1. Öffne den Datei-Explorer (Windows) oder Finder (Mac)
2. Navigiere zum Verzeichnis wo die `.code-workspace` Datei liegt
3. **Doppelklicke** auf die Datei
4. Falls sich nichts tut: Rechtsklick → "Öffnen mit" → VSCode

---

## 🔌 Schritt 2: Extensions installieren

**Nach dem Öffnen des Workspaces:**

VSCode schlägt automatisch Extensions vor. Installiere diese:

### Intelephense (Pflicht - sehr wichtig!)

**Was ist das?**
Intelephense gibt dir Auto-Completion (Vorschläge) für PHP-Code. Ohne diese Extension funktioniert die Auto-Completion für WoltLab-Klassen nicht.

**Installation:**

**Option A: Automatisch (empfohlen)**
- VSCode schlägt die Extension automatisch vor
- Klicke auf "Install"

**Option B: Manuell**
1. Drücke `Ctrl + Shift + X` (Windows/Linux) oder `Cmd + Shift + X` (Mac)
   - Das öffnet die Extensions-Ansicht
2. Suche nach: "Intelephense"
3. Klicke auf "Install" bei "Intelephense" von Ben Mewburn

**Wie prüfe ich ob es installiert ist?**
- In der Extensions-Ansicht sollte "Intelephense" als installiert angezeigt werden
- Oder: Drücke `Ctrl + Shift + P` → Tippe "Intelephense" → Du solltest Befehle sehen

### Xdebug (Optional - für Debugging)

**Was ist das?**
Xdebug hilft dir beim Debuggen (Fehlersuche) von PHP-Code.

**Installation:**
1. Extensions-Ansicht öffnen: `Ctrl + Shift + X` (Windows/Linux) oder `Cmd + Shift + X` (Mac)
2. Suche nach: "PHP Debug"
3. Installiere: "PHP Debug" von Xdebug

**💡 Tipp:** Falls du noch nicht debuggen musst, kannst du diese Extension später installieren.

### EditorConfig (Empfohlen - für Code-Formatierung)

**Was ist das?**
EditorConfig sorgt für einheitliche Code-Formatierung.

**Installation:**
1. Extensions-Ansicht öffnen: `Ctrl + Shift + X` (Windows/Linux) oder `Cmd + Shift + X` (Mac)
2. Suche nach: "EditorConfig"
3. Installiere: "EditorConfig for VS Code"

---

## ⚙️ Schritt 3: Intelephense konfigurieren

**Gute Nachricht:** Die Konfiguration ist bereits im Workspace enthalten! Du musst normalerweise nichts ändern.

**Was ist bereits konfiguriert?**

```json
{
  "intelephense.environment.includePaths": [
    "/pfad/zum/woltlab/core/lib"
  ],
  "intelephense.environment.phpVersion": "8.4.0",
  "intelephense.diagnostics.undefinedTypes": false,
  "intelephense.diagnostics.undefinedMethods": false
}
```

**Falls du die Konfiguration anpassen möchtest:**

**Option A: Über die Settings-UI**
1. Drücke `Ctrl + ,` (Windows/Linux) oder `Cmd + ,` (Mac)
   - Das öffnet die Settings
2. Suche nach: "intelephense"
3. Passe die Einstellungen an

**Option B: Direkt in settings.json**
1. Drücke `Ctrl + Shift + P` (Windows/Linux) oder `Cmd + Shift + P` (Mac)
2. Tippe: "Preferences: Open Workspace Settings (JSON)"
3. Bearbeite die Einstellungen
4. Speichere die Datei

**💡 Wichtig:** Ändere nur etwas, wenn du weißt was du tust. Die Standard-Konfiguration ist bereits optimal!

---

## 🔄 Schritt 4: IDE neustarten

**Nach der Installation der Extensions:**

1. **Schließe VSCode komplett**
   - Nicht nur das Fenster, sondern die gesamte Anwendung
   - **Windows:** Rechtsklick auf Taskbar → "Fenster schließen"
   - **Mac:** `Cmd + Q`
   - **Linux:** Schließe alle Fenster

2. **Öffne VSCode erneut**

3. **Öffne das Workspace wieder:**
   ```bash
   code woltlab-plugin-dev.code-workspace
   ```

**Warum neustarten?**
- Extensions werden erst nach Neustart vollständig aktiviert
- Intelephense muss neu initialisiert werden

---

## ✅ Schritt 5: Auto-Completion testen

**Funktioniert alles?**

Erstelle eine Test-Datei in deinem Plugin:

1. Öffne dein Plugin-Verzeichnis im Workspace
2. Erstelle eine neue Datei: `test.php`
3. Füge diesen Code ein:

```php
<?php
use wcf\system\WCF;
use wcf\data\DatabaseObject;

// Auto-Completion sollte hier funktionieren:
$db = WCF::getDB();  // <- Tippe "WCF::" und drücke Ctrl+Space
```

**Was solltest du sehen?**

- ✅ Keine roten Wellenlinien bei `use wcf\...` Statements
- ✅ Wenn du `WCF::` tippst und `Ctrl + Space` (Windows/Linux) oder `Cmd + Space` (Mac) drückst, solltest du Methoden sehen
- ✅ Auto-Completion bei `FormContainer::create()` zeigt Parameter
- ✅ Keine "Class not found" Fehler

**💡 Falls Auto-Completion nicht funktioniert:** Siehe Troubleshooting unten.

---

## 🐛 Troubleshooting

### "Class not found" Fehler bleiben

**Problem:** Intelephense findet WoltLab-Klassen nicht

**Lösung Schritt für Schritt:**

1. **Prüfe includePaths im Workspace:**
   ```bash
   cat woltlab-plugin-dev.code-workspace | grep includePaths
   ```
   - Du solltest einen Pfad zu `.../woltlab/core/lib` sehen

2. **Prüfe ob Core-Pfad korrekt ist:**
   ```bash
   ls -la "/pfad/zum/woltlab/core/lib"
   # Oder auf Windows:
   dir "C:\pfad\zum\woltlab\core\lib"
   ```
   - Das Verzeichnis sollte existieren

3. **Intelephense-Cache löschen:**
   ```bash
   rm -rf ~/.cache/intelephense/  # Mac/Linux
   # Oder auf Windows:
   rmdir /s C:\Users\DeinName\.cache\intelephense
   ```

4. **VSCode komplett neustarten** (siehe Schritt 4)

5. **Prüfe ob Intelephense installiert ist:**
   - Extensions-Ansicht: `Ctrl + Shift + X`
   - Suche nach "Intelephense"
   - Sollte als installiert angezeigt werden

**💡 Falls nichts hilft:**
- Führe `./install.sh` erneut aus
- Das Script erstellt den Workspace neu mit korrekten Pfaden

### Auto-Completion funktioniert nicht

**Problem:** Keine Vorschläge beim Tippen

**Lösung:**

1. **Prüfe ob Intelephense installiert ist:**
   - Extensions-Ansicht: `Ctrl + Shift + X`
   - Suche nach "Intelephense"
   - Falls nicht installiert: Installiere es (siehe Schritt 2)

2. **Prüfe die VSCode-Konsole für Fehler:**
   - `Help → Toggle Developer Tools`
   - Schaue nach Fehlermeldungen in der Konsole

3. **Starte Intelephense neu:**
   - Drücke `Ctrl + Shift + P` (Windows/Linux) oder `Cmd + Shift + P` (Mac)
   - Tippe: "Intelephense: Restart"
   - Drücke Enter

4. **Lösche den Cache** (siehe oben)

5. **Starte VSCode neu**

### Zu viele Fehler in der IDE

**Problem:** Die IDE zeigt viele rote Wellenlinien und Fehler

**Lösung:**

Die Workspace-Konfiguration deaktiviert bereits viele undefined-Diagnostics. Falls weiterhin zu viele Fehler angezeigt werden, kannst du zusätzliche deaktivieren:

1. Öffne `woltlab-plugin-dev.code-workspace`
2. Füge diese Einstellungen hinzu:

```json
{
  "intelephense.diagnostics.undefinedTypes": false,
  "intelephense.diagnostics.undefinedMethods": false,
  "intelephense.diagnostics.undefinedConstants": false,
  "intelephense.diagnostics.undefinedFunctions": false,
  "intelephense.diagnostics.undefinedClasses": false
}
```

3. Speichere die Datei
4. Starte VSCode neu

**💡 Warum?** WoltLab verwendet dynamische Properties, die Intelephense nicht erkennen kann. Das ist normal und kein Problem.

---

## 💡 Best Practices

**Tipps für die Arbeit mit VSCode:**

1. **Immer Workspace öffnen:** Öffne immer das Workspace-File, nicht einzelne Verzeichnisse
2. **Extensions aktuell halten:** Aktualisiere Extensions regelmäßig
3. **Cache löschen:** Bei Problemen Intelephense-Cache löschen
4. **Separate Workspaces:** Verschiedene Projekte in verschiedenen Workspaces
5. **Terminal in VSCode:** Nutze das integrierte Terminal (`Ctrl + ~` oder `Cmd + ~`)

---

## 📚 Weitere Informationen

- **[WORKSPACE-SETUP.md](WORKSPACE-SETUP.md)** - Workspace-Konfiguration im Detail
- **[INSTALLATION.md](INSTALLATION.md)** - Vollständige Installationsanleitung
- **[DEVELOPER-TOOLS.md](DEVELOPER-TOOLS.md)** - Entwickler-Werkzeuge
- [VSCode Dokumentation](https://code.visualstudio.com/docs)

**Bei Problemen:**
- Öffne ein [Issue auf GitHub](https://github.com/SunnyCueq/simple-woltlab-plugin-manager/issues)
- Kontaktiere die WoltLab Community

---

**Letzte Aktualisierung:** 2025-01-08
