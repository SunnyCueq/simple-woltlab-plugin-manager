# Changelog

## Unreleased

### Plugin-Manager

## Version 1.2.6 – 2026-07-30

### Plugin-Manager

- **Release-Fix:** `./tools.sh release` ruft immer `release-manager.sh` auf — nicht mehr die alte lokale `manager-push.sh` (CalVer `vYYYY.MM.DD`, Remote `manager`). `manager-push.sh` ist nur noch ein Wrapper.
- **Release-Ablauf:** Version optional aus CHANGELOG; `--commit` mit sicherem Staging (`.cursor/`, Plugin-One-Offs, WoltLab-Spiegel ausgeschlossen).

## Version 1.2.5 – 2026-07-30

### Plugin-Manager

- **Release-Ablauf:** `release-manager.sh` — Changelog prüfen, Tag `vX.Y.Z`, Push; CI `release.yml` erstellt GitHub-Release. Aufruf: `./tools.sh release <version>` oder Menü **M**.

## Version 1.2.4 – 2026-07-30

### Plugin-Manager

- **Leere Pack-Ordner:** `check-empty-pack-dirs.py` bricht Build/Validate ab, wenn z. B. leeres `files/acp/` mit in `files.tar` rutschen würde (`tar` packt auch leere Verzeichnisse).
- **Injection-Härtung:** `check_port_reachable` — nur Loopback-Hosts, Port nur Ziffern, bevorzugt `nc -z`; sicherer `trap` in `build.sh`.
- **Neue Checks:** `check-acp-scripts.py` (kein `getTPL()->clearTemplates` in Install-Skripten), `check-frontend-asset-wiring.py` (CSS/JS nicht hinter Default-off-Optionen); Family-Deps warnt bei Template-Kollisionen.
- **Toolkit-Tests:** `tools/tests/run-tests.sh` + CI-Workflow `tools-tests.yml`.

## Version 1.2.3 – 2026-07-29

### Plugin-Manager

- **Schutz vor vermischten Paketen:** Wenn du denselben Plugin-Ordner nacheinander für *verschiedene* Produkte nutzt (z. B. zuerst ein Demo, dann ein Store-Plugin), stoppt der Build mit einer klaren Meldung. Grund: Alte Build-Dateien könnten sonst ins falsche Archiv rutschen. Empfohlen: **ein Ordner pro Plugin**. Einmalig trotzdem wechseln: `SWPM_ALLOW_SLOT_SWITCH=1 ./tools/build.sh …` (räumt den Ordner auf).
- **Saubere Archive:** Der Build nimmt nur noch die in der `package.xml` genannten Archive mit (kein blindes Kopieren aller `*.tar`). Fremde oder Demo-Reste im Paket werden erkannt und brechen Build/Validate ab.
- **GitHub-Release für SWPM:** Push eines SemVer-Tags (`vX.Y.Z`) erzeugt das Release aus dem Changelog-Abschnitt (Workflow + `publish-manager-release.sh`).
- **Repo:** verwaister Branch `master` entfernt; Arbeitslinie ist `main`.

## Version 1.2.2 – 2026-07-29

### Plugin-Manager

- **Build:** fertige `.tar.gz` landen zentral unter `releases/<plugin-ordner>/` (nicht mehr im Plugin-Root); `unpack` / `prepare-acp-install` / `gitpush` / Family suchen dort zuerst (Legacy-Fallback im Plugin-Root).
- **Doku:** ACP-Guide (EN) — DevTools „Projekt abgleichen“ vs. Hotfix vs. Paket-Install; Verweis Community-Thread (kein offizielles CLI).
- **Checks (PR #4 + Follow-up):** FQCN-sichere Endpoint-Registrierung; `files/`-Fallback für PIP-Quellen; neu `check-language-pip-keys.py` (warn) + Validate/Doku.
- **Checks:** `check-template-double-brace.py` (fail) — `{{` in `.tpl` (JSDoc/Mustache) bricht Template-Compiler; Validate + Registry. Zusätzlich `check-language-option-placeholders.py` (fail) für unsichere Platzhalter in Option-Hilfetexten.

## Version 1.2.1 – 2026-07-15

### Plugin-Manager

- **MkDocs Material:** Handbuch unter GitHub Pages (`mkdocs.yml`, Workflow `docs.yml`); Quelle bleibt `tools/docs/` (DE/EN via i18n).
- **README:** auf Einstieg + dominantem Handbuch-Link gekürzt; Site-Titel klar als Dokumentation erkennbar.
- **gitpush.sh:** Release-Notes hängen Compare-Link (`vPREV...vNEW`) und kurze Commit-Liste an den CHANGELOG-Abschnitt an (kein Auto-Changelog).
- **MkDocs UI:** Material-Layout wie Referenzseite (Tabs, Cards, Logo/Favicon); Farben an SWPM-Logo (Teal/Orange).
- **Doku-Pass:** Laien-Klarstellung Build/Validate ≠ Store-Upload; Tools-Übersicht mit CLI/Registry; Links und Store-Tabelle repariert; Check-Icons/Admonitions farblich betont.

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
