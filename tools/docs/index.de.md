---
title: Start
hide:
  - toc
---

<div class="swpm-hero" markdown>

![SWPM Logo](assets/swpm-logo.jpg){ loading=eager }

<div class="swpm-hero__text" markdown>

# SWPM Dokumentation

Offizielles **Handbuch** zum Simple WoltLab Plugin Manager — Guides, Tool-Übersicht und Qualitätsregeln aus einer Quelle (`tools/docs/`), ohne Wiki-Doppelpflege.

</div>

</div>

!!! info "Wichtig für Einsteiger"

    **Build** und **Validate** prüfen dein Plugin **nur lokal**. Sie laden nichts in den WoltLab Plugin-Store hoch — sie helfen dir, dieselben automatischen Regeln *vorher* zu erfüllen. Der echte Upload auf woltlab.com kommt später und bleibt manuell ([Store-Checkliste](PLUGIN-STORE-CHECKLIST.md)).

!!! tip "Schnellstart"

    1. `./tools.sh` — Menü oder `./tools.sh help`
    2. Plugin bauen → `./tools.sh build`
    3. Lokal prüfen → `./tools.sh validate …`
    4. Überblick → [Tools-Übersicht](TOOLS-OVERVIEW.md)

    Sprache umschalten: oben rechts (**DE / EN**).

## Themen

<div class="grid cards" markdown>

-   :material-tools:{ .lg .middle } __Tools-Übersicht__

    ---

    Alle Skripte, Checks, CLI-Befehle und optionale Hooks.

    [:octicons-arrow-right-24: Zur Übersicht](TOOLS-OVERVIEW.md)

-   :material-package-variant:{ .lg .middle } __Paket-Layout__

    ---

    Ordner, Archive und `package.xml` — so baut SWPM Pakete.

    [:octicons-arrow-right-24: Layout-Guide](PACKAGE-LAYOUT.md)

-   :material-sitemap:{ .lg .middle } __Produktlinie__

    ---

    Basis + Zusatzpakete: Abhängigkeiten und Build-Reihenfolge.

    [:octicons-arrow-right-24: Produktlinie](PRODUCT-LINE.md)

-   :material-shield-check:{ .lg .middle } __Security-Checks__

    ---

    Was die Validatoren lokal prüfen (XSS, SQL, Templates, …).

    [:octicons-arrow-right-24: Security](SECURITY-CHECKS.md)

-   :material-store-check:{ .lg .middle } __Plugin-Store__

    ---

    Vor dem **echten** Upload: Checkliste Stufe 1 + manuelle Stufe 2.

    [:octicons-arrow-right-24: Store-Checkliste](PLUGIN-STORE-CHECKLIST.md)

-   :material-laptop:{ .lg .middle } __Plattformen__

    ---

    Linux, macOS, Windows (WSL2 / Git Bash).

    [:octicons-arrow-right-24: Plattformen](CROSS-PLATFORM.md)

-   :material-translate:{ .lg .middle } __Language-XML__

    ---

    Kategorien, Keys, DE/EN — ohne Update-Abbruch.

    [:octicons-arrow-right-24: Language-XML](LANGUAGE-XML.md)

-   :material-file-code:{ .lg .middle } __Template-Regeln__

    ---

    Kein `|encodeHTML`, Scripts mit `encodeJS`, Layout `templates/`.

    [:octicons-arrow-right-24: Templates](WOLTLAB-TEMPLATE-RULES.md)

-   :material-docker:{ .lg .middle } __ACP & Docker__

    ---

    Optionale lokale Installation und Rechte nach `docker cp`.

    [:octicons-arrow-right-24: ACP-Install](ACP-PACKAGE-INSTALL.md) · [Rechte](DOCKER-APP-PERMISSIONS.md)

</div>

## Im Repository

| Thema | Link |
|-------|------|
| Projekt-README | [README.de.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/README.de.md) |
| Tools-Referenz (Details) | [tools/README.de.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/tools/README.de.md) · [Tools-Übersicht](TOOLS-OVERVIEW.md) |
| Mitwirken | [CONTRIBUTING.de.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/CONTRIBUTING.de.md) |
| Changelog | [CHANGELOG.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/CHANGELOG.md) |
| Bei Fehlern | [Logging](LOGGING.md) |

```bash
./tools/swpm-run-checks.sh --mode list   # welche Build-Checks es gibt
```

!!! note "Lokal die Site prüfen"

    ```bash
    python3 -m venv .venv-docs
    . .venv-docs/bin/activate
    pip install -r requirements-docs.txt
    mkdocs serve
    ```
