# Paket-Quellenlayout (SWPM)

Kurz: In welchen Ordnern liegen die Dateien, und was landet im fertigen `.tar.gz`?

SWPM packt aus `temp_edit/` (oder dem Plugin-Root — die **Arbeitskopie** mit `package.xml`). Build, TypeScript und Validate gehören zum normalen Ablauf dazu.

**PIP** = Package Installation Plugin: Installationsschritte in `package.xml` (Dateien, Templates, Optionen, …). Template-Details: [Template-Regeln](WOLTLAB-TEMPLATE-RULES.md). Mehrere Pakete: [Produktlinie](PRODUCT-LINE.md).

## Fertiges Paket (Release-Ablage)

Nach `./tools/build.sh` liegt das installierbare Archiv **zentral** unter dem SWPM-Workspace:

```text
releases/
├── basis-plugin/
│   └── com.vendor.myapp_v1.2.3.tar.gz
└── mein-plugin-b/
    └── com.vendor.other_v0.1.0.tar.gz
```

Der Unterordner heißt wie dein Plugin-Ordner (nicht die Paket-ID). `unpack`, `prepare-acp-install` und `gitpush` suchen dort zuerst; alte `.tar.gz` direkt im Plugin-Root werden noch als Fallback gefunden. Pro Plugin behält der Build die letzten fünf Versionen.

**Wichtig — ein Ordner = ein Produkt:** Wenn du denselben Slot (z. B. `basis-plugin/`) für verschiedene Pakete wiederverwendest, können alte PIP-Archive (`templates.tar`, …) liegen bleiben. Der Build löscht sie vor dem Packen und nimmt nur Archive aus der aktuellen `package.xml` mit.

Zusätzlich speichert der Build die zuletzt gebaute Paket-ID in `.swpm-slot-package-id`. Wechselt die ID im gleichen Ordner (Demo → anderes Plugin), **bricht der Build ab**. Einmalig erzwingen: `SWPM_ALLOW_SLOT_SWITCH=1 ./tools/build.sh …` (wischt dann Slot-Artefakte). Besser dauerhaft: **eigener Ordner pro Plugin**.

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
