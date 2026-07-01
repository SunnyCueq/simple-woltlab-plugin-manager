# Mitwirken am Simple WoltLab Plugin Manager

**[English version](CONTRIBUTING.md)**

SWPM ist ein **generisches** Toolkit für beliebige WoltLab-Plugin-Projekte. Es darf keine fest verdrahteten Referenzen auf ein einzelnes Produkt-Plugin enthalten.

## Harte Regeln

1. Paket-Metadaten aus der `package.xml` des Nutzers (`tools/swpm-package-resolve.sh`) oder `tools/.env` auflösen — niemals Vendor-/Package-/App-Pfade hardcoden.
2. Produktspezifische Skripte, Testdaten und Doku gehören ins **Plugin-Repository**, nicht hierher.
3. Docker-Helfer sind **optionale** Shortcuts für lokale Entwicklung; Kern-Tools (`build.sh`, `validate-plugin.sh`, `tools.sh`) müssen ohne Docker funktionieren.

Vollständige Policy: `.cursor/rules/swpm-generic-only.mdc`.

## Dokumentation

- Alle Anleitungen auf **Deutsch und Englisch** (`*.de.md` / `*.en.md` oder `README.md` / `README.de.md`).
- Index: [tools/docs/README.de.md](tools/docs/README.de.md)
- README-Entwürfe optional mit [readme-ai](tools/docs/README-AI.de.md) — Inhalt manuell prüfen.
