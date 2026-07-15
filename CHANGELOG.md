# Changelog

## Unreleased

### Plugin-Manager

- **Optional Qualitätshooks / Language:** u. a. `check-language-address.py` (Warnung bei gemischter DE-Anrede Sie/Du).
- **Doku:** Menü/CLI und Guides an aktuellen Stand angeglichen; Schreibstil-Pass; **Tools-Übersicht** DE/EN (`TOOLS-OVERVIEW`) mit Alltagsweg und kompletter Skript-Liste.
- **Optional Qualitätshooks:** `check-typescript.sh` (Build/Validate bei `tsconfig`/`.ts`); `run-phpstan.sh` (nur mit `phpstan.neon(.dist)` im Plugin); `./tools.sh lint:python` (ruff für Manager-`tools/*.py`, Skip ohne Binary).
- **Style-Pakete:** `pack-style-tar.sh` packt Variablen, Previews, `images/`/`templates/` und schreibt `style.tar` oder `style.tgz` laut `package.xml`; `check-style-package.py` in der Registry. scssphp bewusst nicht eingebunden (Suite kompiliert aus Variablen). Doku: `PACKAGE-LAYOUT`.
- **Produktlinie:** `swpm-family.json` + `check-family-deps.py` / `swpm-family.sh` — Multi-Paket Build/Validate in Dep-Reihenfolge (genau eine Graph-Komponente); optional Scaffold (`family:init --scaffold`). Discovery überspringt u. a. `examples/`/`fixtures/`; leere Basis-`<version>` bei `minversion` = Fehler; `add-addon` leitet Basis-ID aus Topo-Root ab (`--json`, nur bei gültigem Graph). Scaffold legt `lib/.gitkeep` an; Workspace-Discovery über `--scan-workspace`; `SWPM_FAMILY_RUN=1` ignoriert `.env`-Package-IDs. Fixture: `tools/fixtures/family-demo/`.
- **Doku Produktlinie:** `PRODUCT-LINE.de.md` / `.en.md` — Ordnerlayouts (Geschwister vs. SWPM-Root), Scaffold vs. echte `package.xml`, Schritt-für-Schritt, Manifest-Felder, Checkliste, Anti-Patterns, Glossar (laienverständlich, technisch präzise).
- **Build:** Plugin-/Add-on-Pakete ohne `<applicationdirectory>` sind gültig (nur Apps brauchen das Feld). `family:build` staged Root-Layout-Pakete (`package.xml` im Root) automatisch über `_family-stage/` und kopiert Archive nach `releases/`.
- **Build:** Absolute Plugin-Roots; `TOOLS_DIR` immer SWPM-`tools/` (externe Pakete).
- **Checks:** Registry `swpm-check-registry.txt` + Runner `swpm-run-checks.sh` — der **Build** führt die Fail-Checks darüber aus. Validate deckt dieselben Themen (plus Store) ab, aber nicht 1:1 über denselben Runner. PIP-Quellen bleiben separat in `build.sh`.
- **Template-Layout:** `templates/` ist kanonischer Quellort für Frontend-`.tpl`; Root-`*.tpl` warnt (Legacy-Fallback), `--strict-layout` / `validate-plugin.sh --strict` failt. **Beide Layouts gleichzeitig** → Build-Fehler. Unpack entpackt `templates.tar` nach `templates/`. Docs: `PACKAGE-LAYOUT`, README, `check-template-layout.py`.
- **Doku:** Fremdtool-Vergleiche entfernt; Layout-Guide heißt `PACKAGE-LAYOUT`.
- **Generisch:** AMD-Export-Check ohne festes App-Präfix (`check-js-amd-exports.py` + Prefix aus `package.xml`); Sprach-XML-Beispiele nur noch `myapp.*`.
- **Schreibstil:** Rule `schreibstil-docs.mdc` für Doku/Nutzertexte.

## Version 1.1.0 – 2026-07-01

### Plugin-Manager

- **Paket-Layouts:** `files/`, `files_wcf/`, `style.tar`, `--json`-Build-Reports, strengere PIP-Checks (`--strict-case`, `--json`).
- **Dokumentation:** Alle Guides zweisprachig (DE/EN), Index unter `tools/docs/README.md`, überarbeitete Root-READMEs mit Badges und Inhaltsverzeichnis.
- **tools.sh:** Menü-Optik (`ui.sh`), Plugin-Erkennung (`temp_edit/package.xml`), Bugfix `tr()` vs. System-`tr` (Versionsanzeige), keine Phantom-Plugins mehr.
- **Generisch:** Keine Shr1nkr-Hardcodings; Pfade aus `package.xml` / `tools/.env` (`swpm-package-resolve.sh`).
- **Cross-Platform:** Linux, macOS, WSL2, Git Bash, `tools.cmd`, `check_swpm_requirements()`.
- **Validierung:** Offline DevTools-Parität (PIP-Quellen, Sprach-Keys mit Datei:Zeile).
- **Docker (optional):** ACP-Install-Helfer, Berechtigungs-Fixes, `reset-app-for-acp-install.sh`.
- Cursor/MCP-Integration und VS-Code-Build-Button entfernt.

## Version 1.0.34 – 2026-05-29

### Shr1nkr (de.sunnyc.wsc.shrinkr)

- **UI-Texte:** Optionen, ACP-Menü, Listen, Formulare und Statistik-Hilfen in `de.xml` / `en.xml` überarbeitet – einheitliche Begriffe (Kurz-URL, Kurz-URL-Teil, Weiterleitungsseite), weniger Entwickler-Jargon (Hash, RegEx, Cronjob in Nutzertexten).
- **Statistik:** Datenschutz-Formulierungen verständlicher (anonymisiert statt „gehasht“), Filter und Spalten konsistent benannt.
- **Tooling:** Globales Copywriting-Review (`tools/copywriting/`) mit Regel-Prüfung, optionalem LLM und `glossary_de` / `glossary_en`; Aufruf über `./tools.sh copywriting`.

### Plugin-Manager

- `./tools.sh copywriting` und `./tools.sh copywriting:apply` für Text-Reviews ohne API-Key (Cursor-Workflow).
