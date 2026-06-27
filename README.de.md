# Simple WoltLab Plugin Manager

**[English version](README.md)**

---

## Was ist das?

Der **Simple WoltLab Plugin Manager** ist ein Kommandozeilen-Toolkit für den kompletten **WoltLab Suite**-Plugin-Lebenszyklus: Setup, Entwicklung, Build, Validierung und Release. Ein zentrales Textmenü startet alles; optional ergänzt eine VS Code/Cursor-Integration Buttons in der Seitenleiste.

- **Umgebungs-Setup** — Lädt WoltLab Core, klont offizielle Doku und WCF-Quellcode, richtet TypeScript-Typings (d.ts) ein, setzt Pfade zu deiner lokalen WoltLab-Installation.
- **Entwicklung** — TypeScript-Kompilierung (inkl. Watch), Pakete entpacken zur Inspektion, zentrale Debug-Logs.
- **Build** — Erstellt verteilbare `.tar.gz`-Pakete mit semantischer Versionserhöhung (patch/minor/major).
- **Qualitätssicherung** — Validiert Plugins: Sicherheit (SQL, XSS), Übersetzungen (DE/EN), **offline DevTools-Parität** (PIP-Quellen, fehlende Sprach-Keys mit Datei:Zeile), Minversion, WoltLab-Cloud-Kompatibilität, Store-Vorgaben.
- **Release** — Git-Commit, Push, Version-Tags, GitHub-Release inkl. Asset-Upload.
- **Optional** — DDEV-Anbindung für lokalen WoltLab-Server; Build-Button-Extension für VS Code/Cursor.

Ausgerichtet an den offiziellen [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/). Die TypeScript-Typings stammen vom offiziellen [WoltLab-d.ts](https://github.com/WoltLab/d.ts)-Repo und werden beim Setup geklont.

---

## Was brauche ich?

- **Git** — erforderlich zum Klonen, Bauen und Pushen deiner Plugins.
- **Node.js und npm** (optional) — nötig, wenn du TypeScript oder die Build-Skripte für JavaScript nutzt.
- **VS Code oder Cursor** (optional, aber empfohlen) — du kannst die mitgelieferte Workspace-Datei öffnen für eine übersichtliche Projektstruktur; die Tools funktionieren auch in jedem Terminal.

Du musst kein Vorwissen zur WoltLab-Plugin-Struktur mitbringen. Das Menü und die [Tools-Dokumentation](tools/README.de.md) führen dich Schritt für Schritt.

---

## Schnellstart

1. **Workspace öffnen**  
   Öffne die Datei `simple-woltlab-plugin-manager.code-workspace` in **VS Code** oder **Cursor**. Damit werden die richtigen Ordner (z. B. deine Plugins und die Tools) geladen.

2. **Tools starten**  
   Öffne ein Terminal und führe im **Repo-Root** (der Ordner, in dem `tools.sh` liegt) aus:
   ```bash
   ./tools.sh
   ```
   Du kannst auch `./tools/tools.sh` von dort aus starten. Ohne Argumente startet das **interaktive Menü** (Build, TypeScript, Git, Validierung usw.). Alle CLI-Befehle siehst du mit **`./tools.sh help`**. Core, Doku, Typings und Pfade richtest du mit **`./tools.sh setup`** (oder `./tools/setup-minimal.sh`) ein – wann du willst, nicht automatisch beim ersten Start.

3. **Menü nutzen**  
   Gib die Nummer der gewünschten Option ein (z. B. `1` für Build, `2` für Git Push) und folge den Hinweisen. Mit `0` beendest du. Details und eine Beschreibung jedes Tools stehen in [tools/README.de.md](tools/README.de.md).

> **Tipp:** Setup startest du jederzeit mit **`./tools.sh setup`**, über die entsprechende Menüoption oder `./tools/setup-minimal.sh`. Wenn du einen Pfad zu einer lokalen WoltLab-Installation angibst, werden Workspace-Datei und Editor-Pfade automatisch angepasst.

---

## Ordnerstruktur

| Ordner | Zweck |
|--------|--------|
| `basis-plugin` | Dein Haupt- oder Basis-Plugin-Projekt. |
| `mein-plugin` | Weitere Plugin-Projekte (z. B. abhängig vom Basis-Plugin). |
| `plugins-integrieren` | Externe oder Referenz-Plugins, die du im Workspace behalten willst. |
| `woltlab-core` | Hier legt das Setup WoltLab-Core-Dateien (z. B. WCFSetup) nach dem Download ab. |
| `woltlab-docs` | WoltLab-Dokumentation (Git-Klon, optional im Setup). |
| `woltlab-github` | WoltLab-WCF-Quellcode (Git-Klon, optional im Setup). |
| `woltlab-d-ts` | WoltLab-TypeScript-Typings (d.ts) für die Nutzung in deinem Plugin-TypeScript. |
| `tools` | Alle Skripte, Setup und die Build-Button-Extension liegen hier. |

---

## Tools im Überblick

| Tool | Was es macht | Befehl |
|------|----------------|--------|
| **Hauptmenü** | Startet das interaktive Menü; `./tools.sh help` listet CLI-Befehle. | `./tools.sh` oder `./tools/tools.sh` |
| **Setup** | Lädt Core, Doku, Typings; setzt Pfade und optionale MCP-Vorlage. | `./tools/setup-minimal.sh` |
| **Build** | Baut dein Plugin und kann die Version erhöhen (patch/minor/major). | `./tools/build.sh patch` |
| **Git Push** | Committet, pusht und erstellt ein GitHub-Release für dein Plugin. | `./tools/gitpush.sh` |
| **TypeScript** | Kompiliert TypeScript zu JavaScript (optional Watch-Modus). | `./tools/typescript.sh` |
| **Unpack** | Entpackt ein Plugin-Paket nach `temp_edit/`. | `./tools/unpack.sh` |
| **Validierung** | Prüft dein Plugin auf Sicherheit und Store-Compliance. | `./tools/validate-plugin.sh` |
| **Hilfe** | Öffnet die Tools-Dokumentation. | `./tools/help.sh` |

Vollständige Beschreibung jedes Tools inkl. Optionen und wann du es nutzt: **[tools/README.de.md](tools/README.de.md)**.

---

## Optional: Build-Button-Extension

Im Ordner `tools/woltlab-build-button` findest du eine **VS Code-/Cursor**-Extension. Sie fügt einen **„WoltLab“**-Eintrag in der Seitenleiste hinzu mit Buttons für Build, Git Push, TypeScript, Unpack, Hilfe, Validierung und das komplette Tools-Menü, sodass du keine Befehle tippen musst. Lade sie als **Development**-Extension aus diesem Ordner. Installation und Nutzung in [tools/README.de.md](tools/README.de.md).

---

## Optional: TypeScript und WoltLab-Typings

Wenn du TypeScript in deinem Plugin nutzt, führe das Setup aus und bestätige **„d.ts klonen“** (Standard: ja). Damit werden die [WoltLab d.ts](https://github.com/WoltLab/d.ts)-Typings nach `woltlab-d-ts` geklont. In der `temp_edit/tsconfig.json` deines Plugins kannst du dann darauf verweisen, z. B.:

```json
"typeRoots": ["../../woltlab-d-ts"]
```

Details und weitere Optionen in [tools/README.de.md](tools/README.de.md).

---

## Optional: Cursor / MCP

Wenn du Cursor mit MCP (z. B. DeepWiki) nutzt, liegt die Konfiguration pro Projekt unter `.cursor/` (z. B. `basis-plugin/.cursor/mcp.json`). Das Setup-Skript kann optional eine Vorlage nach `basis-plugin/.cursor/` kopieren, damit du sie nicht von Hand anlegen musst.

---

## Konfiguration

Einstellungen wie der Pfad zu deiner lokalen WoltLab-Installation oder deine GitHub-Repo-URL stehen in **`tools/.env`**. Diese Datei wird nicht in Git eingecheckt (sie kann sensible Daten enthalten). Nutze **`tools/.env.example`** als Vorlage: kopiere sie nach `tools/.env` und trage die Werte ein. Das Setup-Skript kann `tools/.env` für dich anlegen oder aktualisieren.

---

## Links

- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- [WoltLab Docs (GitHub)](https://github.com/WoltLab/docs.woltlab.com)
- [WoltLab WCF (GitHub)](https://github.com/WoltLab/WCF)
- [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/)
