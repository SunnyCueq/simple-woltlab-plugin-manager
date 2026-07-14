# Paket-Quellenlayout (SWPM)

**[English version](PACKAGE-LAYOUT.en.md)**

SWPM baut aus dem Quellbaum deines Plugins (`temp_edit/` bzw. Plugin-Root) ein installierbares Archiv. Validierung, TypeScript-Build und Store-Checks bleiben Teil des normalen Workflows.

## Layout-Ordner

| Ordner | Erzeugt | Bedeutung |
|--------|---------|-----------|
| `templates/` | `templates.tar` | **Kanonisch** — Frontend-Templates (`.tpl`) hier ablegen |
| `acptemplates/` | `acptemplates.tar` | Templates für das Admin Control Panel (ACP) |
| `files/` | `files.tar` | Inhalt von `files/` statt `lib/`, `acp/`, `style/` im Root |
| `files_wcf/` | `files_wcf.tar` | Dateien für das WCF-Verzeichnis statt `js/` + `lib/bootstrap/` im Root |
| `style/style.xml` | `style.tar` | Style-Paketinstallation (PIP) über `pack-style-tar.sh` |

**Frontend-Templates:** Die Quelle ist `templates/*.tpl`. Liegen `.tpl`-Dateien noch im Root, packt SWPM sie weiter (Legacy-Fallback) und warnt. Existieren **beide** Layouts gleichzeitig, bricht der Build ab — bitte Root-Dateien nach `templates/` verschieben. Mit `build.sh --strict-layout` bzw. `validate-plugin.sh --strict` sind auch reine Root-`*.tpl` ein Fehler. Beim Entpacken landet `templates.tar` in `templates/` — nicht als lose Dateien im Root. PIP-XMLs (`option.xml`, `page.xml`, …) bleiben im Paket-Root.

Wenn `files/` existiert, wird **nur** daraus gepackt (nicht zusätzlich `lib/`). Nutze entweder das klassische Layout (`lib/`, `acp/`, `style/`) oder das `files/`-Layout — nicht beides gemischt.

## CLI-Flags

```bash
./tools/build.sh --json patch          # JSON-Report auf stdout (CI)
./tools/build.sh --strict-layout same  # Root-*.tpl → Fehler
./tools/check-pip-sources.py --json    # PIP-Check als JSON
./tools/check-pip-sources.py --strict-case  # Pfad-Groß/Kleinschreibung wie auf dem Server
./tools/check-template-layout.py [--strict] temp_edit
./tools/validate-plugin.sh --strict [plugin]
```

`--json` bei `build.sh` unterdrückt die normalen Logzeilen nicht; der JSON-Block erscheint am Ende bei Erfolg (`ok: true`) oder bei `build_fail` auf stderr (`ok: false`).

## Weitere SWPM-Funktionen

- `validate-plugin.sh` (Store, XSS, Sprachen)
- Git-Push / Release
- TypeScript-Kompilierung
- Docker-Helfer für die lokale ACP-Installation (optional)
