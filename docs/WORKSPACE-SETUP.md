# Workspace Setup - Simple WoltLab Plugin Manager

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

**Letzte Aktualisierung:** 2025-11-08  
**Status:** Aktuell

---

Diese Anleitung erklärt, was ein Workspace ist und wie du ihn für die WoltLab Plugin-Entwicklung verwendest. Alles wird einfach erklärt – auch wenn du noch nie mit Workspaces gearbeitet hast.

---

## 📖 Was ist ein Workspace?

**Einfach erklärt:**
Ein **Workspace** ist wie ein Ordner, der mehrere Verzeichnisse gleichzeitig öffnet. Stell dir vor, du hast mehrere Ordner auf deinem Schreibtisch – ein Workspace zeigt dir alle gleichzeitig in deiner IDE.

**Warum brauchst du das?**
- Du kannst dein Plugin bearbeiten
- Gleichzeitig hast du Zugriff auf WoltLab Core (als Referenz)
- Du kannst andere Plugins als Referenz einbinden
- Alles an einem Ort, übersichtlich organisiert

**Typische Workspace-Struktur:**

```
Workspace (woltlab-plugin-dev.code-workspace)
├── 🎯 Mein Plugin                    # Dein Plugin (bearbeitbar)
├── 🔧 WoltLab Suite Core            # WoltLab Core (Read-Only - nur zum Anschauen)
└── 📦 Hauptplugin 1                 # Referenz-Plugin (optional)
```

---

## 🚀 Workspace automatisch erstellen

**Das einfachste Setup:**

Das Install-Script erstellt den Workspace automatisch für dich:

```bash
./install.sh
```

Das Script fragt dich nach:
1. WoltLab Core Verzeichnis
2. Plugin-Verzeichnis
3. Hauptplugins (optional)

**Danach findest du eine Datei:** `woltlab-plugin-dev.code-workspace`

**💡 Tipp:** Falls du den Workspace später ändern möchtest, kannst du die Datei einfach bearbeiten (siehe unten).

---

## 📂 Wo finde ich den Workspace?

**Nach der Installation:**

Der Workspace wird meist hier erstellt:
- Im übergeordneten Verzeichnis deines Plugins
- Oder in deinem Home-Verzeichnis:
  - **Windows:** `C:\Users\DeinName\`
  - **Mac/Linux:** `~/` oder `/Users/DeinName/`

**Wie finde ich die Datei?**

**Option A: Über das Terminal**
```bash
# Suche nach der Datei
find ~ -name "woltlab-plugin-dev.code-workspace" 2>/dev/null
# Oder auf Windows (PowerShell):
Get-ChildItem -Path $HOME -Filter "woltlab-plugin-dev.code-workspace" -Recurse
```

**Option B: Über den Datei-Explorer/Finder**
- Öffne den Datei-Explorer (Windows) oder Finder (Mac)
- Suche nach "woltlab-plugin-dev.code-workspace"
- Oder navigiere zu den oben genannten Verzeichnissen

---

## 🔧 Workspace öffnen

**Wie öffne ich den Workspace?**

**Option A: Über das Terminal (einfachste Methode)**

1. Öffne ein Terminal (siehe [INSTALLATION.md](INSTALLATION.md) für Details)
2. Navigiere zum Verzeichnis wo die `.code-workspace` Datei liegt:
   ```bash
   cd ~/Documents  # Beispiel - passe den Pfad an
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
- Aktiviere die Terminal-Befehle in der IDE (siehe [INSTALLATION.md](INSTALLATION.md))
- Oder verwende Option B

**Option B: Über den Datei-Explorer/Finder**

1. Öffne den Datei-Explorer (Windows) oder Finder (Mac)
2. Navigiere zum Verzeichnis wo die `.code-workspace` Datei liegt
3. **Doppelklicke** auf die Datei `woltlab-plugin-dev.code-workspace`
4. Falls sich nichts tut:
   - **Windows:** Rechtsklick → "Öffnen mit" → Cursor oder VSCode
   - **Mac:** Rechtsklick → "Öffnen mit" → Cursor oder VSCode

**Option C: Über das IDE-Menü**

1. Öffne Cursor oder VSCode
2. Gehe zu: **File → Open Workspace from File...**
3. Wähle die Datei `woltlab-plugin-dev.code-workspace`
4. Klicke auf "Öffnen"

---

## 📝 Workspace anpassen

**Wie füge ich weitere Verzeichnisse hinzu?**

1. Öffne die Datei `woltlab-plugin-dev.code-workspace` in deiner IDE
   - Du kannst sie direkt in Cursor/VSCode öffnen
   - Oder mit einem Text-Editor bearbeiten

2. Suche nach dem `"folders"` Abschnitt

3. Füge einen neuen Eintrag hinzu:

```json
{
  "folders": [
    {
      "name": "🎯 Mein Plugin",
      "path": "/pfad/zum/plugin"
    },
    {
      "name": "🔧 WoltLab Suite Core",
      "path": "/pfad/zum/core"
    },
    {
      "name": "📦 Neues Plugin",
      "path": "/pfad/zum/neuen/plugin"
    }
  ]
}
```

4. **Speichere** die Datei (`Ctrl + S` oder `Cmd + S`)
5. Die IDE lädt das Workspace automatisch neu

**💡 Wichtig:**
- Verwende **absolute Pfade** (vollständige Pfade, nicht relative)
- Auf Windows: Verwende `/` statt `\` in den Pfaden
- `~` wird automatisch zu deinem Home-Verzeichnis erweitert

---

## ⚙️ Intelephense-Konfiguration

**Was ist Intelephense?**

Intelephense ist eine Extension, die dir Auto-Completion (Vorschläge) für PHP-Code gibt. Damit funktioniert die Auto-Completion auch für WoltLab-Klassen.

**Die Konfiguration ist bereits im Workspace enthalten:**

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

**Was bedeuten diese Einstellungen?**

- **includePaths:** Zeigt Intelephense, wo WoltLab-Klassen zu finden sind
- **phpVersion:** PHP-Version für Syntax-Checking
- **undefinedTypes/Methods:** Deaktiviert, da WoltLab dynamische Properties verwendet

**💡 Du musst nichts ändern** – die Konfiguration ist bereits optimal eingestellt!

**Falls du sie trotzdem anpassen möchtest:**

1. Öffne `woltlab-plugin-dev.code-workspace` in der IDE
2. Bearbeite die `settings` Sektion
3. Speichere die Datei
4. Die IDE lädt die Änderungen automatisch

---

## 🐛 Troubleshooting

### "Pfad existiert nicht"

**Problem:** Die IDE zeigt an, dass ein Pfad nicht existiert

**Lösung:**
1. Prüfe ob die Pfade im Workspace korrekt sind
2. Verwende absolute Pfade (vollständige Pfade)
3. Prüfe ob die Verzeichnisse wirklich existieren:
   ```bash
   ls -la "/pfad/zum/verzeichnis"  # Mac/Linux
   dir "C:\pfad\zum\verzeichnis"   # Windows
   ```
4. Korrigiere die Pfade im Workspace-File

**💡 Tipp:** Verwende `~` für dein Home-Verzeichnis (wird automatisch erweitert)

### "Intelephense findet keine Klassen"

**Problem:** Auto-Completion funktioniert nicht

**Lösung:**
1. Prüfe `includePaths` im Workspace:
   ```bash
   cat woltlab-plugin-dev.code-workspace | grep includePaths
   ```
2. Prüfe ob der Core-Pfad korrekt ist:
   ```bash
   ls -la "/pfad/zum/woltlab/core/lib"
   ```
3. Stelle sicher, dass Intelephense installiert ist (siehe [IDE-SETUP-CURSOR.md](IDE-SETUP-CURSOR.md) oder [IDE-SETUP-VSCODE.md](IDE-SETUP-VSCODE.md))
4. Löschen den Intelephense-Cache:
   ```bash
   rm -rf ~/.cache/intelephense/  # Mac/Linux
   # Oder auf Windows:
   rmdir /s C:\Users\DeinName\.cache\intelephense
   ```
5. Starte die IDE neu

### "Workspace lädt nicht"

**Problem:** Die IDE zeigt Fehler beim Öffnen des Workspaces

**Lösung:**
1. Prüfe die JSON-Syntax (keine Kommas am Ende!)
2. Stelle sicher, dass alle Pfade existieren
3. Öffne die IDE-Konsole für Fehlermeldungen:
   - **Cursor/VSCode:** `Help → Toggle Developer Tools`
4. Prüfe ob die Datei korrekt ist:
   ```bash
   cat woltlab-plugin-dev.code-workspace | python -m json.tool
   # Oder mit jq (falls installiert):
   cat woltlab-plugin-dev.code-workspace | jq .
   ```

**💡 Falls nichts hilft:**
- Führe `./install.sh` erneut aus
- Das Script erstellt den Workspace neu

---

## 💡 Best Practices

**Tipps für die Arbeit mit Workspaces:**

1. **Immer Workspace öffnen:** Öffne immer das Workspace-File, nicht einzelne Verzeichnisse
2. **Read-Only für Core:** Markiere WoltLab Core als Read-Only in der IDE (verhindert versehentliche Änderungen)
3. **Separate Workspaces:** Erstelle separate Workspaces für verschiedene Projekte
4. **Backup:** Sichere dein Workspace-File regelmäßig (falls du es angepasst hast)
5. **Version Control:** Committe Workspace-Files nicht ins Git (außer Templates)

---

## 📚 Weitere Informationen

- **[INSTALLATION.md](INSTALLATION.md)** - Vollständige Installationsanleitung
- **[IDE-SETUP-CURSOR.md](IDE-SETUP-CURSOR.md)** - Cursor IDE Setup im Detail
- **[IDE-SETUP-VSCODE.md](IDE-SETUP-VSCODE.md)** - VSCode Setup im Detail
- **[DEVELOPER-TOOLS.md](DEVELOPER-TOOLS.md)** - Entwickler-Werkzeuge

**Bei Problemen:**
- Öffne ein [Issue auf GitHub](https://github.com/SunnyCueq/simple-woltlab-plugin-manager/issues)
- Kontaktiere die WoltLab Community

---

**Letzte Aktualisierung:** 2025-11-08
