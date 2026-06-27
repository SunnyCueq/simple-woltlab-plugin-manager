# Tools – WoltLab Plugin Manager

**[Deutsche Version](README.de.md)**

---

## Overview

The `tools/` folder contains all scripts used for **WoltLab plugin development**: building plugins, pushing to Git, creating releases, compiling TypeScript, unpacking packages, validating code, and running a one-time setup. The tools support the path from development to the **WoltLab Plugin Store** and take WoltLab and Store requirements into account. Everything is driven from the main menu (`tools.sh`) or by calling the scripts directly. This page describes each tool so you know when and how to use it.

**Platforms:** Linux, macOS, **Windows (WSL2)** and **Windows (Git Bash)**. Use `tools.cmd` from cmd/Explorer on Windows. Plain cmd/PowerShell are not supported. Details: [docs/CROSS-PLATFORM.md](docs/CROSS-PLATFORM.md).

---

## Main menu (tools.sh)

**What it does:** Starts the interactive menu. From the repo root you can run `./tools.sh` or `./tools/tools.sh`. The menu shows the current state (e.g. detected plugins) and numbered options.

**Options:**

| Option | Name | Short description |
|--------|------|-------------------|
| 1 | Build | Build plugin(s) and bump version (patch/minor/major). |
| 2 | Git Push | Commit, push, and create a GitHub release for your plugin(s). |
| 3 | TypeScript | Compile TypeScript to JavaScript (normal or watch mode). |
| 4 | Unpack | Unpack a plugin package into `temp_edit/`. |
| 5 | Help & Documentation | Open this documentation. |
| 6 | Plugin Validation | Run security and store-compliance checks. |
| 7 | Setup / Preparation | Run the one-time setup (Core, docs, typings, paths). |
| 8 | Repo | Show or change the Git repository (origin) used for push. |
| 0 | Exit | Quit the menu. |

If `manager-push.sh` exists (maintainer only), option 9 appears for pushing the Plugin Manager itself.

---

## Each tool in detail

### build.sh – Build plugins

**What it does:** Finds your plugin(s) (folders with `package.xml`), compiles TypeScript if present, and builds an installable plugin archive (e.g. `.tar.gz`). It can also bump the version in `package.xml` (patch, minor, or major).

**When to use it:** Whenever you have changed plugin code and want an installable package to test in WoltLab or to ship.

**Command:**

```bash
./tools/build.sh [target] [version_type]
```

- `target`: leave empty for “first plugin”, or give a plugin directory name (e.g. `basis-plugin`), or `all` for all plugins.
- `version_type`: `patch` (default), `minor`, or `major`.

**Examples:**

```bash
./tools/build.sh              # First plugin, patch version
./tools/build.sh patch        # Same
./tools/build.sh basis-plugin minor
./tools/build.sh all patch
```

---

### gitpush.sh – Commit, push, and release (plugins)

**What it does:** Detects which plugin(s) have changes, commits them, pushes to the configured Git remote (origin), creates a version tag, and optionally a GitHub release with notes. This is for **plugin** releases, not for the Plugin Manager repo itself.

**When to use it:** When you are happy with your plugin changes and want to publish them to GitHub (commit + push + tag + release).

**Command:**

```bash
./tools/gitpush.sh [target] [commit_message]
```

- `target`: leave empty for auto-detect, or a plugin name, or `all`.
- `commit_message`: optional; otherwise one is generated from the plugin version.

**Requirements:** Git remote `origin` must point to your plugin (or workspace) repo. Use SSH or a Personal Access Token for GitHub. You can set `GIT_REPO_URL` in `tools/.env` or use menu option 8 to set the repo.

> **Tip:** Run this from the repo root. The script uses the same exclude list as the rest of the tools (e.g. it does not commit the contents of `woltlab-docs`, `woltlab-github`, or `tools/woltlab-dev/public`).

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

**What it does:** Unpacks a built plugin package (e.g. `.tar.gz`) into the plugin’s `temp_edit/` folder so you can inspect or modify the packed contents.

**When to use it:** When you have a package file and want to see or edit what’s inside without installing it in WoltLab.

**Command:**

```bash
./tools/unpack.sh [plugin] [package_file]
```

- `plugin`: plugin directory name (e.g. `basis-plugin`); can be left empty to use the first detected plugin.
- `package_file`: optional path to a specific `.tar.gz`; if omitted, the latest package in the plugin folder is used.

---

### validate-plugin.sh – Security and store compliance

**What it does:** Checks your plugin for common issues before release or store submission. It validates: **PHP and XML syntax**; **translations** (DE and EN present and consistent); **PIP sources** (DevTools parity: syncable vs package-only instructions); **plugin language keys** used in code vs `language/*.xml` with **file:line** locations; **minimum WoltLab version**; that no external package servers are used; **security** (e.g. SQL injection, XSS); **debug and development code** that should not ship; and **cloud/compatibility** and other store-related rules. The checks align with the [Plugin Store checklist](docs/PLUGIN-STORE-CHECKLIST.md), which also lists manual steps the script does not cover.

**When to use it:** Before releasing or submitting to the store, to catch problems early.

**Command:**

```bash
./tools/validate-plugin.sh [plugin_path]
```

- `plugin_path`: optional; plugin directory or path. If omitted, the current directory or the first detected plugin is used.

Results and details are shown in the terminal; logs may be written under `/tmp/` (see script output).

**Individual checks (without full validation):**

```bash
python3 tools/check-pip-sources.py --strict /path/to/plugin
python3 tools/check-language-keys.py /path/to/plugin
python3 tools/check-template-xss.py /path/to/plugin
python3 tools/check-like-escaping.py /path/to/plugin
python3 tools/check-language-categories.py /path/to/plugin
python3 tools/fix-template-xss-escaping.py /path/to/plugin --dry-run
```

`check-pip-sources.py` mirrors WoltLab DevTools PIP targets offline (no ACP sync). `check-language-keys.py` reports missing **app** keys (`shrinkr.*` etc.) with locations; core `wcf.*` phrases are ignored.

See [docs/SECURITY-CHECKS.de.md](docs/SECURITY-CHECKS.de.md) for heuristics and false positives.  
Language XML category rules: [docs/LANGUAGE-XML.de.md](docs/LANGUAGE-XML.de.md).

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

Documents in `tools/docs/`:

- **[docs/CROSS-PLATFORM.md](docs/CROSS-PLATFORM.md)** — Linux, macOS, Windows (WSL2, Git Bash), `tools.cmd`.
- **[docs/PLUGIN-STORE-CHECKLIST.md](docs/PLUGIN-STORE-CHECKLIST.md)** — Checklist before submitting a plugin to the WoltLab store: what `validate-plugin.sh` covers and what you should still check manually. English version: [docs/PLUGIN-STORE-CHECKLIST.en.md](docs/PLUGIN-STORE-CHECKLIST.en.md).
- **[docs/SECURITY-CHECKS.de.md](docs/SECURITY-CHECKS.de.md)** — XSS/LIKE/SQL validation heuristics (Shr1nkr / WoltLab 6.2.5 learnings).

---

## Other notes

- **Logging:** A single central debug log is used (`tools/docs/logs/woltlab-dev-debug.log`). Level and paths are configurable via `DEBUG_LEVEL`, `DEBUG_LOG_FILE`. See [tools/docs/LOGGING.md](docs/LOGGING.md) for the convention and levels.
- **Language:** Menu language can be set to DE or EN via `WOLTLAB_LANG` in `tools/.env` or the menu option “Switch language” (L). Translations live in `tools/language/de.txt` and `tools/language/en.txt` (key=value). The `tr "key"` function in `common.sh` returns the string for the current language; scripts can be migrated to use it step by step.
- **Repo root:** All commands assume you are in the repository root (the folder that contains `tools/`) unless stated otherwise.
