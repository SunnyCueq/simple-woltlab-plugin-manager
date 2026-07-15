# Mitwirken am Simple WoltLab Plugin Manager

**[English version](CONTRIBUTING.md)**

SWPM ist ein **generisches** Toolkit für beliebige WoltLab-Plugin-Projekte. Es darf keine fest verdrahteten Bezüge auf ein einzelnes Produkt-Plugin enthalten.

## Harte Regeln

1. Paket-Metadaten aus der `package.xml` des Nutzers (`tools/swpm-package-resolve.sh`) oder aus `tools/.env` lesen — keine fest eingetragenen Vendor-/Package-/App-Pfade.
2. Produktspezifische Skripte, Testdaten und Doku gehören ins **Plugin-Repository**, nicht hierher.
3. Docker-Helfer sind **optional** für die lokale Entwicklung; die Kern-Tools (`build.sh`, `validate-plugin.sh`, `tools.sh`) müssen ohne Docker laufen.

Vollständige Policy: `.cursor/rules/swpm-generic-only.mdc`.

## Dokumentation

- Alle Anleitungen auf **Deutsch und Englisch** (`*.de.md` / `*.en.md` oder `README.md` / `README.de.md`).
- Index / Handbuch: [MkDocs-Site](https://benjarogit.github.io/simple-woltlab-plugin-manager/) (Quelle `tools/docs/`)
- Neue Build-Fail-Checks: Eintrag in `tools/swpm-check-registry.txt` + Skript unter `tools/` (siehe [SECURITY-CHECKS.de.md](tools/docs/SECURITY-CHECKS.de.md)).
