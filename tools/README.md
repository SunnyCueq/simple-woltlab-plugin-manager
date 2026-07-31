# Tools – WoltLab Plugin Manager

**[Deutsche Version](README.de.md)** · **[Handbook / Documentation](https://benjarogit.github.io/simple-woltlab-plugin-manager/en/)**

---

## Overview

The `tools/` folder holds the day-to-day scripts: build a plugin, validate it, compile TypeScript, unpack packages, run setup, and push to Git. Most people start with `./tools.sh` (menu or CLI). Goal: from development to **local quality checks** and an optional store upload (manual on woltlab.com) — without requiring Docker.

**Full list:** [docs/TOOLS-OVERVIEW.en.md](docs/TOOLS-OVERVIEW.en.md) — core, build checks, optional, Docker, internal.

**Platforms:** Linux, macOS, Windows (WSL2 or Git Bash). From cmd/Explorer on Windows: `tools.cmd`. Details: [docs/CROSS-PLATFORM.en.md](docs/CROSS-PLATFORM.en.md).

**Note:** `tools/woltlab-plugin-recovery/` is a separate recovery helper — not part of the normal build/validate menu.

---

## Main menu (tools.sh)

```bash
./tools.sh          # interactive menu
./tools.sh help     # all CLI commands
```

| Key | Name | Short |
|-----|------|-------|
| 1 | Build / update package | `patch` · `minor` · `major` · `same` |
| 2 | TypeScript | compile / watch |
| 3 | Unpack | package → `temp_edit/` |
| F | Product line | core + add-ons (`family:*`) |
| 4 | Validate plugin | local quality checks |
| 5 | Help / docs | this documentation |
| 6 | Git Push | commit, push, release (plugin) |
| 7 | Setup | Core, docs, typings, paths |
| 8 | Repo (origin) | show/set remote |
| 9 | WoltLab refs | check · sync on update |
| L | Language DE/EN | menu language |
| M | SWPM Release | `release-manager.sh` |
| 0 | Exit | |

---

## Each tool in detail

### build.sh – Build plugins

**What it does:** Finds your plugin (folder with `package.xml`), compiles TypeScript when needed, and builds an installable `.tar.gz` under `releases/<plugin-folder>/`. It can also bump the version in `package.xml`.

**Important:** One plugin = one folder. If you reuse the same folder for a *different* plugin, the build stops (protection against mixed files). One-shot switch: `SWPM_ALLOW_SLOT_SWITCH=1 ./tools/build.sh …`. Details: [docs/PACKAGE-LAYOUT.en.md](docs/PACKAGE-LAYOUT.en.md).

**When:** After code changes, when you need a package to test or ship.

**Command:**

```bash
./tools/build.sh [target] [version_type]
```

- `target`: empty = first plugin, folder name (e.g. `basis-plugin`), or `all`
- `version_type`: `patch` (default), `minor`, `major`, or `same` (keep version)

**Examples:**

```bash
./tools/build.sh              # First plugin, patch
./tools/build.sh patch
./tools/build.sh basis-plugin minor
./tools/build.sh all same
./tools/build.sh --json patch        # CI: JSON report
./tools/build.sh --dry-run patch     # Show planned contents only
```

**Layouts:** Classic (`lib/`, `js/`, …), `files/` / `files_wcf/`, or style package (`style/style.xml` → `style.tar`/`style.tgz`). See [docs/PACKAGE-LAYOUT.en.md](docs/PACKAGE-LAYOUT.en.md).

---

### gitpush.sh – Commit, push, and release (plugins)

**What it does:** Finds changed plugins, commits, pushes to `origin`, creates a version tag, and can open a GitHub release. Release notes: section from `CHANGELOG.md`, plus a Compare link to the previous `v*` tag and a short commit list (not an auto-changelog). For **plugin** releases — not the Plugin Manager repo itself.

**When:** When plugin changes are ready to go to GitHub (commit + push + tag + release).

**Command:**

```bash
./tools/gitpush.sh [target] [commit_message]
```

- `target`: empty = auto-detect, plugin name, or `all`
- `commit_message`: optional; otherwise derived from the plugin version

**Requirements:** `origin` points at your plugin or workspace repo (SSH or Personal Access Token). Or set `GIT_REPO_URL` in `tools/.env` / menu option 8.

> **Tip:** Run from the repo root. Known reference folders (e.g. `woltlab-docs`, `woltlab-github`) are not committed.

---

### typescript.sh – Compile TypeScript

**What it does:** Compiles TypeScript (`.ts`) in your plugin directories to JavaScript (`.js`) and can generate minified (`.min.js`) files. Optional watch mode recompiles when files change.

**When to use it:** When your plugin uses TypeScript; run after editing `.ts` files or use watch mode while developing.

**Command:**

```bash
./tools/typescript.sh [watch]
```

- No argument: one-time compile.
- `watch`: keep running and recompile on file changes (stop with Ctrl+C).

---

### unpack.sh – Unpack a plugin package

**What it does:** Unpacks a built plugin package (e.g. `.tar.gz`) into the plugin’s `temp_edit/` folder so you can inspect or modify the packed contents. Frontend `templates.tar` is extracted into `templates/` (WoltLab layout); ACP templates into `acptemplates/`. PIP XMLs stay in the package root.

**When to use it:** When you have a package file and want to see or edit what’s inside without installing it in WoltLab.

**Command:**

```bash
./tools/unpack.sh [plugin] [package_file]
```

- `plugin`: plugin directory name (e.g. `basis-plugin`); can be left empty to use the first detected plugin.
- `package_file`: optional path to a specific `.tar.gz`; if omitted, the latest package under `releases/<plugin>/` is used (fallback: plugin root).

---

### Toolkit smoke tests (SWPM itself)

```bash
./tools/tests/run-tests.sh
```

Covers `common.sh` helpers (release paths, `check_port_reachable` allowlist) and `check-package-pip-archives.py`. Also runs in CI via `.github/workflows/tools-tests.yml`.

### Optional checks (TypeScript, PHPStan, ruff)

Only when that tech is part of the project:

```bash
./tools/check-typescript.sh [--no-emit] [plugin]   # when tsconfig / ts/ exist — build + validate
./tools/run-phpstan.sh [plugin]                    # only with phpstan.neon(.dist)
./tools.sh lint:python                             # ruff on manager tools/*.py (skips if missing)
./tools.sh phpstan [plugin]
```

- **TypeScript:** If sources exist and `tsc` fails → build/validate abort.
- **PHPStan / ruff:** Optional; skipped without binary or config.

### validate-plugin.sh – Security and store compliance

**What it does:** Checks the plugin locally before release/store: PHP/XML syntax, languages (DE/EN), templates, PIP sources, security heuristics (XSS/SQL), guideline rules, and more. Does **not** upload anything. Some items stay manual — see the [Plugin Store checklist](docs/PLUGIN-STORE-CHECKLIST.en.md).

**When:** Before every release or before a real store upload.

```bash
./tools/validate-plugin.sh [--strict] [plugin_path]
```

**Check registry (build):** Fail/warn checks for the **build** live in `swpm-check-registry.txt` and run via `swpm-run-checks.sh`. Validate covers the same topics (plus extra store checks) but does not call the runner 1:1. List them:

```bash
./tools/swpm-run-checks.sh --mode list
./tools/swpm-run-checks.sh --mode build [--strict-layout] /path/to/temp_edit
```

**Individual checks (without full validation):**

```bash
python3 tools/check-pip-sources.py --strict /path/to/plugin
python3 tools/check-language-keys.py /path/to/plugin
python3 tools/check-template-xss.py /path/to/plugin
python3 tools/check-like-escaping.py /path/to/plugin
python3 tools/check-language-categories.py /path/to/plugin
python3 tools/check-style-package.py /path/to/plugin
python3 tools/fix-template-xss-escaping.py /path/to/plugin --dry-run
```

`check-pip-sources.py` stays outside the registry (needs `package.xml`). Details: [docs/SECURITY-CHECKS.en.md](docs/SECURITY-CHECKS.en.md) · languages: [docs/LANGUAGE-XML.en.md](docs/LANGUAGE-XML.en.md)

---

### Product line (core + add-ons)

**What it does:** Check and build related packages in dependency order via `swpm-family.json` (`family:check`, `family:build`, `family:validate`). Optional scaffold: `family:init --scaffold`.

**Commands:**

```bash
./tools.sh family:list
./tools.sh family:check
./tools.sh family:build patch
./tools/swpm-family.sh --manifest /path/swpm-family.json check
```

Details: [docs/PRODUCT-LINE.en.md](docs/PRODUCT-LINE.en.md).

---

### prepare-acp-install.sh – Prepare package for ACP upload

**What it does:** Finds the newest `.tar.gz` under `releases/<plugin>/` (fallback: plugin root), copies it into the local Docker web container (`woltlab-web`), and prints the steps for the manual ACP upload.

**When:** After `./tools/build.sh`, before testing the package in WoltLab. You still pick the file yourself in the ACP dialog.

```bash
./tools/prepare-acp-install.sh [plugin]
```

Details: [docs/ACP-PACKAGE-INSTALL.en.md](docs/ACP-PACKAGE-INSTALL.en.md)

---

### setup-minimal.sh – One-time setup

**What it does:** Guides you through a minimal setup: download WoltLab Core (or set path), clone WoltLab docs and/or WCF from GitHub, clone the WoltLab d.ts typings for TypeScript, set an optional path to a local WoltLab installation. It writes settings into `tools/.env` and can update the workspace file and Intelephense paths.

**When to use it:** Once after cloning the repo, or when you want to add/change Core, docs, typings, or the local install path.

**Command:**

```bash
./tools/setup-minimal.sh
```

Run from the repo root. You will be prompted for each step; you can skip any you don’t need.

---

### help.sh – Open documentation

**What it does:** Opens or displays the tools documentation (this README and related docs) so you can read them in the terminal or in your editor.

**When to use it:** When you want a quick reminder of commands or to read the full tool descriptions.

**Command:**

```bash
./tools/help.sh
```

---

### download-woltlab-core.sh – Download WoltLab Core

**What it does:** Downloads the WoltLab Suite installation package from the official site and places it (or the extracted Core files) where the rest of the setup expects them (e.g. for use with a local server or DDEV).

**When to use it:** If you need Core separately from the full setup (e.g. you already ran setup but skipped the download). Otherwise the main setup (`setup-minimal.sh`) can do this for you.

**Command:** Run from the repo root; the script or the main README will state the exact command (e.g. `./tools/download-woltlab-core.sh`). You must be logged in to the WoltLab customer area for the download.

---

## Further documentation

The typings come from the **official WoltLab d.ts repository** on GitHub. During setup you can clone it into `woltlab-d-ts` so your API usage stays in sync with WoltLab. To update them later, run `git pull` in the `woltlab-d-ts` folder.

If you ran setup and chose to clone the typings, they live in the `woltlab-d-ts` folder at the workspace root. To use them in a plugin:

1. In your plugin’s `temp_edit/tsconfig.json` (or the folder where your `.ts` files are), add a path to the typings. For example, from a plugin at `basis-plugin/temp_edit/`:

```json
"typeRoots": ["../../woltlab-d-ts"]
```

2. Adjust the path if your plugin lives in a different depth (e.g. `mein-plugin/extracted_plugin/xyz/temp_edit/` might use `../../../../woltlab-d-ts`).

After that, your editor and the TypeScript compiler can use WoltLab API types. See [WoltLab d.ts](https://github.com/WoltLab/d.ts) for the upstream project.

---

## Configuration

- **`tools/.env`** — Main config file. It is not committed to Git. Here you can set:
  - Path to your local WoltLab installation
  - GitHub repo URL for push (`GIT_REPO_URL`)
  - WoltLab d.ts clone URL or path (if needed)
  - Other options used by the scripts

- **`tools/.env.example`** — Template listing available variables. Copy it to `tools/.env` and fill in values. The setup script can create or update `tools/.env` for you.

---

## Further documentation

Full index / navigation: **[MkDocs site](https://benjarogit.github.io/simple-woltlab-plugin-manager/en/)** (source: `tools/docs/`)

| Topic | Link (repo) |
|-------|------|
| Tools overview | [TOOLS-OVERVIEW.en.md](docs/TOOLS-OVERVIEW.en.md) |
| Platforms | [CROSS-PLATFORM.en.md](docs/CROSS-PLATFORM.en.md) |
| Package layout / styles | [PACKAGE-LAYOUT.en.md](docs/PACKAGE-LAYOUT.en.md) |
| Product line | [PRODUCT-LINE.en.md](docs/PRODUCT-LINE.en.md) |
| Store checklist | [PLUGIN-STORE-CHECKLIST.en.md](docs/PLUGIN-STORE-CHECKLIST.en.md) |
| Security checks | [SECURITY-CHECKS.en.md](docs/SECURITY-CHECKS.en.md) |
| Language XML | [LANGUAGE-XML.en.md](docs/LANGUAGE-XML.en.md) |
| ACP install (Docker) | [ACP-PACKAGE-INSTALL.en.md](docs/ACP-PACKAGE-INSTALL.en.md) |

---

## Other notes

- **Logging:** Central debug log at `tools/docs/logs/woltlab-dev-debug.log`. See [LOGGING.en.md](docs/LOGGING.en.md) / [LOGGING.de.md](docs/LOGGING.de.md).
- **Language:** Menu language can be set to DE or EN via `WOLTLAB_LANG` in `tools/.env` or the menu option “Switch language” (L). Translations live in `tools/language/de.txt` and `tools/language/en.txt` (key=value). The `tr "key"` function in `common.sh` returns the string for the current language; scripts can be migrated to use it step by step.
- **Repo root:** All commands assume you are in the repository root (the folder that contains `tools/`) unless stated otherwise.
