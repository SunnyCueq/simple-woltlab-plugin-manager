# WoltLab Plugin Manager – Tools

Kurzreferenz für die Skripte im tools/-Ordner. Diese Tools dienen ausschließlich der Plugin-Entwicklung (Build, Git Push, TypeScript, Unpack, Validierung, Hilfe).


## Menü

Hauptmenü: ./tools.sh (im Repo-Root) bzw. ./tools/tools.sh

  Option 1   Build                 Plugin bauen & Version erhöhen
  Option 2   Git Push              Committen, pushen & Release erstellen
  Option 3   TypeScript            TypeScript kompilieren & .min.js
  Option 4   Unpack                Plugin-Paket in temp_edit/ entpacken
  Option 5   Hilfe & Dokumentation README & Anleitungen
  Option 6   Plugin Validierung    Code-Qualität & Store-Compliance
  Option 7   Setup / Vorbereitung  WoltLab-Core, Docs, GitHub, d.ts, lokaler Pfad, optional MCP-Vorlage
  Option 0   Beenden


## Wichtige Befehle

  Menü:        ./tools/tools.sh
  Setup:       ./tools/setup-minimal.sh
  Build:       ./tools/build.sh patch   (oder minor / major)
  Git Push:    ./tools/gitpush.sh
  TypeScript:  ./tools/typescript.sh     (optional: watch für Watch-Mode)
  Unpack:      ./tools/unpack.sh        (oder mit Plugin/Paket)
  Validierung: ./tools/validate-plugin.sh  (optional mit Plugin-Pfad)
  Hilfe:       ./tools/help.sh


## TypeScript-Typings (d.ts)

Wenn beim Setup **woltlab-d-ts** geklont wurde (Standard: ja), können Plugin-Projekte die [WoltLab TypeScript-Typings](https://github.com/WoltLab/d.ts) nutzen. Im Plugin z. B. in `temp_edit/tsconfig.json`:

```json
"typeRoots": ["../../woltlab-d-ts"]
```

oder relativer Pfad vom Plugin-Root zum Workspace-Ordner `woltlab-d-ts`. So stehen WoltLab-API-Typen für TypeScript zur Verfügung.


## Dokumentation

  Shell-Skript-Struktur:  docs/SHELL-STRUCTURE.md
  Plugin-Store-Checkliste: docs/PLUGIN-STORE-CHECKLIST.md


## Sonstiges

  **Dockge:** In der Systemübersicht (common.sh) wird optional der Status eines Docker-Containers namens „dockge“ angezeigt, falls vorhanden. Für den Plugin-Manager nicht erforderlich.
