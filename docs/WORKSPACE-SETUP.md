# Workspace Setup - Simple WoltLab Plugin Manager

**Letzte Aktualisierung:** 2025-01-08  
**Status:** Aktuell

**Letzte Änderung:** Initiale Version
- Grund: Erstellung der Workspace-Setup-Anleitung

---

Diese Anleitung erklärt das Multi-Root Workspace-Konzept und wie Sie es für die WoltLab Plugin-Entwicklung konfigurieren.

## Was ist ein Multi-Root Workspace?

Ein Multi-Root Workspace erlaubt es, mehrere Verzeichnisse gleichzeitig in einer IDE zu öffnen. Dies ist ideal für die WoltLab Plugin-Entwicklung, da Sie:

- Ihr Plugin-Verzeichnis bearbeiten können
- Zugriff auf WoltLab Core für Referenz haben
- Hauptplugins als Referenz einbinden können

## Verzeichnisstruktur

Typische Workspace-Struktur:

```
Workspace (woltlab-plugin-dev.code-workspace)
├── 🎯 Mein Plugin                    # Ihr Plugin (bearbeitbar)
├── 🔧 WoltLab Suite Core            # WoltLab Core (Read-Only)
└── 📦 Hauptplugin 1                 # Referenz-Plugin (optional)
```

## Automatische Erstellung

Das einfachste Setup erfolgt über das Install-Script:

```bash
./install.sh
```

Das Script fragt nach allen benötigten Pfaden und erstellt automatisch das Workspace-File.

## Manuelle Erstellung

Falls Sie das Workspace manuell erstellen möchten:

```bash
./scripts/setup-workspace.sh
```

Das Script führt Sie interaktiv durch die Erstellung.

## Workspace-File Struktur

Das Workspace-File ist eine JSON-Datei mit folgender Struktur:

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
    }
  ],
  "settings": {
    "intelephense.environment.includePaths": [
      "/pfad/zum/core/lib"
    ]
  }
}
```

## Intelephense-Konfiguration

Die Intelephense-Konfiguration ist entscheidend für Auto-Completion:

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

### Warum diese Einstellungen?

- **includePaths:** Zeigt Intelephense, wo WoltLab-Klassen zu finden sind
- **phpVersion:** PHP-Version für Syntax-Checking
- **undefinedTypes/Methods:** Deaktiviert, da WoltLab dynamische Properties verwendet

## Workspace öffnen

### Cursor

```bash
cursor woltlab-plugin-dev.code-workspace
```

Oder über das Menü:
1. `File → Open Workspace from File...`
2. Wählen Sie `woltlab-plugin-dev.code-workspace`

### VSCode

```bash
code woltlab-plugin-dev.code-workspace
```

Oder über das Menü:
1. `File → Open Workspace from File...`
2. Wählen Sie `woltlab-plugin-dev.code-workspace`

## Verzeichnisse hinzufügen

Um weitere Verzeichnisse hinzuzufügen:

1. Öffnen Sie das Workspace-File in der IDE
2. Fügen Sie einen neuen Eintrag zu `folders` hinzu:

```json
{
  "name": "📦 Neues Plugin",
  "path": "/pfad/zum/neuen/plugin"
}
```

3. Speichern Sie das File
4. Die IDE lädt das Workspace automatisch neu

## Troubleshooting

### "Pfad existiert nicht"

Prüfen Sie, ob die Pfade im Workspace korrekt sind:
- Absolute Pfade verwenden (nicht relative)
- `~` wird zu `$HOME` erweitert
- Windows: Verwenden Sie `/` statt `\` in Pfaden

### "Intelephense findet keine Klassen"

1. Prüfen Sie `includePaths` im Workspace
2. Stellen Sie sicher, dass der Core-Pfad korrekt ist
3. Starten Sie die IDE neu
4. Löschen Sie den Cache: `rm -rf ~/.cache/intelephense/`

### "Workspace lädt nicht"

1. Prüfen Sie die JSON-Syntax (keine Kommas am Ende)
2. Stellen Sie sicher, dass alle Pfade existieren
3. Öffnen Sie die IDE-Konsole für Fehlermeldungen

## Best Practices

1. **Read-Only für Core:** Markieren Sie WoltLab Core als Read-Only in der IDE
2. **Separate Workspaces:** Erstellen Sie separate Workspaces für verschiedene Projekte
3. **Backup:** Sichern Sie Ihr Workspace-File regelmäßig
4. **Version Control:** Committen Sie Workspace-Files nicht (außer Templates)

## Weitere Informationen

- **[IDE-SETUP-CURSOR.md](IDE-SETUP-CURSOR.md)** - Cursor-spezifische Anleitung
- **[IDE-SETUP-VSCODE.md](IDE-SETUP-VSCODE.md)** - VSCode-spezifische Anleitung

