# IDE Setup - Cursor

**Letzte Aktualisierung:** 2025-01-08  
**Status:** Aktuell

**Letzte Änderung:** Initiale Version
- Grund: Erstellung der Cursor IDE Setup-Anleitung

---

Diese Anleitung erklärt, wie Sie Cursor IDE für die WoltLab Plugin-Entwicklung einrichten.

## Voraussetzungen

- Cursor IDE installiert
- Workspace erstellt (siehe [WORKSPACE-SETUP.md](WORKSPACE-SETUP.md))
- WoltLab Core verfügbar

## Schritt 1: Workspace öffnen

Öffnen Sie das Workspace-File in Cursor:

```bash
cursor woltlab-plugin-dev.code-workspace
```

Oder über das Menü:
1. `File → Open Workspace from File...`
2. Wählen Sie `woltlab-plugin-dev.code-workspace`

## Schritt 2: Extensions installieren

Cursor schlägt automatisch die empfohlenen Extensions vor. Installieren Sie:

### Intelephense (Pflicht)

- **Name:** Intelephense
- **ID:** `bmewburn.vscode-intelephense-client`
- **Zweck:** PHP Auto-Completion für WoltLab-Klassen

Installation:
1. `Ctrl+Shift+X` (Extensions öffnen)
2. Suche: "Intelephense"
3. Installieren

### Xdebug (Optional)

- **Name:** PHP Debug
- **ID:** `xdebug.php-debug`
- **Zweck:** PHP Debugging

### EditorConfig (Empfohlen)

- **Name:** EditorConfig for VS Code
- **ID:** `EditorConfig.EditorConfig`
- **Zweck:** Einheitliche Code-Formatierung

## Schritt 3: Intelephense konfigurieren

Die Intelephense-Konfiguration ist bereits im Workspace enthalten:

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

### Manuelle Anpassung

Falls Sie die Konfiguration anpassen möchten:

1. Öffnen Sie `woltlab-plugin-dev.code-workspace`
2. Bearbeiten Sie die `settings` Sektion
3. Speichern Sie das File
4. Cursor lädt die Änderungen automatisch

## Schritt 4: IDE neustarten

Nach der Installation der Extensions:

1. Schließen Sie Cursor komplett
2. Öffnen Sie Cursor erneut
3. Öffnen Sie das Workspace

## Schritt 5: Auto-Completion testen

Erstellen Sie eine Test-Datei:

```php
<?php
use wcf\system\WCF;
use wcf\data\DatabaseObject;

// Auto-Completion sollte hier funktionieren:
$db = WCF::getDB();  // <- Cursor + Space sollte Methoden zeigen
```

### Erwartetes Verhalten

- Keine roten Wellenlinien bei `use wcf\...` Statements
- Auto-Completion bei `WCF::` zeigt Methoden
- Auto-Completion bei `FormContainer::create()` zeigt Parameter
- Keine "Class not found" Fehler

## Troubleshooting

### "Class not found" Fehler bleiben

1. **Prüfe includePaths:**
   ```bash
   cat woltlab-plugin-dev.code-workspace | grep includePaths
   ```

2. **Prüfe ob Core-Pfad korrekt ist:**
   ```bash
   ls -la "/pfad/zum/woltlab/core/lib"
   ```

3. **Intelephense-Cache löschen:**
   ```bash
   rm -rf ~/.cache/intelephense/
   ```

4. **Cursor komplett neustarten**

### Auto-Completion funktioniert nicht

1. Prüfen Sie, ob Intelephense installiert ist: `Ctrl+Shift+X` → "Intelephense"
2. Prüfen Sie die Cursor-Konsole für Fehler: `Help → Toggle Developer Tools`
3. Starten Sie Intelephense neu: `Ctrl+Shift+P` → "Intelephense: Restart"

### Zu viele Fehler in der IDE

Die Workspace-Konfiguration deaktiviert bereits viele undefined-Diagnostics. Falls weiterhin zu viele Fehler angezeigt werden:

```json
{
  "intelephense.diagnostics.undefinedTypes": false,
  "intelephense.diagnostics.undefinedMethods": false,
  "intelephense.diagnostics.undefinedConstants": false,
  "intelephense.diagnostics.undefinedFunctions": false,
  "intelephense.diagnostics.undefinedClasses": false
}
```

## Best Practices

1. **Workspace verwenden:** Immer das Workspace-File öffnen, nicht einzelne Verzeichnisse
2. **Extensions aktuell halten:** Regelmäßig Extensions aktualisieren
3. **Cache löschen:** Bei Problemen Intelephense-Cache löschen
4. **Separate Workspaces:** Verschiedene Projekte in verschiedenen Workspaces

## Weitere Informationen

- **[WORKSPACE-SETUP.md](WORKSPACE-SETUP.md)** - Workspace-Konfiguration
- **[INSTALLATION.md](INSTALLATION.md)** - Installation
- [Cursor Dokumentation](https://cursor.sh/docs)

