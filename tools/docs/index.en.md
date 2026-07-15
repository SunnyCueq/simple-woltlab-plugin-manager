---
title: Home
hide:
  - toc
---

<div class="swpm-hero" markdown>

![SWPM Logo](assets/swpm-logo.jpg){ loading=eager }

<div class="swpm-hero__text" markdown>

# SWPM Documentation

Official **handbook** for the Simple WoltLab Plugin Manager — guides, tools overview, and store/quality rules from one source (`tools/docs/`), without a separate Wiki.

</div>

</div>

!!! tip "Quick start"

    1. `./tools.sh` — menu or `./tools.sh help`
    2. Build a plugin → `./tools.sh build`
    3. Validate → `./tools.sh validate …`
    4. Overview → [Tools overview](TOOLS-OVERVIEW.md)

    Switch language: top right (**DE / EN**).

## Topics

<div class="grid cards" markdown>

-   :material-tools:{ .lg .middle } __Tools overview__

    ---

    All scripts, checks, and optional quality hooks at a glance.

    [:octicons-arrow-right-24: Open overview](TOOLS-OVERVIEW.md)

-   :material-package-variant:{ .lg .middle } __Package layout__

    ---

    Folders, archives, and `package.xml` — how SWPM builds installable packages.

    [:octicons-arrow-right-24: Layout guide](PACKAGE-LAYOUT.md)

-   :material-sitemap:{ .lg .middle } __Product line__

    ---

    Core + add-ons: dependencies, build order, family checks.

    [:octicons-arrow-right-24: Product line](PRODUCT-LINE.md)

-   :material-store-check:{ .lg .middle } __Plugin Store__

    ---

    Checklist before upload — security, languages, readiness.

    [:octicons-arrow-right-24: Store checklist](PLUGIN-STORE-CHECKLIST.md)

-   :material-shield-check:{ .lg .middle } __Security checks__

    ---

    What the validators look for (XSS, SQL, templates, …).

    [:octicons-arrow-right-24: Security](SECURITY-CHECKS.md)

-   :material-laptop:{ .lg .middle } __Platforms__

    ---

    Linux, macOS, Windows (WSL2 / Git Bash) — what you need.

    [:octicons-arrow-right-24: Platforms](CROSS-PLATFORM.md)

</div>

## In the repository

| Topic | Link |
|-------|------|
| Project README | [README.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/README.md) |
| Tools reference (details) | [tools/README.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/tools/README.md) |
| Contributing | [CONTRIBUTING.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/CONTRIBUTING.md) |
| Changelog | [CHANGELOG.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/CHANGELOG.md) |

```bash
./tools/swpm-run-checks.sh --mode list
```

!!! note "Preview the site locally"

    ```bash
    python3 -m venv .venv-docs
    . .venv-docs/bin/activate
    pip install -r requirements-docs.txt
    mkdocs serve
    ```
