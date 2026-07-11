# wspackager-Parität (SWPM)

**[English version](WSPACKAGER-PARITY.en.md)**

SWPM übernimmt die **Pack-Logik** von [wspackager](https://github.com/wbbaddons/wspackager), ohne npm/Node zu verlangen. Zusätzlich bleiben SWPM-Validierung, TypeScript-Build und Store-Checks erhalten.

## Layout-Ordner

| Ordner | Erzeugt | Entspricht wspackager / WoltLab |
|--------|---------|----------------------------------|
| `templates/` | `templates.tar` | **Kanonisch** — Frontend-`.tpl` hier ablegen |
| `acptemplates/` | `acptemplates.tar` | Unverändert — ACP-Templates |
| `files/` | `files.tar` | Ja — Inhalt von `files/` statt `lib/`, `acp/`, `style/` |
| `files_wcf/` | `files_wcf.tar` | Ja — statt `js/` + `lib/bootstrap/` im Root |
| `style/style.xml` | `style.tar` | Ja — via `pack-style-tar.sh` |

**Frontend-Templates:** Quelle ist `templates/*.tpl`. Root-`*.tpl` bleibt als Legacy-Fallback packbar (Warnung); mit `build.sh --strict-layout` bzw. `validate-plugin.sh --strict` schlägt der Check fehl. Unpack legt `templates.tar` nach `templates/` (nicht lose ins Root). PIP-XMLs (`option.xml`, `page.xml`, …) bleiben im Paket-Root.

Wenn `files/` existiert, wird **nur** daraus gepackt (nicht zusätzlich `lib/`). Entweder klassisches Layout oder wspackager-Layout — nicht beides mischen.

## CLI-Flags

```bash
./tools/build.sh --json patch          # JSON-Report auf stdout (CI)
./tools/build.sh --strict-layout same  # Root-*.tpl → Fehler
./tools/check-pip-sources.py --json    # PIP-Check als JSON
./tools/check-pip-sources.py --strict-case  # Pfad-Groß/Kleinschreibung wie auf dem Server
./tools/check-template-layout.py [--strict] temp_edit
./tools/validate-plugin.sh --strict [plugin]
```

`--json` bei `build.sh` unterdrückt keine menschlichen Logs; der JSON-Block steht am Ende bei Erfolg (`ok: true`) oder bei `build_fail` auf stderr (`ok: false`).

## Was SWPM zusätzlich kann

- `validate-plugin.sh` (Store, XSS, Sprachen)
- Git-Push / Release
- TypeScript-Kompilierung
- Docker-Helfer für lokale ACP-Installation (optional)

wspackager bleibt sinnvoll, wenn du bereits eine npm-CI-Pipeline hast und nur packen willst.
