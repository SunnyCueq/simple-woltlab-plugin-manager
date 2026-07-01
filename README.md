<p align="center">
  <img src="docs/assets/swpm-logo.jpg" alt="SWPM — Simple WoltLab Plugin Manager" width="360">
</p>

<h1 align="center">Simple WoltLab Plugin Manager</h1>

<p align="center">
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager"><img src="https://img.shields.io/github/stars/benjarogit/simple-woltlab-plugin-manager?style=flat-square" alt="GitHub stars"></a>
  <img src="https://img.shields.io/badge/platform-Linux%20%7C%20macOS%20%7C%20WSL2%20%7C%20Git%20Bash-2d6a4f?style=flat-square" alt="Platforms">
  <img src="https://img.shields.io/badge/WoltLab%20Suite-6.x-1a5fb4?style=flat-square" alt="WoltLab Suite">
  <img src="https://img.shields.io/badge/docs-EN%20%7C%20DE-555?style=flat-square" alt="Bilingual docs">
</p>

<p align="center"><strong><a href="README.de.md">Deutsche Version</a></strong></p>

---

## Table of contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Quick start](#quick-start)
- [Cross-platform](#cross-platform)
- [Workspace layout](#workspace-layout)
- [Tools at a glance](#tools-at-a-glance)
- [Documentation](#documentation)
- [Configuration](#configuration)
- [External links](#external-links)

---

## Overview

The **Simple WoltLab Plugin Manager (SWPM)** is a cross-platform command-line toolkit for the full **WoltLab Suite** plugin lifecycle: setup, development, build, validation, and release. A central text menu drives everything from the terminal.

## Features

- **Environment setup** — Downloads WoltLab Core, clones official docs and WCF source, installs TypeScript typings (d.ts), configures paths to your local WoltLab install.
- **Development** — TypeScript compile (with watch), unpack packages for inspection, centralized debug logging.
- **Build** — Creates distributable `.tar.gz` packages with semantic version bumping; [wspackager parity](tools/docs/WSPACKAGER-PARITY.en.md) (`files/`, `files_wcf/`, `--json`).
- **Quality assurance** — Security (SQL, XSS), translations (DE/EN), offline DevTools-parity checks (PIP sources, language keys with file:line), minimum version, WoltLab Cloud compatibility, store requirements.
- **Release** — Git commit, push, version tags, GitHub release creation and asset upload.
- **Optional** — Docker helpers for local ACP testing; DDEV integration.

Aligned with the official [WoltLab Plugin Store guidelines](https://www.woltlab.com/pluginstore/en/guidelines/). TypeScript typings come from [WoltLab d.ts](https://github.com/WoltLab/d.ts).

## Requirements

- **Bash** — Linux, macOS, **Windows WSL2**, or **Git Bash** ([details](tools/docs/CROSS-PLATFORM.md))
- **Git** and **tar**
- **Python 3** (recommended) — validation scripts
- **Node.js** (optional) — TypeScript in your plugin

No prior WoltLab plugin knowledge required. The menu and [tools documentation](tools/README.md) guide you step by step.

## Quick start

1. Clone the repo and open a terminal in the root (folder containing `tools.sh`).

2. Start the toolkit:
   ```bash
   ./tools.sh
   ```
   On Windows without a Unix shell: **`tools.cmd`** ([Git for Windows](https://git-scm.com/download/win)).

3. Use the interactive menu (Build, Git Push, validation, …). CLI overview: `./tools.sh help`. Run **`./tools.sh setup`** when you need Core, docs, typings, and paths — not on first launch.

## Cross-platform

| Platform | Command |
|----------|---------|
| Linux / macOS | `./tools.sh` |
| Windows (WSL2) | `./tools.sh` inside WSL |
| Windows (Git Bash) | `./tools.sh` or `tools.cmd` |

Full guide: **[tools/docs/CROSS-PLATFORM.md](tools/docs/CROSS-PLATFORM.md)**

## Workspace layout

| Folder | Purpose |
|--------|---------|
| `basis-plugin` | Your main or base plugin project |
| `mein-plugin` | Additional plugin projects |
| `plugins-integrieren` | External or reference plugins in the workspace |
| `woltlab-core` | WoltLab Core files after setup download |
| `woltlab-docs` | WoltLab documentation (optional Git clone) |
| `woltlab-github` | WCF source (optional Git clone) |
| `woltlab-d-ts` | WoltLab TypeScript typings for your plugin |
| `tools` | All scripts and setup |

## Tools at a glance

| Tool | Purpose | Command |
|------|---------|---------|
| **Main menu** | Interactive menu + CLI | `./tools.sh` |
| **Setup** | Core, docs, typings, paths | `./tools/setup-minimal.sh` |
| **Build** | Package + version bump | `./tools/build.sh patch` |
| **Git Push** | Commit, push, GitHub release | `./tools/gitpush.sh` |
| **TypeScript** | Compile TS → JS | `./tools/typescript.sh` |
| **Unpack** | Extract package to `temp_edit/` | `./tools/unpack.sh` |
| **Validation** | Security and store checks | `./tools/validate-plugin.sh` |
| **Help** | Open tools documentation | `./tools/help.sh` |

Details: **[tools/README.md](tools/README.md)**

## Documentation

| Resource | English | Deutsch |
|----------|---------|---------|
| Tools reference | [tools/README.md](tools/README.md) | [tools/README.de.md](tools/README.de.md) |
| All guides (index) | [tools/docs/README.md](tools/docs/README.md) | [tools/docs/README.de.md](tools/docs/README.de.md) |
| Contributing | [CONTRIBUTING.md](CONTRIBUTING.md) | [CONTRIBUTING.de.md](CONTRIBUTING.de.md) |

## Configuration

Settings (local WoltLab path, GitHub URL, …) live in **`tools/.env`** (not committed). Copy **`tools/.env.example`** and fill in values. Setup can create or update the file.

### Optional: TypeScript typings

After setup with **“clone d.ts”**, point your plugin `tsconfig.json` at the workspace typings:

```json
"typeRoots": ["../../woltlab-d-ts"]
```

### Optional: editor workspace

`simple-woltlab-plugin-manager.code-workspace` is an optional VS Code multi-root layout. **Not required** — all tools work from the terminal.

## External links

- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- [WoltLab Docs (GitHub)](https://github.com/WoltLab/docs.woltlab.com)
- [WoltLab WCF (GitHub)](https://github.com/WoltLab/WCF)
- [WoltLab Plugin Store Guidelines](https://www.woltlab.com/pluginstore/en/guidelines/)
