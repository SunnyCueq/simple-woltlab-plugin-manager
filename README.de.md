<p align="center">
  <img src="docs/assets/swpm-logo.jpg" alt="SWPM — Simple WoltLab Plugin Manager" width="320">
</p>

<h1 align="center">Simple WoltLab Plugin Manager</h1>

<p align="center">
  <strong>CLI-Workbench für WoltLab-Plugins auf Linux, macOS und Windows — aus der Praxis entstanden, mit Checks und Validierung im Terminal.</strong>
</p>

<p align="center">
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/releases/latest"><img src="https://img.shields.io/github/v/release/benjarogit/simple-woltlab-plugin-manager?style=flat-square&label=Release" alt="Aktuelles Release"></a>
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/LICENSE"><img src="https://img.shields.io/badge/Lizenz-MIT-blue?style=flat-square" alt="MIT-Lizenz"></a>
  <img src="https://img.shields.io/badge/Plattform-Linux%20%7C%20macOS%20%7C%20WSL2%20%7C%20Git%20Bash-2d6a4f?style=flat-square" alt="Plattformen">
  <img src="https://img.shields.io/badge/WoltLab%20Suite-6.2%2B-1a5fb4?style=flat-square" alt="WoltLab Suite 6.2+">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Bash-4EAA25?style=flat-square&logo=gnu-bash&logoColor=white" alt="Bash">
  <img src="https://img.shields.io/badge/Python-3776AB?style=flat-square&logo=python&logoColor=white" alt="Python">
  <img src="https://img.shields.io/badge/Shell_Skripte-121011?style=flat-square&logo=gnubash&logoColor=white" alt="Shell">
  <img src="https://img.shields.io/badge/TypeScript-3178C6?style=flat-square&logo=typescript&logoColor=white" alt="TypeScript (optional)">
  <img src="https://img.shields.io/badge/Docker-optional-2496ED?style=flat-square&logo=docker&logoColor=white" alt="Docker optional">
</p>

<p align="center"><a href="README.md"><strong>English version</strong></a></p>

---

## Über das Projekt

**SWPM** kommt aus dem Alltag: Beim Bau eigener WoltLab-Suite-Plugins wiederholen sich dieselben Schritte — Paket schnüren (`package.xml`), Checks laufen lassen, Store-Regeln und Übersetzungen prüfen. SWPM nimmt genau diesen Workflow ins Terminal-Menü, damit der Fokus beim Plugin bleibt.

Heute ist es ein **generisches** CLI-Toolkit für Setup, Build, Validierung und GitHub-Release — ohne Bindung an ein bestimmtes Produkt-Plugin. Orientiert an den offiziellen [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/).

---

## Funktionen

- **Interaktives Menü** — `./tools.sh` für Build, Validierung, TypeScript, Git-Push und Setup.
- **Paket-Build** — Installierbare `.tar.gz`-Archive mit patch/minor/major/same; [Quellenlayouts](tools/docs/PACKAGE-LAYOUT.de.md) (`files/`, `files_wcf/`, `templates/`, Style-Pakete als `style.tar`/`style.tgz`, `--json` für CI).
- **Produktlinie** — Optional Basis + Zusatzpakete über `swpm-family.json` (`family:build` / `family:validate`); siehe [PRODUCT-LINE.de.md](tools/docs/PRODUCT-LINE.de.md).
- **Validierung** — PHP/XML, XSS/SQL-Heuristiken, DE/EN-Sprachkeys, PIP-Quellen, Store-Checkliste; TypeScript-Check wenn Quellen vorhanden; optional PHPStan (`phpstan.neon`) und `./tools.sh lint:python` (ruff).
- **Workspace-Setup** — Optional: WoltLab Core, offizielle Doku, WCF-Quellcode und [WoltLab d.ts](https://github.com/WoltLab/d.ts).
- **Release** — Commit, Push, Tag und GitHub-Release über `gitpush.sh`.
- **Plattformen** — Linux, macOS, Windows (WSL2 oder Git Bash); unter Windows `tools.cmd`.
- **Docker optional** — Helfer für lokale ACP-Installation und Berechtigungen; die **Kern-Tools laufen ohne Docker**.

---

## Tech Stack

| Ebene | Technologie |
|-------|-------------|
| Einstieg & Orchestrierung | Bash (`tools.sh`, `tools/*.sh`) |
| Validierung | Python 3 (`check-*.py`, `validate-plugin.sh`) |
| Plugin-Assets (optional) | Node.js / TypeScript im Plugin-Projekt |
| Paketierung | `tar`, `package.xml`, WoltLab-PIP-Archive |
| Lokaler Test (optional) | Docker, DDEV |

Pflicht für den Kern: **Bash + Git + tar**. Für die Validierung ist **Python 3** stark empfohlen.

---

## Installation

### Voraussetzungen

| Anforderung | Pflicht | Hinweis |
|-------------|---------|---------|
| Bash | Ja | WSL2 oder [Git for Windows](https://git-scm.com/download/win) |
| Git | Ja | Klonen und Release |
| tar | Ja | Paket-Archive |
| Python 3 | Empfohlen | Validierungsskripte |
| Node.js | Optional | Nur bei TypeScript im Plugin |

### Schritte

**1. Repository klonen**

```bash
git clone https://github.com/benjarogit/simple-woltlab-plugin-manager.git
cd simple-woltlab-plugin-manager
```

**2. Toolkit starten**

```bash
./tools.sh
```

Unter Windows ohne Unix-Shell:

```cmd
tools.cmd
```

**3. (Empfohlen) Setup für Core, Doku oder Typings**

```bash
./tools.sh setup
```

**4. Lokale Pfade konfigurieren (optional)**

```bash
cp tools/.env.example tools/.env
# tools/.env bearbeiten — WoltLab-Pfad, GIT_REPO_URL, …
```

Plugin-Ordner mit `package.xml` anlegen (z. B. `basis-plugin/`) oder bestehendes Paket nach `temp_edit/` entpacken.

---

## Verwendung

### Interaktives Menü

```bash
./tools.sh
```

Beispiel (gekürzt):

```text
╔══════════════════════════════════════════════════════════╗
║              Simple WoltLab Plugin Manager               ║
╚══════════════════════════════════════════════════════════╝

  Plugins
  ✓ Mein Plugin    v1.0.0 [basis-plugin]

  ENTWICKLUNG
  1   Build / Update-Paket
  2   TypeScript
  3   Unpack
  F   Produktlinie
  4   Plugin validieren
  …
```

### Häufige Befehle

```bash
# Build mit Patch-Versionserhöhung
./tools.sh build

# Build ohne Versionsänderung (Entwicklung)
./tools.sh build:same

# Validierung vor Store-Einreichung
./tools.sh validate basis-plugin

# Produktlinie (Basis + Add-ons)
./tools.sh family:check
./tools.sh family:build patch

# TypeScript kompilieren
./tools.sh typescript

# Optional: PHPStan (nur mit phpstan.neon) / ruff für Manager-Python
./tools.sh phpstan basis-plugin
./tools.sh lint:python

# Commit, Push, GitHub-Release
./tools.sh push

# CLI-Übersicht
./tools.sh help
```

### CI-Build (JSON-Report)

```bash
./tools/build.sh --json patch
```

### Ordnerstruktur

| Ordner | Zweck |
|--------|---------|
| `basis-plugin/` | Dein Haupt-Plugin (Beispielname) |
| `tools/` | Alle Skripte |
| `woltlab-core/` | Core nach Setup |
| `woltlab-d-ts/` | TypeScript-Typings nach Setup |

**Plugin-Quellenlayout:** Frontend-Templates gehören nach `templates/` (daraus wird `templates.tar`). ACP-Templates bleiben in `acptemplates/`. PIP-XMLs wie `option.xml` und `page.xml` bleiben im Paket-Root. Root-`*.tpl` wird weiter gepackt, aber mit Warnung; mit `--strict-layout` bzw. `validate-plugin.sh --strict` wird daraus ein Fehler. Style-Pakete: Quellen unter `style/` → Archivname laut `package.xml`. Details: [PACKAGE-LAYOUT.de.md](tools/docs/PACKAGE-LAYOUT.de.md).

Tools-Referenz: **[tools/README.de.md](tools/README.de.md)** · Anleitungen: **[tools/docs/README.de.md](tools/docs/README.de.md)**

---

## Mitwirken

Beiträge sind willkommen. SWPM bleibt **generisch** — keine fest verdrahteten Pfade oder Skripte für ein einzelnes Produkt-Plugin.

1. Repository forken und Feature-Branch anlegen.
2. Änderungen fokussiert halten; bestehenden Stil in Bash/Python beibehalten.
3. Bei nutzersichtbaren Änderungen **Deutsch und Englisch** dokumentieren.
4. Pull Request mit klarer Beschreibung (was / warum).

Details: **[CONTRIBUTING.de.md](CONTRIBUTING.de.md)** (EN: [CONTRIBUTING.md](CONTRIBUTING.md))

---

## Lizenz

Dieses Projekt steht unter der **[MIT-Lizenz](LICENSE)**.

Du darfst SWPM frei nutzen, ändern und weitergeben. Bitte den Copyright-Hinweis belassen. Mitwirken ist willkommen; die Software als eigenes proprietäres Produkt ohne Namensnennung auszugeben, entspricht nicht der Idee dieser Lizenz.

---

## Links

- [Aktuelles Release](https://github.com/benjarogit/simple-woltlab-plugin-manager/releases/latest)
- [Changelog](CHANGELOG.md)
- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/)
