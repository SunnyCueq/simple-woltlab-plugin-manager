# Changelog

## Unreleased

### Plugin-Manager

- Cursor/MCP-Integration und VS-Code-Build-Button-Extension entfernt.
- Cross-Platform: Linux, macOS, Windows (WSL2, Git Bash), `tools.cmd`, `check_swpm_requirements()`, Docs unter `tools/docs/CROSS-PLATFORM*.md`.
- Generische Docker/ACP-Hilfen: Pfade aus `package.xml` (`swpm-package-resolve.sh`), `reset-app-for-acp-install.sh`; Shr1nkr-spezifische Skripte entfernt.

## Version 1.0.34 – 2026-05-29

### Shr1nkr (de.sunnyc.wsc.shrinkr)

- **UI-Texte:** Optionen, ACP-Menü, Listen, Formulare und Statistik-Hilfen in `de.xml` / `en.xml` überarbeitet – einheitliche Begriffe (Kurz-URL, Kurz-URL-Teil, Weiterleitungsseite), weniger Entwickler-Jargon (Hash, RegEx, Cronjob in Nutzertexten).
- **Statistik:** Datenschutz-Formulierungen verständlicher (anonymisiert statt „gehasht“), Filter und Spalten konsistent benannt.
- **Tooling:** Globales Copywriting-Review (`tools/copywriting/`) mit Regel-Prüfung, optionalem LLM und `glossary_de` / `glossary_en`; Aufruf über `./tools.sh copywriting`.

### Plugin-Manager

- `./tools.sh copywriting` und `./tools.sh copywriting:apply` für Text-Reviews ohne API-Key (Cursor-Workflow).
