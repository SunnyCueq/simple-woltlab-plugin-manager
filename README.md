# Simple WoltLab Plugin Manager

Tools zum Entwickeln, Bauen und Verwalten von WoltLab-Plugins. Optional: minimales Setup zum Herunterladen von WoltLab-Core, WoltLab-Docs, WoltLab-GitHub, WoltLab d.ts (TypeScript-Typings) und zum Angeben eines Pfads zur lokalen WoltLab-Installation.

## Voraussetzungen

- **Git** für Clone und Plugin-Build/Push
- Optional: **Node.js/npm** für TypeScript und Build

## Schnellstart

1. **Workspace öffnen:** Datei `woltlab-development.code-workspace` in VS Code oder Cursor öffnen.
2. **Tools starten:** `./tools.sh` (im Repo-Root) oder `./tools/tools.sh`. Beim ersten Start wird angeboten, das **Setup auszuführen** (WoltLab-Core, Docs, GitHub, d.ts-Typings, optional Pfad zur lokalen Installation). Danach: Menü mit Build, Git Push, TypeScript, Unpack, Hilfe, Validierung.
3. **Setup später:** Im Menü Option „Setup / Vorbereitung“ oder direkt `./tools/setup-minimal.sh`. Wenn du einen Pfad zur lokalen WoltLab-Installation angibst, werden die Workspace-Datei und die Intelephense-Pfade automatisch angepasst.

**Build-Button-Extension (optional):** Unter `tools/woltlab-build-button` liegt eine VS Code/Cursor-Extension mit Seitenleiste „WoltLab“ und Buttons für Build, Git Push, TypeScript, Unpack, Hilfe, Validierung und Tools-Menü. Als „Development“-Extension aus diesem Ordner laden.

**TypeScript:** Für WoltLab-API-Typings beim Setup „d.ts klonen“ bestätigen (Standard: ja). In der Plugin-`temp_edit/tsconfig.json` z. B. `"typeRoots": ["../../woltlab-d-ts"]` setzen – Details in [tools/README.md](tools/README.md).

**Cursor/MCP:** MCP-Konfiguration (z. B. DeepWiki) liegt pro Projekt unter `.cursor/` (z. B. `basis-plugin/.cursor/mcp.json`). Das Setup kann optional eine Vorlage nach `basis-plugin/.cursor/` kopieren.

## Wichtige Links

- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- [WoltLab Docs (GitHub)](https://github.com/WoltLab/docs.woltlab.com)
- [WoltLab WCF (GitHub)](https://github.com/WoltLab/WCF)
- [WoltLab Plugin-Store Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/)

## Ordnerstruktur

| Ordner | Zweck |
|--------|--------|
| `basis-plugin` | Haupt-Plugin / Basis-App |
| `mein-plugin` | Weitere Plugins (abhängig vom Basis-Plugin) |
| `plugins-integrieren` | Externe Plugins als Referenz |
| `woltlab-core` | Setup-Dateien (WCFSetup etc.), Ziel des Setup-Downloads |
| `woltlab-docs` | WoltLab-Dokumentation (Git-Clone, optional im Setup) |
| `woltlab-github` | WoltLab-Quellcode WCF (Git-Clone, optional im Setup) |
| `woltlab-d-ts` | WoltLab TypeScript-Typings (d.ts), Git-Clone für TypeScript-Plugin-Entwicklung |
| `tools` | Skripte, Setup, Build-Button-Extension |

Details: [docs/STRUKTUR.md](docs/STRUKTUR.md).

## Wichtige Befehle

| Aktion | Befehl |
|--------|--------|
| Menü | `./tools.sh` oder `./tools/tools.sh` |
| Setup / Vorbereitung | `./tools/setup-minimal.sh` |
| Plugin bauen | `./tools/build.sh patch` (bzw. minor/major) |
| Git Push | `./tools/gitpush.sh` |
| TypeScript | `./tools/typescript.sh` |
| Unpack | `./tools/unpack.sh` |
| Validierung | `./tools/validate-plugin.sh` |
| Hilfe | `./tools/help.sh` |

Konfiguration (z. B. Pfad zur lokalen WoltLab-Installation, GitHub): `tools/.env` (siehe `tools/.env.example`).
