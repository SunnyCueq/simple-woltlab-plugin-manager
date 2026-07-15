---
title: Start
hide:
  - toc
---

<div class="swpm-hero" markdown>

![SWPM Logo](assets/swpm-logo.jpg){ loading=eager }

<div class="swpm-hero__text" markdown>

# SWPM Dokumentation

Offizielles **Handbuch** zum Simple WoltLab Plugin Manager — Guides, Tool-Übersicht und Store-/Qualitätsregeln aus einer Quelle (`tools/docs/`), ohne Wiki-Doppelpflege.

</div>

</div>

!!! tip "Schnellstart"

    1. `./tools.sh` — Menü oder `./tools.sh help`
    2. Plugin bauen → `./tools.sh build`
    3. Prüfen → `./tools.sh validate …`
    4. Überblick → [Tools-Übersicht](TOOLS-OVERVIEW.md)

    Sprache umschalten: oben rechts (**DE / EN**).

## Themen

<div class="grid cards" markdown>

-   :material-tools:{ .lg .middle } __Tools-Übersicht__

    ---

    Alle Skripte, Checks und optionale Qualitätshooks im Überblick.

    [:octicons-arrow-right-24: Zur Übersicht](TOOLS-OVERVIEW.md)

-   :material-package-variant:{ .lg .middle } __Paket-Layout__

    ---

    Ordner, Archive und `package.xml` — so baut SWPM installierbare Pakete.

    [:octicons-arrow-right-24: Layout-Guide](PACKAGE-LAYOUT.md)

-   :material-sitemap:{ .lg .middle } __Produktlinie__

    ---

    Basis + Zusatzpakete: Abhängigkeiten, Build-Reihenfolge, Family-Checks.

    [:octicons-arrow-right-24: Produktlinie](PRODUCT-LINE.md)

-   :material-store-check:{ .lg .middle } __Plugin-Store__

    ---

    Checkliste vor dem Upload — Sicherheit, Sprachen, Lauffähigkeit.

    [:octicons-arrow-right-24: Store-Checkliste](PLUGIN-STORE-CHECKLIST.md)

-   :material-shield-check:{ .lg .middle } __Security-Checks__

    ---

    Was die Validatoren prüfen (XSS, SQL, Templates, …).

    [:octicons-arrow-right-24: Security](SECURITY-CHECKS.md)

-   :material-laptop:{ .lg .middle } __Plattformen__

    ---

    Linux, macOS, Windows (WSL2 / Git Bash) — was du brauchst.

    [:octicons-arrow-right-24: Plattformen](CROSS-PLATFORM.md)

</div>

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

!!! note "Lokal die Site prüfen"

    ```bash
    python3 -m venv .venv-docs
    . .venv-docs/bin/activate
    pip install -r requirements-docs.txt
    mkdocs serve
    ```
