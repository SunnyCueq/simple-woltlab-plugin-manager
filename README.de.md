<p align="center">
  <img src="docs/assets/swpm-logo.jpg" alt="SWPM — Simple WoltLab Plugin Manager" width="360">
</p>

<h1 align="center">Simple WoltLab Plugin Manager</h1>

<p align="center">
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager"><img src="https://img.shields.io/github/stars/benjarogit/simple-woltlab-plugin-manager?style=flat-square" alt="GitHub stars"></a>
  <img src="https://img.shields.io/badge/platform-Linux%20%7C%20macOS%20%7C%20WSL2%20%7C%20Git%20Bash-2d6a4f?style=flat-square" alt="Plattformen">
  <img src="https://img.shields.io/badge/WoltLab%20Suite-6.x-1a5fb4?style=flat-square" alt="WoltLab Suite">
  <img src="https://img.shields.io/badge/Doku-DE%20%7C%20EN-555?style=flat-square" alt="Zweisprachige Doku">
</p>

<p align="center"><strong><a href="README.md">English version</a></strong></p>

---

## Inhaltsverzeichnis

- [Überblick](#überblick)
- [Funktionen](#funktionen)
- [Voraussetzungen](#voraussetzungen)
- [Schnellstart](#schnellstart)
- [Plattformen](#plattformen)
- [Ordnerstruktur](#ordnerstruktur)
- [Tools im Überblick](#tools-im-überblick)
- [Dokumentation](#dokumentation)
- [Konfiguration](#konfiguration)
- [Externe Links](#externe-links)

---

## Überblick

Der **Simple WoltLab Plugin Manager (SWPM)** ist ein plattformübergreifendes Kommandozeilen-Toolkit für den kompletten **WoltLab Suite**-Plugin-Lebenszyklus: Setup, Entwicklung, Build, Validierung und Release. Ein zentrales Textmenü steuert alles im Terminal.

## Funktionen

- **Umgebungs-Setup** — WoltLab Core, offizielle Doku und WCF-Quellcode, TypeScript-Typings (d.ts), Pfade zur lokalen Installation.
- **Entwicklung** — TypeScript-Kompilierung (inkl. Watch), Pakete entpacken, zentrales Debug-Log.
- **Build** — Verteilbare `.tar.gz`-Pakete mit Versionserhöhung; [wspackager-Parität](tools/docs/WSPACKAGER-PARITY.de.md) (`files/`, `files_wcf/`, `--json`).
- **Qualitätssicherung** — Sicherheit (SQL, XSS), Übersetzungen (DE/EN), offline DevTools-Parität (PIP-Quellen, Sprach-Keys mit Datei:Zeile), Minversion, WoltLab-Cloud, Store-Vorgaben.
- **Release** — Git-Commit, Push, Tags, GitHub-Release inkl. Assets.
- **Optional** — Docker-Helfer für lokale ACP-Tests; DDEV-Anbindung.

Ausgerichtet an den [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/). Typings von [WoltLab d.ts](https://github.com/WoltLab/d.ts).

## Voraussetzungen

- **Bash** — Linux, macOS, **Windows WSL2** oder **Git Bash** ([Details](tools/docs/CROSS-PLATFORM.de.md))
- **Git** und **tar**
- **Python 3** (empfohlen) — Validierungsskripte
- **Node.js** (optional) — TypeScript im Plugin

Kein Vorwissen nötig. Menü und [Tools-Dokumentation](tools/README.de.md) führen Schritt für Schritt.

## Schnellstart

1. Repo klonen, Terminal im Root öffnen (Ordner mit `tools.sh`).

2. Toolkit starten:
   ```bash
   ./tools.sh
   ```
   Windows ohne Unix-Shell: **`tools.cmd`** ([Git for Windows](https://git-scm.com/download/win)).

3. Interaktives Menü nutzen (Build, Git Push, Validierung, …). CLI: `./tools.sh help`. **`./tools.sh setup`** für Core, Doku, Typings und Pfade — nicht beim ersten Start.

## Plattformen

| Plattform | Befehl |
|-----------|--------|
| Linux / macOS | `./tools.sh` |
| Windows (WSL2) | `./tools.sh` in WSL |
| Windows (Git Bash) | `./tools.sh` oder `tools.cmd` |

Details: **[tools/docs/CROSS-PLATFORM.de.md](tools/docs/CROSS-PLATFORM.de.md)**

## Ordnerstruktur

| Ordner | Zweck |
|--------|--------|
| `basis-plugin` | Haupt- oder Basis-Plugin |
| `mein-plugin` | Weitere Plugin-Projekte |
| `plugins-integrieren` | Externe/Referenz-Plugins im Workspace |
| `woltlab-core` | WoltLab-Core nach Setup-Download |
| `woltlab-docs` | WoltLab-Doku (optional) |
| `woltlab-github` | WCF-Quellcode (optional) |
| `woltlab-d-ts` | TypeScript-Typings für dein Plugin |
| `tools` | Skripte und Setup |

## Tools im Überblick

| Tool | Zweck | Befehl |
|------|--------|--------|
| **Hauptmenü** | Menü + CLI | `./tools.sh` |
| **Setup** | Core, Doku, Typings, Pfade | `./tools/setup-minimal.sh` |
| **Build** | Paket + Version | `./tools/build.sh patch` |
| **Git Push** | Commit, Push, GitHub-Release | `./tools/gitpush.sh` |
| **TypeScript** | TS → JS | `./tools/typescript.sh` |
| **Unpack** | Paket nach `temp_edit/` | `./tools/unpack.sh` |
| **Validierung** | Sicherheit und Store | `./tools/validate-plugin.sh` |
| **Hilfe** | Tools-Doku öffnen | `./tools/help.sh` |

Details: **[tools/README.de.md](tools/README.de.md)**

## Dokumentation

| Ressource | English | Deutsch |
|-----------|---------|---------|
| Tools-Referenz | [tools/README.md](tools/README.md) | [tools/README.de.md](tools/README.de.md) |
| Alle Anleitungen | [tools/docs/README.md](tools/docs/README.md) | [tools/docs/README.de.md](tools/docs/README.de.md) |
| Mitwirken | [CONTRIBUTING.md](CONTRIBUTING.md) | [CONTRIBUTING.de.md](CONTRIBUTING.de.md) |

## Konfiguration

Einstellungen (WoltLab-Pfad, GitHub-URL, …) in **`tools/.env`** (nicht im Git). Vorlage: **`tools/.env.example`**. Setup kann die Datei anlegen.

### Optional: TypeScript-Typings

Nach Setup mit **„d.ts klonen“** im Plugin-`tsconfig.json`:

```json
"typeRoots": ["../../woltlab-d-ts"]
```

### Optional: Editor-Workspace

`simple-woltlab-plugin-manager.code-workspace` — optionales VS-Code-Multiroot-Layout. **Nicht Pflicht** — alles läuft im Terminal.

## Externe Links

- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- [WoltLab Docs (GitHub)](https://github.com/WoltLab/docs.woltlab.com)
- [WoltLab WCF (GitHub)](https://github.com/WoltLab/WCF)
- [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/)
