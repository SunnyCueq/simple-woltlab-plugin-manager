<p align="center">
  <img src="docs/assets/swpm-logo.jpg" alt="SWPM — Simple WoltLab Plugin Manager" width="360">
</p>

# Simple WoltLab Plugin Manager

**[English version](README.md)**

---

## Was ist das?

Der **Simple WoltLab Plugin Manager** ist ein **plattformübergreifendes** Kommandozeilen-Toolkit für den kompletten **WoltLab Suite**-Plugin-Lebenszyklus: Setup, Entwicklung, Build, Validierung und Release. Ein zentrales Textmenü startet alles im Terminal.

- **Umgebungs-Setup** — Lädt WoltLab Core, klont offizielle Doku und WCF-Quellcode, richtet TypeScript-Typings (d.ts) ein, setzt Pfade zu deiner lokalen WoltLab-Installation.
- **Entwicklung** — TypeScript-Kompilierung (inkl. Watch), Pakete entpacken zur Inspektion, zentrale Debug-Logs.
- **Build** — Erstellt verteilbare `.tar.gz`-Pakete mit semantischer Versionserhöhung (patch/minor/major).
- **Qualitätssicherung** — Validiert Plugins: Sicherheit (SQL, XSS), Übersetzungen (DE/EN), **offline DevTools-Parität** (PIP-Quellen, fehlende Sprach-Keys mit Datei:Zeile), Minversion, WoltLab-Cloud-Kompatibilität, Store-Vorgaben.
- **Release** — Git-Commit, Push, Version-Tags, GitHub-Release inkl. Asset-Upload.
- **Optional** — DDEV-Anbindung für lokalen WoltLab-Server.

Ausgerichtet an den offiziellen [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/). Die TypeScript-Typings stammen vom offiziellen [WoltLab-d.ts](https://github.com/WoltLab/d.ts)-Repo und werden beim Setup geklont.

---

## Was brauche ich?

- **Bash-Umgebung** — Linux, macOS, **Windows WSL2** oder **Git Bash** (siehe [Plattformen](tools/docs/CROSS-PLATFORM.de.md)).
- **Git** und **tar** — Pflicht für Klonen, Bauen und Paketieren.
- **Python 3** (empfohlen) — Validierungs- und Sprach-Checks.
- **Node.js und npm** (optional) — TypeScript im Plugin.

Du musst kein Vorwissen zur WoltLab-Plugin-Struktur mitbringen. Das Menü und die [Tools-Dokumentation](tools/README.de.md) führen dich Schritt für Schritt.

---

## Schnellstart

1. **Repo klonen** und im Repo-Root (Ordner mit `tools.sh`) ein Terminal öffnen.

2. **Tools starten**
   ```bash
   ./tools.sh
   ```
   Unter Windows ohne Unix-Shell: **`tools.cmd`** (benötigt [Git for Windows](https://git-scm.com/download/win)).

   Ohne Argumente startet das **interaktive Menü**. Alle CLI-Befehle: `./tools.sh help`. Core, Doku, Typings und Pfade: **`./tools.sh setup`** — wann du willst, nicht automatisch beim ersten Start.

3. **Menü nutzen**  
   Nummer der Option eingeben (z. B. `1` Build, `2` Git Push), mit `0` beenden. Details in [tools/README.de.md](tools/README.de.md).

> **Tipp:** Setup jederzeit mit **`./tools.sh setup`** oder `./tools/setup-minimal.sh`. Bei Angabe eines lokalen WoltLab-Pfads können optionale Editor-Workspace-Pfade angepasst werden.

---

## Plattformen

| Plattform | Befehl |
|-----------|--------|
| Linux / macOS | `./tools.sh` |
| Windows (WSL2) | `./tools.sh` in WSL |
| Windows (Git Bash) | `./tools.sh` oder `tools.cmd` |

Details: **[tools/docs/CROSS-PLATFORM.de.md](tools/docs/CROSS-PLATFORM.de.md)**

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
| `tools` | Alle Skripte und das Setup liegen hier. |

---

## Tools im Überblick

| Tool | Was es macht | Befehl |
|------|----------------|--------|
| **Hauptmenü** | Interaktives Menü; `./tools.sh help` listet CLI-Befehle. | `./tools.sh` oder `./tools/tools.sh` |
| **Setup** | Lädt Core, Doku, Typings; setzt Pfade. | `./tools/setup-minimal.sh` |
| **Build** | Baut dein Plugin und kann die Version erhöhen (patch/minor/major). | `./tools/build.sh patch` |
| **Git Push** | Committet, pusht und erstellt ein GitHub-Release für dein Plugin. | `./tools/gitpush.sh` |
| **TypeScript** | Kompiliert TypeScript zu JavaScript (optional Watch-Modus). | `./tools/typescript.sh` |
| **Unpack** | Entpackt ein Plugin-Paket nach `temp_edit/`. | `./tools/unpack.sh` |
| **Validierung** | Prüft dein Plugin auf Sicherheit und Store-Compliance. | `./tools/validate-plugin.sh` |
| **Hilfe** | Öffnet die Tools-Dokumentation. | `./tools/help.sh` |

Vollständige Beschreibung: **[tools/README.de.md](tools/README.de.md)**.

---

## Optional: TypeScript und WoltLab-Typings

Setup ausführen und **„d.ts klonen“** bestätigen (Standard: ja). [WoltLab d.ts](https://github.com/WoltLab/d.ts) landet in `woltlab-d-ts`. In `temp_edit/tsconfig.json` z. B.:

```json
"typeRoots": ["../../woltlab-d-ts"]
```

Details in [tools/README.de.md](tools/README.de.md).

---

## Optional: Editor-Workspace

`simple-woltlab-plugin-manager.code-workspace` ist ein optionales Multi-Root-Layout für VS Code (o. Ä.). **Nicht erforderlich** — alle Tools laufen im Terminal.

---

## Konfiguration

Einstellungen stehen in **`tools/.env`** (nicht in Git). Vorlage: **`tools/.env.example`**. Setup kann `tools/.env` anlegen oder aktualisieren.

---

## Links

- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- [WoltLab Docs (GitHub)](https://github.com/WoltLab/docs.woltlab.com)
- [WoltLab WCF (GitHub)](https://github.com/WoltLab/WCF)
- [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/)
