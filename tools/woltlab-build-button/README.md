# WoltLab Build Button

Minimale Extension: **Build-Button** und **Tools-Menü** in der Seitenleiste.

## Wartung: TOOL_ENTRIES ↔ tools.sh

Die Einträge in `extension.js` (TOOL_ENTRIES) entsprechen der Reihenfolge des Hauptmenüs in `tools/tools.sh` (Optionen 1–14 + Tools-Menü). Bei Änderungen an den Tools (z. B. neue Menüpunkte in tools.sh) die TOOL_ENTRIES und die `commands` in `package.json` hier anpassen.

## Wo ist der Button?

- **Linke Leiste (Activity Bar):** Neues Icon **„WoltLab“** (▶-Symbol).
- **Klick darauf** → Seitenleiste öffnet sich mit dem Eintrag **„▶ WoltLab Build (Patch)“**.
- **Einen Klick auf diesen Eintrag** → Build startet.

(Dasselbe Icon wie Explorer/Suche – nur für WoltLab Build.)

## Installation in Cursor / VS Code

1. **Strg+Shift+P** → **„Developer: Install Extension from Location…“**
2. Ordner auswählen: **`tools/woltlab-build-button`** (im WoltLab-Entwicklungs-Root)
3. **Fenster neu laden** (Reload), damit die neue Version aktiv wird.

## Deinstallieren

Extensions → „WoltLab Build Button“ → Deinstallieren.
