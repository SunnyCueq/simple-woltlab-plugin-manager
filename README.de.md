# Simple WoltLab Plugin Manager

**[English version](README.md)**

---

## Was ist das?

Der **Simple WoltLab Plugin Manager** ist ein schlankes Toolkit zum Entwickeln, Bauen und Veröffentlichen von **WoltLab Suite**-Plugins. Über ein zentrales Textmenü startest du alles: Plugins bauen, Versionen erhöhen, zu Git pushen, Releases erstellen, TypeScript kompilieren, Pakete entpacken und Code prüfen. Ein optionaler Setup-Schritt kann WoltLab Core herunterladen, die offizielle Doku und den Quellcode klonen, TypeScript-Typings einrichten und Pfade zu deiner lokalen WoltLab-Installation setzen – damit du mit minimaler manueller Konfiguration loslegen kannst.

**WoltLab und Plugin-Store.** Das Toolkit ist an die offiziellen [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/) und bewährte Praktiken ausgerichtet. Die integrierte Validierung prüft u. a. Sicherheit (SQL, XSS), Übersetzungen (DE/EN), Minversion und Store-Vorgaben – so hilft es dir, ein store-taugliches Plugin zu erstellen. Die TypeScript-Typings stammen vom offiziellen **WoltLab-d.ts**-Repository auf GitHub und werden beim Setup geklont, damit deine API-Nutzung mit WoltLab aktuell bleibt.

---

## Was brauche ich?

- **Git** — erforderlich zum Klonen, Bauen und Pushen deiner Plugins.
- **Node.js und npm** (optional) — nötig, wenn du TypeScript oder die Build-Skripte für JavaScript nutzt.
- **VS Code oder Cursor** (optional, aber empfohlen) — du kannst die mitgelieferte Workspace-Datei öffnen für eine übersichtliche Projektstruktur; die Tools funktionieren auch in jedem Terminal.

Du musst kein Vorwissen zur WoltLab-Plugin-Struktur mitbringen. Das Menü und die [Tools-Dokumentation](tools/README.de.md) führen dich Schritt für Schritt.

---

## Schnellstart

1. **Workspace öffnen**  
   Öffne die Datei `woltlab-development.code-workspace` in **VS Code** oder **Cursor**. Damit werden die richtigen Ordner (z. B. deine Plugins und die Tools) geladen.

2. **Tools starten**  
   Öffne ein Terminal und führe im **Repo-Root** (der Ordner, in dem `tools.sh` liegt) aus:
   ```bash
   ./tools.sh
   ```
   Du kannst auch `./tools/tools.sh` von dort aus starten. Beim ersten Start wirst du gefragt, ob du **Setup** ausführen möchtest. Sag ja, wenn du WoltLab Core, Doku, GitHub-Klon, TypeScript-Typings herunterladen und optional einen Pfad zu deiner lokalen WoltLab-Installation setzen willst. Danach erscheint das Hauptmenü mit Build, Git Push, TypeScript, Unpack, Hilfe, Validierung und Setup.

3. **Menü nutzen**  
   Gib die Nummer der gewünschten Option ein (z. B. `1` für Build, `2` für Git Push) und folge den Hinweisen. Mit `0` beendest du. Details und eine Beschreibung jedes Tools stehen in [tools/README.de.md](tools/README.de.md).

> **Tipp:** Setup kannst du später jederzeit über die Menüoption **„Setup / Vorbereitung“** oder mit `./tools/setup-minimal.sh` ausführen. Wenn du einen Pfad zu einer lokalen WoltLab-Installation angibst, werden Workspace-Datei und Editor-Pfade automatisch angepasst.

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

Mehr Details in [docs/STRUKTUR.md](docs/STRUKTUR.md), falls diese Datei in deinem Klon vorhanden ist.

---

## Tools im Überblick

| Tool | Was es macht | Befehl |
|------|----------------|--------|
| **Hauptmenü** | Startet das interaktive Menü. | `./tools.sh` oder `./tools/tools.sh` |
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

Im Ordner `tools/woltlab-build-button` findest du eine **VS Code-/Cursor-**Extension. Sie fügt einen **„WoltLab“-**Eintrag in der Seitenleiste hinzu mit Buttons für Build, Git Push, TypeScript, Unpack, Hilfe, Validierung und das komplette Tools-Menü, sodass du keine Befehle tippen musst. Lade sie als **Development-**Extension aus diesem Ordner. Installation und Nutzung in [tools/README.de.md](tools/README.de.md).

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
