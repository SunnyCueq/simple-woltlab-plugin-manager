# SWPM-Dokumentation

Willkommen beim **Simple WoltLab Plugin Manager**. Diese Site ist die Handbuch-Ansicht der Guides aus dem Repo (`tools/docs/`) — eine Quelle, keine Doppelpflege.

## Schnellstart

1. `./tools.sh` — Menü oder `./tools.sh help`
2. Plugin bauen → `./tools.sh build`
3. Prüfen → `./tools.sh validate …`
4. Welche Skripte es gibt → [Tools-Übersicht](TOOLS-OVERVIEW.md)

Sprache umschalten: oben rechts (DE / EN).

## Themen

Nutze die **Navigation links**. Wichtige Einstiege:

- [Tools-Übersicht](TOOLS-OVERVIEW.md) — alle Skripte und Checks
- [Paket-Layout](PACKAGE-LAYOUT.md) — Ordner und Archive
- [Produktlinie](PRODUCT-LINE.md) — Basis + Add-ons
- [Plugin-Store-Checkliste](PLUGIN-STORE-CHECKLIST.md) — vor dem Upload

## Im Repository

| Thema | Link |
|-------|------|
| Projekt-README | [README.de.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/README.de.md) |
| Tools-Referenz (Details) | [tools/README.de.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/tools/README.de.md) |
| Mitwirken | [CONTRIBUTING.de.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/CONTRIBUTING.de.md) |
| Changelog | [CHANGELOG.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/CHANGELOG.md) |

```bash
./tools/swpm-run-checks.sh --mode list
```

Lokal die Site prüfen:

```bash
python3 -m venv .venv-docs
. .venv-docs/bin/activate
pip install -r requirements-docs.txt
mkdocs serve
```
