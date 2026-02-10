# Folder structure (detailed)

This document describes each main folder in the Simple WoltLab Plugin Manager workspace. For a short overview see the [main README](../README.md).

---

## Plugin folders

| Folder | Purpose |
|--------|---------|
| **basis-plugin** | Your main or base plugin. It usually contains the core functionality and is the first plugin you build and validate. Inside you typically have `temp_edit/` (source files), `package.xml`, and the built `.tar.gz` package. |
| **mein-plugin** | Additional plugin projects. Use this for plugins that depend on the base plugin or for separate products. Structure is the same as in basis-plugin (temp_edit, package.xml, etc.). |
| **plugins-integrieren** | Reference or third-party plugins you keep in the workspace for comparison or integration. Not necessarily built by you; used as reference. |

---

## WoltLab resources (setup and reference)

| Folder | Purpose |
|--------|---------|
| **woltlab-core** | Target folder for the WoltLab Suite installation package (e.g. WCFSetup) when you run the Core download. Setup stores files here so you can install or use them with a local server or DDEV. |
| **woltlab-docs** | Optional Git clone of the [official WoltLab documentation](https://github.com/WoltLab/docs.woltlab.com). Created during setup if you choose to clone docs. Use it as a local reference. |
| **woltlab-github** | Optional Git clone of the [WoltLab WCF](https://github.com/WoltLab/WCF) source. Created during setup if you choose to clone it. Use it to look up core code and APIs. |
| **woltlab-d-ts** | Git clone of the [official WoltLab TypeScript typings (d.ts)](https://github.com/WoltLab/d.ts). Created during setup when you accept "d.ts klonen". Your plugin’s TypeScript can reference this folder so the editor and compiler know WoltLab API types. Run `git pull` in this folder to update typings to the latest from WoltLab. |

---

## Tools

| Folder | Purpose |
|--------|---------|
| **tools** | All scripts and the Build Button extension. Contains `tools.sh` (main menu), `build.sh`, `gitpush.sh`, `typescript.sh`, `unpack.sh`, `validate-plugin.sh`, `setup-minimal.sh`, `help.sh`, `download-woltlab-core.sh`, and the `woltlab-build-button` extension. Configuration is in `tools/.env` (see `tools/.env.example`). Documentation: `tools/README.md` and `tools/docs/` (e.g. PLUGIN-STORE-CHECKLIST.md, PLUGIN-STORE-CHECKLIST.en.md). |

---

## Root files

- **tools.sh** — Shortcut to start the main menu (same as `./tools/tools.sh`).
- **woltlab-development.code-workspace** — VS Code / Cursor workspace file. Open this to load all relevant folders.
- **README.md** / **README.de.md** — Main documentation in English and German.
