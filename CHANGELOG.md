# Changelog

## Unreleased

### Plugin-Manager

- **MkDocs Material:** Handbuch unter GitHub Pages (`mkdocs.yml`, Workflow `docs.yml`); Quelle bleibt `tools/docs/` (DE/EN via i18n). README verweist auf die Site statt Wiki.

## Version 1.2.0 – 2026-07-15

### Plugin-Manager

- **Produktlinie:** `swpm-family.json` + `check-family-deps.py` / `swpm-family.sh` — Multi-Paket Build/Validate in Dep-Reihenfolge; optional Scaffold (`family:init --scaffold`). Fixture: `tools/fixtures/family-demo/`. Doku: `PRODUCT-LINE.de.md` / `.en.md`.
- **Checks:** Registry `swpm-check-registry.txt` + Runner `swpm-run-checks.sh` im Build; Validate deckt dieselben Themen (plus Store) ab.
- **Style-Pakete:** `pack-style-tar.sh` inkl. Variablen/Previews; `style.tar` oder `style.tgz` laut `package.xml`; `check-style-package.py`.
- **Template-Layout:** `templates/` kanonisch; Root-`*.tpl` warnt / `--strict` failt.
- **Optional Qualitätshooks:** TypeScript (`check-typescript.sh`), PHPStan (`run-phpstan.sh`), ruff (`lint:python`), DE-Anrede Sie/Du (`check-language-address.py`, Warnung).
- **Doku:** Tools-Übersicht (`TOOLS-OVERVIEW`), Schreibstil-Pass, Menü/CLI an aktuellen Stand; Layout-Guide `PACKAGE-LAYOUT`.
- **Build:** Absolute Plugin-Roots; `TOOLS_DIR` = SWPM-`tools/`; Add-ons ohne `<applicationdirectory>` gültig; `family:build` staged Root-Layout über `_family-stage/`.
- **Generisch:** AMD-Prefix aus `package.xml`; Sprach-Beispiele `myapp.*`.

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
