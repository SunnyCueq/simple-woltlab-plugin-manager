# WoltLab Build Button

**[Deutsche Version](README.de.md)**

Minimal extension: **Build** button and **Tools** menu in the sidebar.

## Maintenance: TOOL_ENTRIES ↔ tools.sh

The entries in `extension.js` (TOOL_ENTRIES) match the main menu order in `tools/tools.sh` (options 1–14 + Tools menu). When you add or change menu items in tools.sh, update TOOL_ENTRIES and the `commands` in `package.json` here.

## Where is the button?

- **Left bar (Activity Bar):** New **„WoltLab“** icon (▶ symbol).
- **Click it** → Sidebar opens with **„▶ WoltLab Build (Patch)“**.
- **Click that entry** → Build runs.

(Same icon style as Explorer/Search – only for WoltLab Build.)

## Installation in Cursor / VS Code

1. **Ctrl+Shift+P** → **„Developer: Install Extension from Location…“**
2. Select folder: **`tools/woltlab-build-button`** (inside your WoltLab development root)
3. **Reload** the window so the extension is active.

## Uninstall

Extensions → „WoltLab Build Button“ → Uninstall.
