# Simple WoltLab Plugin Manager

A lightweight toolkit for developing, building, and publishing **WoltLab Suite** plugins. It provides a central menu, build scripts, Git-based release workflow, and optional setup for WoltLab Core, documentation, TypeScript typings, and local installation paths.

---

## Requirements

- **Git** — for cloning, building, and pushing plugins
- **Node.js / npm** (optional) — for TypeScript compilation and build tooling

---

## Quick Start

1. **Open the workspace**  
   Open `woltlab-development.code-workspace` in **VS Code** or **Cursor**.

2. **Run the tools**  
   From the repo root, run:
   ```bash
   ./tools.sh
   ```
   or `./tools/tools.sh`. On first run you’ll be prompted to run **Setup** (WoltLab Core, Docs, GitHub clone, d.ts typings, optional local install path). After that you get the main menu: Build, Git Push, TypeScript, Unpack, Help, Validation.

3. **Run setup later**  
   Use menu option **“Setup / Vorbereitung”** or run:
   ```bash
   ./tools/setup-minimal.sh
   ```
   If you set a path to a local WoltLab installation, the workspace file and Intelephense paths are updated automatically.

---

## Optional: Build Button extension

In `tools/woltlab-build-button` you’ll find a **VS Code / Cursor** extension that adds a **“WoltLab”** sidebar with one-click actions: Build, Git Push, TypeScript, Unpack, Help, Validation, and the full tools menu. Load it as a **Development** extension from that folder.

---

## TypeScript & WoltLab typings

During setup, confirm **“d.ts klonen”** (default: yes) to clone the [WoltLab d.ts](https://github.com/WoltLab/d.ts) typings. In your plugin’s `temp_edit/tsconfig.json` add for example:

```json
"typeRoots": ["../../woltlab-d-ts"]
```

See [tools/README.md](tools/README.md) for details.

---

## Cursor / MCP

MCP configuration (e.g. DeepWiki) lives per project under `.cursor/` (e.g. `basis-plugin/.cursor/mcp.json`). Setup can optionally copy a template into `basis-plugin/.cursor/`.

---

## Folder structure

| Folder | Purpose |
|--------|---------|
| `basis-plugin` | Main / base plugin |
| `mein-plugin` | Additional plugins (e.g. depending on the base plugin) |
| `plugins-integrieren` | External plugins as reference |
| `woltlab-core` | Setup artifacts (e.g. WCFSetup); target of the Core download |
| `woltlab-docs` | WoltLab documentation (Git clone, optional in setup) |
| `woltlab-github` | WoltLab WCF source (Git clone, optional in setup) |
| `woltlab-d-ts` | WoltLab TypeScript typings (d.ts) for plugin development |
| `tools` | Scripts, setup, and the Build Button extension |

More detail: [docs/STRUKTUR.md](docs/STRUKTUR.md) (if present).

---

## Commands

| Action | Command |
|--------|---------|
| **Main menu** | `./tools.sh` or `./tools/tools.sh` |
| **Setup** | `./tools/setup-minimal.sh` |
| **Build plugin** | `./tools/build.sh patch` (or `minor` / `major`) |
| **Git Push (plugins)** | `./tools/gitpush.sh` |
| **TypeScript** | `./tools/typescript.sh` |
| **Unpack package** | `./tools/unpack.sh` |
| **Validation** | `./tools/validate-plugin.sh` |
| **Help** | `./tools/help.sh` |

Configuration (e.g. local WoltLab path, GitHub repo) is stored in `tools/.env`; see `tools/.env.example` for options.

---

## Links

- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- [WoltLab Docs (GitHub)](https://github.com/WoltLab/docs.woltlab.com)
- [WoltLab WCF (GitHub)](https://github.com/WoltLab/WCF)
- [WoltLab Plugin Store Guidelines](https://www.woltlab.com/pluginstore/de/richtlinien/)
