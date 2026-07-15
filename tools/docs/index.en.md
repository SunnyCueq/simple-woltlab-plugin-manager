# SWPM Documentation

Official **handbook** for the Simple WoltLab Plugin Manager. Guides, tools overview, and store/quality rules live here — one source in the repo (`tools/docs/`), no duplicate maintenance and no separate Wiki.

## Quick start

1. `./tools.sh` — menu or `./tools.sh help`
2. Build a plugin → `./tools.sh build`
3. Validate → `./tools.sh validate …`
4. Which scripts exist → [Tools overview](TOOLS-OVERVIEW.md)

Switch language: top right (DE / EN).

## Topics

Use the **navigation on the left**. Key entry points:

- [Tools overview](TOOLS-OVERVIEW.md) — all scripts and checks
- [Package layout](PACKAGE-LAYOUT.md) — folders and archives
- [Product line](PRODUCT-LINE.md) — core + add-ons
- [Plugin Store checklist](PLUGIN-STORE-CHECKLIST.md) — before upload

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

Preview the site locally:

```bash
python3 -m venv .venv-docs
. .venv-docs/bin/activate
pip install -r requirements-docs.txt
mkdocs serve
```
