<p align="center">
  <img src="docs/assets/swpm-logo.jpg" alt="SWPM — Simple WoltLab Plugin Manager" width="360">
</p>

# Simple WoltLab Plugin Manager

**[Deutsche Version](README.de.md)**

---

## What is this?

The **Simple WoltLab Plugin Manager** is a **cross-platform** command-line toolkit for the full **WoltLab Suite** plugin lifecycle: setup, development, build, validation, and release. A central text menu runs everything from any terminal.

- **Environment setup** — Downloads WoltLab Core, clones official docs and WCF source, installs TypeScript typings (d.ts), sets paths to your local WoltLab install.
- **Development** — TypeScript compile (with watch), unpack packages for inspection, centralized debug logging.
- **Build** — Creates distributable `.tar.gz` packages with semantic version bumping (patch/minor/major).
- **Quality assurance** — Validates plugins with security (SQL, XSS), translations (DE/EN), **offline DevTools-parity checks** (PIP sources, missing language keys with file:line), minimum version, WoltLab Cloud compatibility, store requirements.
- **Release** — Git commit, push, version tagging, GitHub release creation and asset upload.
- **Optional** — DDEV integration for a local WoltLab dev server.

Aligned with the official [WoltLab Plugin Store guidelines](https://www.woltlab.com/pluginstore/en/guidelines/). TypeScript typings come from the official [WoltLab d.ts](https://github.com/WoltLab/d.ts) repo and are cloned during setup.

---

## What do I need?

- **Bash environment** — Linux, macOS, **Windows WSL2**, or **Git Bash** (see [Cross-platform](tools/docs/CROSS-PLATFORM.md)).
- **Git** and **tar** — required for cloning, building, and packaging.
- **Python 3** (recommended) — validation and language-check scripts.
- **Node.js and npm** (optional) — TypeScript in your plugin.

No prior knowledge of WoltLab plugin structure is required. The menu and [tools documentation](tools/README.md) guide you step by step.

---

## Quick Start

1. **Clone and open a terminal** in the repo root (the folder that contains `tools.sh`).

2. **Run the tools**
   ```bash
   ./tools.sh
   ```
   On Windows without a Unix shell, use **`tools.cmd`** (requires [Git for Windows](https://git-scm.com/download/win)).

   Without arguments you get the **interactive menu** (Build, TypeScript, Git, validation, etc.). For all CLI commands: `./tools.sh help`. For Core, docs, typings, and paths: **`./tools.sh setup`** when you are ready—not on first launch.

3. **Use the menu**  
   Type the number of the option you want (e.g. `1` for Build, `2` for Git Push) and follow the prompts. Type `0` to exit. Details in [tools/README.md](tools/README.md).

> **Tip:** Run setup anytime via **`./tools.sh setup`** or `./tools/setup-minimal.sh`. If you set a path to a local WoltLab installation, optional editor workspace paths can be updated automatically.

---

## Cross-platform

| Platform | Command |
|----------|---------|
| Linux / macOS | `./tools.sh` |
| Windows (WSL2) | `./tools.sh` inside WSL |
| Windows (Git Bash) | `./tools.sh` or `tools.cmd` |

Full details: **[tools/docs/CROSS-PLATFORM.md](tools/docs/CROSS-PLATFORM.md)**

---

## Folder structure

| Folder | Purpose |
|--------|---------|
| `basis-plugin` | Your main or base plugin project. |
| `mein-plugin` | Additional plugin projects (e.g. that depend on the base plugin). |
| `plugins-integrieren` | External or reference plugins you want to keep in the workspace. |
| `woltlab-core` | Where setup stores WoltLab Core files (e.g. WCFSetup) after download. |
| `woltlab-docs` | WoltLab documentation (Git clone, optional during setup). |
| `woltlab-github` | WoltLab WCF source code (Git clone, optional during setup). |
| `woltlab-d-ts` | WoltLab TypeScript typings (d.ts) for use in your plugin’s TypeScript code. |
| `tools` | All scripts and setup live here. |

---

## Tools at a glance

| Tool | What it does | Command |
|------|----------------|--------|
| **Main menu** | Starts the interactive menu; `./tools.sh help` lists CLI commands. | `./tools.sh` or `./tools/tools.sh` |
| **Setup** | Downloads Core, docs, typings; sets paths. | `./tools/setup-minimal.sh` |
| **Build** | Builds your plugin and can bump version (patch/minor/major). | `./tools/build.sh patch` |
| **Git Push** | Commits, pushes, and creates a GitHub release for your plugin. | `./tools/gitpush.sh` |
| **TypeScript** | Compiles TypeScript to JavaScript (and optional watch mode). | `./tools/typescript.sh` |
| **Unpack** | Unpacks a plugin package into `temp_edit/`. | `./tools/unpack.sh` |
| **Validation** | Checks your plugin for security issues and store compliance. | `./tools/validate-plugin.sh` |
| **Help** | Opens the tools documentation. | `./tools/help.sh` |

Full description of each tool: **[tools/README.md](tools/README.md)**.

---

## Optional: TypeScript and WoltLab typings

If you use TypeScript in your plugin, run setup and accept **"d.ts klonen"** (default: yes). That clones the [WoltLab d.ts](https://github.com/WoltLab/d.ts) typings into `woltlab-d-ts`. In your plugin’s `temp_edit/tsconfig.json` you can then point to them, for example:

```json
"typeRoots": ["../../woltlab-d-ts"]
```

Details and other options are in [tools/README.md](tools/README.md).

---

## Optional: editor workspace

`simple-woltlab-plugin-manager.code-workspace` is an optional multi-root layout for VS Code (or compatible editors). **Not required** — all tools work from the terminal.

---

## Configuration

Settings like the path to your local WoltLab installation or your GitHub repo URL are stored in **`tools/.env`**. That file is not committed to Git (it may contain secrets). Use **`tools/.env.example`** as a template: copy it to `tools/.env` and fill in the values. The setup script can create or update `tools/.env` for you.

---

## Links

- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- [WoltLab Docs (GitHub)](https://github.com/WoltLab/docs.woltlab.com)
- [WoltLab WCF (GitHub)](https://github.com/WoltLab/WCF)
- [WoltLab Plugin Store Guidelines](https://www.woltlab.com/pluginstore/de/richtlinien/)
