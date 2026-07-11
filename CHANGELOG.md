# Changelog

## Unreleased

### Plugin-Manager

- **Template-Layout:** `templates/` ist kanonischer Quellort für Frontend-`.tpl`; Root-`*.tpl` warnt (Legacy-Fallback), `--strict-layout` / `validate-plugin.sh --strict` failt. Unpack entpackt `templates.tar` nach `templates/`. Docs: `WSPACKAGER-PARITY`, README, `check-template-layout.py`.

## Version 1.1.0 – 2026-07-01

### Plugin-Manager

- **wspackager-Parität:** `files/`, `files_wcf/`, `style.tar`, `--json`-Build-Reports, strengere PIP-Checks (`--strict-case`, `--json`).
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
