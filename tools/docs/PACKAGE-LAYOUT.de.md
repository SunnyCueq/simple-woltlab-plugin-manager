# Paket-Quellenlayout (SWPM)

Kurz: In welchen Ordnern liegen die Dateien, und was landet im fertigen `.tar.gz`?

SWPM packt aus `temp_edit/` (oder dem Plugin-Root). Build, TypeScript und Validate gehören zum normalen Ablauf dazu.

## Layout-Ordner

| Ordner | Erzeugt | Bedeutung |
|--------|---------|-----------|
| `templates/` | `templates.tar` | **Standardort** — Frontend-Templates (`.tpl`) hier ablegen |
| `acptemplates/` | `acptemplates.tar` | Templates für das Admin Control Panel (ACP) |
| `files/` | `files.tar` | Inhalt von `files/` statt `lib/`, `acp/`, `style/` im Root |
| `files_wcf/` | `files_wcf.tar` | Dateien für das WCF-Verzeichnis statt `js/` + `lib/bootstrap/` im Root |
| `style/style.xml` | `style.tar` / `style.tgz` / `style.tar.gz` | Style-PIP — Archivname kommt aus `package.xml` (`pack-style-tar.sh`) |

### Style-Pakete (reiner Stil, z. B. Theme)

Quellen unter `style/`:

| Datei / Ordner | Rolle |
|----------------|--------|
| `style/style.xml` | Metadaten, Referenzen auf Variablen/Bilder/Templates |
| `style/variables.xml` | Style-Variablen (WoltLab erzeugt daraus CSS) |
| `style/variables_dark.xml` | optional Dark Mode |
| `style/images/` | wird zu `images.tar` |
| `style/templates/` | wird zu `templates.tar` |
| Preview-/Cover-Bilder | wie in `<image>` / `<coverPhoto>` benannt |

In `package.xml` z. B. `<instruction type="style">style.tgz</instruction>` — SWPM packt dann **genau diesen** Dateinamen.

**scssphp:** Brauchst du in SWPM nicht. WoltLab erzeugt CSS aus den Variablen selbst. Eigene `.scss` in `style/` werden nicht kompiliert (höchstens Warnung). App-Plugins mit fertigem CSS: `style/` + `check-style-assets.py`.

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
