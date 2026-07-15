---
title: Home
hide:
  - toc
---

<div class="swpm-hero" markdown>

![SWPM Logo](assets/swpm-logo.jpg){ loading=eager }

<div class="swpm-hero__text" markdown>

# SWPM Documentation

Official **handbook** for the Simple WoltLab Plugin Manager — guides, tools overview, and quality rules from one source (`tools/docs/`), without a separate Wiki.

</div>

</div>

!!! info "Important for beginners"

    **Build** and **validate** check your plugin **locally only**. They do not upload anything to the WoltLab Plugin Store — they help you meet the same automated rules *beforehand*. Real upload to woltlab.com comes later and stays manual ([Store checklist](PLUGIN-STORE-CHECKLIST.md)).

!!! tip "Quick start"

    1. `./tools.sh` — menu or `./tools.sh help`
    2. Build a plugin → `./tools.sh build`
    3. Check locally → `./tools.sh validate …`
    4. Overview → [Tools overview](TOOLS-OVERVIEW.md)

    Switch language: top right (**DE / EN**).

## Topics

<div class="grid cards" markdown>

-   :material-tools:{ .lg .middle } __Tools overview__

    ---

    All scripts, checks, CLI commands, and optional hooks.

    [:octicons-arrow-right-24: Open overview](TOOLS-OVERVIEW.md)

-   :material-package-variant:{ .lg .middle } __Package layout__

    ---

    Folders, archives, and `package.xml` — how SWPM builds packages.

    [:octicons-arrow-right-24: Layout guide](PACKAGE-LAYOUT.md)

-   :material-sitemap:{ .lg .middle } __Product line__

    ---

    Core + add-ons: dependencies and build order.

    [:octicons-arrow-right-24: Product line](PRODUCT-LINE.md)

-   :material-shield-check:{ .lg .middle } __Security checks__

    ---

    What validators check locally (XSS, SQL, templates, …).

    [:octicons-arrow-right-24: Security](SECURITY-CHECKS.md)

-   :material-store-check:{ .lg .middle } __Plugin Store__

    ---

    Before a **real** upload: stage-1 checklist + manual stage 2.

    [:octicons-arrow-right-24: Store checklist](PLUGIN-STORE-CHECKLIST.md)

-   :material-laptop:{ .lg .middle } __Platforms__

    ---

    Linux, macOS, Windows (WSL2 / Git Bash).

    [:octicons-arrow-right-24: Platforms](CROSS-PLATFORM.md)

-   :material-translate:{ .lg .middle } __Language XML__

    ---

    Categories, keys, DE/EN — without update failures.

    [:octicons-arrow-right-24: Language XML](LANGUAGE-XML.md)

-   :material-file-code:{ .lg .middle } __Template rules__

    ---

    No `|encodeHTML`, scripts with `encodeJS`, layout under `templates/`.

    [:octicons-arrow-right-24: Templates](WOLTLAB-TEMPLATE-RULES.md)

-   :material-docker:{ .lg .middle } __ACP & Docker__

    ---

    Optional local install and permissions after `docker cp`.

    [:octicons-arrow-right-24: ACP install](ACP-PACKAGE-INSTALL.md) · [Permissions](DOCKER-APP-PERMISSIONS.md)

</div>

## In the repository

| Topic | Link |
|-------|------|
| Project README | [README.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/README.md) |
| Tools reference (details) | [tools/README.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/tools/README.md) · [Tools overview](TOOLS-OVERVIEW.md) |
| Contributing | [CONTRIBUTING.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/CONTRIBUTING.md) |
| Changelog | [CHANGELOG.md](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/CHANGELOG.md) |
| When something fails | [Logging](LOGGING.md) |

```bash
./tools/swpm-run-checks.sh --mode list   # which build checks exist
```

!!! note "Preview the site locally"

    ```bash
    python3 -m venv .venv-docs
    . .venv-docs/bin/activate
    pip install -r requirements-docs.txt
    mkdocs serve
    ```
