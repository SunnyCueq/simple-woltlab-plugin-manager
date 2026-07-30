# Tools – WoltLab Plugin Manager

**[English version](README.md)** · **[Handbuch / Dokumentation](https://benjarogit.github.io/simple-woltlab-plugin-manager/)**

---

## Überblick

Im Ordner `tools/` stecken die Skripte für den Alltag: Plugin bauen, prüfen, TypeScript kompilieren, entpacken, Setup und Git-Push. Einstieg meist über `./tools.sh` (Menü oder CLI). Ziel: von der Entwicklung bis zur **lokalen Qualitätsprüfung** und zum optionalen Store-Upload (manuell auf woltlab.com) — ohne Docker-Pflicht.

**Komplette Liste:** [docs/TOOLS-OVERVIEW.de.md](docs/TOOLS-OVERVIEW.de.md) — Kern, Build-Checks, optional, Docker, Intern.

**Plattformen:** Linux, macOS, Windows (WSL2 oder Git Bash). Unter Windows aus cmd/Explorer: `tools.cmd`. Details: [docs/CROSS-PLATFORM.de.md](docs/CROSS-PLATFORM.de.md).

**Hinweis:** `tools/woltlab-plugin-recovery/` ist ein separates Recovery-Hilfsmittel — nicht Teil des normalen Build-/Validate-Menüs.

---

## Hauptmenü (tools.sh)

```bash
./tools.sh          # interaktives Menü
./tools.sh help     # alle CLI-Befehle
```

| Taste | Name | Kurz |
|-------|------|------|
| 1 | Build / Update-Paket | `patch` · `minor` · `major` · `same` |
| 2 | TypeScript | kompilieren / Watch |
| 3 | Unpack | Paket → `temp_edit/` |
| F | Produktlinie | Basis + Add-ons (`family:*`) |
| 4 | Plugin validieren | lokale Qualitätschecks |
| 5 | Hilfe / Doku | diese Dokumentation |
| 6 | Git Push | Commit, Push, Release (Plugin) |
| 7 | Setup | Core, Docs, Typings, Pfade |
| 8 | Repo (origin) | Remote anzeigen/setzen |
| 9 | WoltLab-Version | Core/Docs-Info |
| L | Sprache DE/EN | Menüsprache |
| M | SWPM Release | `release-manager.sh` |
| 0 | Beenden | |

---

## Jedes Tool im Detail

### build.sh – Plugins bauen

**Was es macht:** Findet dein Plugin (Ordner mit `package.xml`), kompiliert bei Bedarf TypeScript und erzeugt ein installierbares `.tar.gz` unter `releases/<plugin-ordner>/`. Die Version in der `package.xml` kann erhöht werden.

**Wichtig:** Ein Plugin = ein Ordner. Wenn du denselben Ordner für ein *anderes* Plugin wiederverwendest, stoppt der Build (Schutz vor vermischten Dateien). Einmalig wechseln: `SWPM_ALLOW_SLOT_SWITCH=1 ./tools/build.sh …`. Details: [docs/PACKAGE-LAYOUT.de.md](docs/PACKAGE-LAYOUT.de.md).

**Wann:** Nach Code-Änderungen, wenn du ein Paket zum Testen oder Ausliefern brauchst.

**Befehl:**

```bash
./tools/build.sh [Ziel] [Versionstyp]
```

- `Ziel`: leer = erstes Plugin, Ordnername (z. B. `basis-plugin`), oder `all`
- `Versionstyp`: `patch` (Standard), `minor`, `major` oder `same` (Version unverändert)

**Beispiele:**

```bash
./tools/build.sh              # Erstes Plugin, Patch
./tools/build.sh patch
./tools/build.sh basis-plugin minor
./tools/build.sh all same
./tools/build.sh --json patch        # CI: JSON-Report
./tools/build.sh --dry-run patch     # Nur geplanten Inhalt zeigen
```

**Layouts:** Klassisch (`lib/`, `js/`, …), `files/` / `files_wcf/`, oder Style-Paket (`style/style.xml` → `style.tar`/`style.tgz`). Details: [docs/PACKAGE-LAYOUT.de.md](docs/PACKAGE-LAYOUT.de.md).

---

### gitpush.sh – Committen, pushen, Release (Plugins)

**Was es macht:** Findet geänderte Plugins, committed, pusht zu `origin`, setzt einen Versions-Tag und kann ein GitHub-Release anlegen. Release-Notes: Abschnitt aus `CHANGELOG.md`, plus Compare-Link zum vorherigen `v*`-Tag und eine kurze Commit-Liste (kein Auto-Changelog). Für **Plugin**-Releases — nicht für das Plugin-Manager-Repo selbst.

**Wann:** Wenn die Plugin-Änderungen fertig sind und auf GitHub sollen (Commit + Push + Tag + Release).

**Befehl:**

```bash
./tools/gitpush.sh [Ziel] [Commit-Nachricht]
```

- `Ziel`: leer = Auto-Erkennung, Plugin-Name oder `all`
- `Commit-Nachricht`: optional; sonst aus der Plugin-Version

**Voraussetzungen:** `origin` zeigt auf dein Plugin- oder Workspace-Repo (SSH oder Personal Access Token). Alternativ `GIT_REPO_URL` in `tools/.env` oder Menüoption 8.

> **Tipp:** Vom Repo-Root starten. Bekannte Referenzordner (z. B. `woltlab-docs`, `woltlab-github`) werden nicht mitcommitted.

---

### typescript.sh – TypeScript kompilieren

**Was es macht:** Kompiliert TypeScript (`.ts`) in deinen Plugin-Ordnern zu JavaScript (`.js`) und kann minimierte (`.min.js`) Dateien erzeugen. Optionaler Watch-Modus kompiliert bei Änderungen neu.

**Wann nutzen:** Wenn dein Plugin TypeScript nutzt; nach Bearbeitung von `.ts`-Dateien ausführen oder im Watch-Modus während der Entwicklung.

**Befehl:**

```bash
./tools/typescript.sh [watch]
```

- Ohne Argument: einmalige Kompilierung.
- `watch`: läuft weiter und kompiliert bei Änderungen (mit Ctrl+C beenden).

---

### unpack.sh – Plugin-Paket entpacken

**Was es macht:** Entpackt ein gebautes Plugin-Paket (z. B. `.tar.gz`) in den `temp_edit/`-Ordner des Plugins, damit du den Inhalt prüfen oder anpassen kannst. Frontend-`templates.tar` landet in `templates/` (WoltLab-Layout); ACP-Templates in `acptemplates/`. PIP-XMLs bleiben im Paket-Root.

**Wann nutzen:** Wenn du eine Paketdatei hast und sehen oder bearbeiten willst, was drin ist, ohne es in WoltLab zu installieren.

**Befehl:**

```bash
./tools/unpack.sh [Plugin] [Paketdatei]
```

- `Plugin`: Plugin-Ordnername (z. B. `basis-plugin`); kann leer bleiben für das erste erkannte Plugin.
- `Paketdatei`: optionaler Pfad zu einer bestimmten `.tar.gz`; ohne Angabe wird das neueste Paket unter `releases/<plugin>/` verwendet (Fallback: Plugin-Root).

---

### Toolkit-Smoke-Tests (SWPM selbst)

```bash
./tools/tests/run-tests.sh
```

Prüft `common.sh`-Helfer (Release-Pfade, Allowlist in `check_port_reachable`) und `check-package-pip-archives.py`. Läuft auch in CI über `.github/workflows/tools-tests.yml`.

### Optionale Checks (TypeScript, PHPStan, ruff)

Nur relevant, wenn die jeweilige Technik im Projekt vorkommt:

```bash
./tools/check-typescript.sh [--no-emit] [plugin]   # bei tsconfig / ts/ — Build + Validate
./tools/run-phpstan.sh [plugin]                    # nur mit phpstan.neon(.dist)
./tools.sh lint:python                             # ruff für Manager-tools/*.py (Skip ohne ruff)
./tools.sh phpstan [plugin]
```

- **TypeScript:** Wenn Quellen da sind und `tsc` fehlschlägt → Build/Validate abbrechen.
- **PHPStan / ruff:** Freiwillig; ohne Binary oder Config wird übersprungen.

### validate-plugin.sh – Sicherheit und Richtlinien

**Was es macht:** Prüft das Plugin **lokal** vor Release/Store: PHP/XML-Syntax, Sprachen (DE/EN), Templates, PIP-Quellen, Sicherheit (XSS/SQL-Heuristiken), Richtlinien und mehr. Lädt **nichts** hoch. Manche Punkte bleiben manuell — siehe [Plugin-Store-Checkliste](docs/PLUGIN-STORE-CHECKLIST.de.md).

**Wann:** Vor jedem Release oder vor dem echten Store-Upload.

```bash
./tools/validate-plugin.sh [--strict] [Plugin-Pfad]
```

**Check-Registry (Build):** Die Fail-/Warn-Checks für den **Build** stehen in `swpm-check-registry.txt` und laufen über `swpm-run-checks.sh`. Validate deckt dieselben Themen (und zusätzliche Store-Prüfungen) ab, ruft den Runner aber nicht 1:1 auf. Liste:

```bash
./tools/swpm-run-checks.sh --mode list
./tools/swpm-run-checks.sh --mode build [--strict-layout] /pfad/zu/temp_edit
```

**Einzelchecks (ohne vollständige Validierung):**

```bash
python3 tools/check-pip-sources.py --strict /pfad/zum/plugin
python3 tools/check-language-keys.py /pfad/zum/plugin
python3 tools/check-template-xss.py /pfad/zum/plugin
python3 tools/check-like-escaping.py /pfad/zum/plugin
python3 tools/check-language-categories.py /pfad/zum/plugin
python3 tools/check-style-package.py /pfad/zum/plugin
python3 tools/fix-template-xss-escaping.py /pfad/zum/plugin --dry-run
```

`check-pip-sources.py` bleibt außerhalb der Registry (braucht `package.xml`). Details: [docs/SECURITY-CHECKS.de.md](docs/SECURITY-CHECKS.de.md) · Sprachen: [docs/LANGUAGE-XML.de.md](docs/LANGUAGE-XML.de.md)

---

### Produktlinie (Basis + Zusatzpakete)

**Was es macht:** Mehrere zusammengehörige Pakete über `swpm-family.json` in der richtigen Reihenfolge prüfen und bauen (`family:check`, `family:build`, `family:validate`). Optional: Scaffold mit `family:init --scaffold`.

**Befehl:**

```bash
./tools.sh family:list
./tools.sh family:check
./tools.sh family:build patch
./tools/swpm-family.sh --manifest /pfad/swpm-family.json check
```

Details und Ablage: [docs/PRODUCT-LINE.de.md](docs/PRODUCT-LINE.de.md).

---

### prepare-acp-install.sh – Paket für ACP-Upload vorbereiten

**Was es macht:** Findet das neueste `.tar.gz` unter `releases/<plugin>/` (Fallback: Plugin-Root), kopiert es in den lokalen Docker-Webserver (`woltlab-web`) und gibt die exakten Schritte für den manuellen ACP-Upload aus.

**Wann nutzen:** Nach `./tools/build.sh`, bevor du das Paket in WoltLab testest. Paketdatei im ACP manuell im Installationsdialog auswählen.

**Befehl:**

```bash
./tools/prepare-acp-install.sh [Plugin]
```

Details: [docs/ACP-PACKAGE-INSTALL.de.md](docs/ACP-PACKAGE-INSTALL.de.md)

---

### setup-minimal.sh – Einmaliges Setup

**Was es macht:** Führt dich durch ein minimales Setup: WoltLab Core herunterladen (oder Pfad setzen), WoltLab-Doku und/oder WCF von GitHub klonen, WoltLab-d.ts-Typings für TypeScript klonen, optionalen Pfad zu einer lokalen WoltLab-Installation setzen. Einstellungen werden in `tools/.env` geschrieben; Workspace-Datei und Intelephense-Pfade können angepasst werden.

**Wann nutzen:** Einmal nach dem Klonen des Repos oder wenn du Core, Doku, Typings oder den lokalen Installationspfad hinzufügen/ändern willst.

**Befehl:**

```bash
./tools/setup-minimal.sh
```

Vom Repo-Root aus ausführen. Du wirst für jeden Schritt gefragt; einzelne Schritte kannst du überspringen.

---

### help.sh – Dokumentation öffnen

**Was es macht:** Öffnet oder zeigt die Tools-Dokumentation (dieses README und verwandte Docs) an, damit du sie im Terminal oder im Editor lesen kannst.

**Wann nutzen:** Wenn du eine kurze Auffrischung der Befehle brauchst oder die vollständigen Tool-Beschreibungen lesen willst.

**Befehl:**

```bash
./tools/help.sh
```

---

### download-woltlab-core.sh – WoltLab Core herunterladen

**Was es macht:** Lädt das WoltLab-Suite-Installationspaket von der offiziellen Seite und legt es (oder die entpackten Core-Dateien) dort ab, wo das übrige Setup sie erwartet (z. B. für lokalen Server oder DDEV).

**Wann nutzen:** Wenn du Core getrennt vom vollständigen Setup brauchst (z. B. du hast das Setup schon ausgeführt und den Download übersprungen). Ansonsten kann das Haupt-Setup (`setup-minimal.sh`) das für dich erledigen.

**Befehl:** Vom Repo-Root aus ausführen; das Skript oder das Haupt-README nennt den genauen Befehl (z. B. `./tools/download-woltlab-core.sh`). Du musst im WoltLab-Kundenbereich eingeloggt sein für den Download.

---

## TypeScript-Typings (d.ts)

Die Typings stammen vom **offiziellen WoltLab-d.ts-Repository** auf GitHub. Beim Setup kannst du es nach `woltlab-d-ts` klonen, damit deine API-Nutzung mit WoltLab abgestimmt bleibt. Zum Aktualisieren später: `git pull` im Ordner `woltlab-d-ts` ausführen.

Wenn du das Setup ausgeführt und das Klonen der Typings gewählt hast, liegen sie im Ordner `woltlab-d-ts` im Workspace-Root. So nutzt du sie in einem Plugin:

1. In der `temp_edit/tsconfig.json` deines Plugins (oder dem Ordner mit deinen `.ts`-Dateien) einen Pfad zu den Typings eintragen. Z. B. bei einem Plugin unter `basis-plugin/temp_edit/`:

```json
"typeRoots": ["../../woltlab-d-ts"]
```

2. Den Pfad anpassen, wenn dein Plugin anders verschachtelt ist (z. B. `mein-plugin/extracted_plugin/xyz/temp_edit/` könnte `../../../../woltlab-d-ts` verwenden).

Danach können Editor und TypeScript-Compiler die WoltLab-API-Typen nutzen. Siehe [WoltLab d.ts](https://github.com/WoltLab/d.ts) für das Upstream-Projekt.

---

## Konfiguration

- **`tools/.env`** — Hauptkonfigurationsdatei. Sie wird nicht in Git eingecheckt. Hier kannst du setzen:
  - Pfad zu deiner lokalen WoltLab-Installation
  - GitHub-Repo-URL für Push (`GIT_REPO_URL`)
  - WoltLab-d.ts-Klon-URL oder -Pfad (falls nötig)
  - Weitere von den Skripten genutzte Optionen

- **`tools/.env.example`** — Vorlage mit den verfügbaren Variablen. Nach `tools/.env` kopieren und Werte eintragen. Das Setup-Skript kann `tools/.env` für dich anlegen oder aktualisieren.

---

## Weitere Dokumentation

Vollständiger Index / Navigation: **[MkDocs-Site](https://benjarogit.github.io/simple-woltlab-plugin-manager/)** (Quelle: `tools/docs/`)

| Thema | Link (Repo) |
|-------|------|
| Tools-Übersicht | [TOOLS-OVERVIEW.de.md](docs/TOOLS-OVERVIEW.de.md) |
| Plattformen | [CROSS-PLATFORM.de.md](docs/CROSS-PLATFORM.de.md) |
| Paket-Layout / Style | [PACKAGE-LAYOUT.de.md](docs/PACKAGE-LAYOUT.de.md) |
| Produktlinie | [PRODUCT-LINE.de.md](docs/PRODUCT-LINE.de.md) |
| Store-Checkliste | [PLUGIN-STORE-CHECKLIST.de.md](docs/PLUGIN-STORE-CHECKLIST.de.md) |
| Security-Checks | [SECURITY-CHECKS.de.md](docs/SECURITY-CHECKS.de.md) |
| Language-XML | [LANGUAGE-XML.de.md](docs/LANGUAGE-XML.de.md) |
| ACP-Install (Docker) | [ACP-PACKAGE-INSTALL.de.md](docs/ACP-PACKAGE-INSTALL.de.md) |

---

## Sonstiges

- **Logging:** Zentrales Debug-Log unter `tools/docs/logs/woltlab-dev-debug.log`. Siehe [LOGGING.de.md](docs/LOGGING.de.md) / [LOGGING.en.md](docs/LOGGING.en.md).
- **Sprache:** Die Menüsprache lässt sich über `WOLTLAB_LANG` in `tools/.env` oder die Menüoption „Sprache wechseln“ (L) auf DE oder EN stellen. Übersetzungen liegen in `tools/language/de.txt` und `tools/language/en.txt` (Schlüssel=Wert). Die Funktion `tr "key"` in `common.sh` liefert den Text für die aktuelle Sprache; Skripte können schrittweise darauf umgestellt werden.
- **Repo-Root:** Alle Befehle setzen voraus, dass du dich im Repository-Root (der Ordner, der `tools/` enthält) befindest, sofern nicht anders angegeben.
