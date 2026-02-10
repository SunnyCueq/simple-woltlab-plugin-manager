# Ordnerstruktur (detailliert)

Dieses Dokument beschreibt die wichtigsten Ordner im Workspace des Simple WoltLab Plugin Manager. Eine kurze Übersicht steht im [Haupt-README](../README.de.md).

---

## Plugin-Ordner

| Ordner | Zweck |
|--------|--------|
| **basis-plugin** | Dein Haupt- oder Basis-Plugin mit der zentralen Funktionalität. Enthält typischerweise `temp_edit/` (Quelldateien), `package.xml` und das gebaute `.tar.gz`-Paket. |
| **mein-plugin** | Weitere Plugin-Projekte, z. B. abhängig vom Basis-Plugin oder eigene Produkte. Aufbau wie basis-plugin (temp_edit, package.xml, etc.). |
| **plugins-integrieren** | Referenz- oder Fremd-Plugins im Workspace zum Vergleichen oder Integrieren. Nicht zwingend von dir gebaut. |

---

## WoltLab-Ressourcen (Setup und Referenz)

| Ordner | Zweck |
|--------|--------|
| **woltlab-core** | Zielordner für das WoltLab-Suite-Installationspaket (z. B. WCFSetup) beim Core-Download. Das Setup legt die Dateien hier ab. |
| **woltlab-docs** | Optionaler Git-Klon der [offiziellen WoltLab-Dokumentation](https://github.com/WoltLab/docs.woltlab.com). Wird beim Setup erstellt, wenn du Docs klonen wählst. |
| **woltlab-github** | Optionaler Git-Klon des [WoltLab-WCF](https://github.com/WoltLab/WCF)-Quellcodes. Wird beim Setup erstellt. Zum Nachschlagen von Core-Code und APIs. |
| **woltlab-d-ts** | Git-Klon der [offiziellen WoltLab-TypeScript-Typings (d.ts)](https://github.com/WoltLab/d.ts). Wird beim Setup erstellt, wenn du „d.ts klonen“ bestätigst. Dein Plugin-TypeScript kann auf diesen Ordner verweisen. `git pull` dort hält die Typings aktuell. |

---

## Tools

| Ordner | Zweck |
|--------|--------|
| **tools** | Alle Skripte und die Build-Button-Extension. Enthält `tools.sh` (Hauptmenü), `build.sh`, `gitpush.sh`, `typescript.sh`, `unpack.sh`, `validate-plugin.sh`, `setup-minimal.sh`, `help.sh`, `download-woltlab-core.sh` und `woltlab-build-button`. Konfiguration in `tools/.env` (siehe `tools/.env.example`). Doku: `tools/README.md` / `tools/README.de.md` und `tools/docs/` (z. B. PLUGIN-STORE-CHECKLIST). |

---

## Root-Dateien

- **tools.sh** — Kurzbefehl zum Starten des Hauptmenüs (wie `./tools/tools.sh`).
- **woltlab-development.code-workspace** — VS Code-/Cursor-Workspace-Datei. Öffnen, um alle relevanten Ordner zu laden.
- **README.md** / **README.de.md** — Hauptdokumentation auf Englisch und Deutsch.
