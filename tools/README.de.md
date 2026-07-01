# Tools – WoltLab Plugin Manager

**[English version](README.md)**

---

## Überblick

Der Ordner `tools/` enthält alle Skripte für die **WoltLab-Plugin-Entwicklung**: Plugins bauen, zu Git pushen, Releases erstellen, TypeScript kompilieren, Pakete entpacken, Code prüfen und ein einmaliges Setup. Die Tools unterstützen den Weg von der Entwicklung bis zum **WoltLab Plugin-Store** und beachten dabei WoltLab- und Store-Anforderungen. Alles wird über das Hauptmenü (`tools.sh`) oder durch direkten Aufruf der Skripte gesteuert. Diese Seite beschreibt jedes Tool, damit du weißt, wann und wie du es nutzt.

**Plattformen:** Linux, macOS, **Windows (WSL2)** und **Windows (Git Bash)**. Unter Windows aus cmd/Explorer: `tools.cmd`. Reines cmd/PowerShell wird nicht unterstützt. Details: [docs/CROSS-PLATFORM.de.md](docs/CROSS-PLATFORM.de.md).

---

## Hauptmenü (tools.sh)

**Was es macht:** Startet das interaktive Menü. Vom Repo-Root aus kannst du `./tools.sh` oder `./tools/tools.sh` ausführen. Das Menü zeigt den aktuellen Zustand (z. B. erkannte Plugins) und nummerierte Optionen.

**Optionen:**

| Option | Name | Kurzbeschreibung |
|--------|------|------------------|
| 1 | Build | Plugin(s) bauen und Version erhöhen (patch/minor/major). |
| 2 | Git Push | Committen, pushen und GitHub-Release für dein(e) Plugin(s) erstellen. |
| 3 | TypeScript | TypeScript nach JavaScript kompilieren (normal oder Watch-Modus). |
| 4 | Unpack | Plugin-Paket nach `temp_edit/` entpacken. |
| 5 | Hilfe & Dokumentation | Diese Doku öffnen. |
| 6 | Plugin-Validierung | Sicherheits- und Store-Compliance-Prüfungen ausführen. |
| 7 | Setup / Vorbereitung | Einmaliges Setup (Core, Doku, Typings, Pfade). |
| 8 | Repo | Git-Repository (origin) für Push anzeigen oder ändern. |
| 0 | Beenden | Menü verlassen. |

Falls `manager-push.sh` existiert (nur für Maintainer), erscheint Option 9 zum Pushen des Plugin-Managers selbst.

---

## Jedes Tool im Detail

### build.sh – Plugins bauen

**Was es macht:** Findet deine Plugin(s) (Ordner mit `package.xml`), kompiliert bei Bedarf TypeScript und erstellt ein installierbares Plugin-Archiv (z. B. `.tar.gz`). Es kann außerdem die Version in der `package.xml` erhöhen (patch, minor oder major).

**Wann nutzen:** Immer wenn du Plugin-Code geändert hast und ein installierbares Paket zum Testen in WoltLab oder zum Ausliefern brauchst.

**Befehl:**

```bash
./tools/build.sh [Ziel] [Versionstyp]
```

- `Ziel`: leer lassen für „erstes Plugin“, oder Plugin-Ordnername (z. B. `basis-plugin`), oder `all` für alle Plugins.
- `Versionstyp`: `patch` (Standard), `minor` oder `major`.

**Beispiele:**

```bash
./tools/build.sh              # Erstes Plugin, Patch-Version
./tools/build.sh patch        # Dasselbe
./tools/build.sh basis-plugin minor
./tools/build.sh all patch
```

---

### gitpush.sh – Committen, pushen, Release (Plugins)

**Was es macht:** Erkennt, welche Plugin(s) geändert wurden, committet sie, pusht zum konfigurierten Git-Remote (origin), erstellt einen Versions-Tag und optional ein GitHub-Release mit Notizen. Gilt für **Plugin-**Releases, nicht für das Plugin-Manager-Repo selbst.

**Wann nutzen:** Wenn du mit deinen Plugin-Änderungen zufrieden bist und sie auf GitHub veröffentlichen willst (Commit + Push + Tag + Release).

**Befehl:**

```bash
./tools/gitpush.sh [Ziel] [Commit-Nachricht]
```

- `Ziel`: leer für Auto-Erkennung, oder Plugin-Name, oder `all`.
- `Commit-Nachricht`: optional; sonst wird eine aus der Plugin-Version erzeugt.

**Voraussetzungen:** Das Git-Remote `origin` muss auf dein Plugin- oder Workspace-Repo zeigen. Für GitHub SSH oder Personal Access Token verwenden. Du kannst `GIT_REPO_URL` in `tools/.env` setzen oder die Menüoption 8 nutzen, um das Repo zu setzen.

> **Tipp:** Skript vom Repo-Root aus starten. Es nutzt dieselbe Ausschlussliste wie die übrigen Tools (z. B. Inhalt von `woltlab-docs`, `woltlab-github` oder `tools/woltlab-dev/public` wird nicht committed).

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

**Was es macht:** Entpackt ein gebautes Plugin-Paket (z. B. `.tar.gz`) in den `temp_edit/`-Ordner des Plugins, damit du den Inhalt prüfen oder anpassen kannst.

**Wann nutzen:** Wenn du eine Paketdatei hast und sehen oder bearbeiten willst, was drin ist, ohne es in WoltLab zu installieren.

**Befehl:**

```bash
./tools/unpack.sh [Plugin] [Paketdatei]
```

- `Plugin`: Plugin-Ordnername (z. B. `basis-plugin`); kann leer bleiben für das erste erkannte Plugin.
- `Paketdatei`: optionaler Pfad zu einer bestimmten `.tar.gz`; ohne Angabe wird das neueste Paket im Plugin-Ordner verwendet.

---

### validate-plugin.sh – Sicherheit und Store-Compliance

**Was es macht:** Prüft dein Plugin vor Release oder Store-Einreichung auf typische Probleme. Geprüft werden: **PHP- und XML-Syntax**; **Übersetzungen** (DE und EN vorhanden und konsistent); **PIP-Quellen** (DevTools-Parität: sync-fähig vs. nur Paket-Update); **Plugin-Sprach-Keys** im Code vs. `language/*.xml` mit **Datei:Zeile**; **Mindestversion WoltLab**; dass keine externen Paket-Server genutzt werden; **Sicherheit** (z. B. SQL-Injection, XSS); **Debug- und Entwicklungs-Code**, der nicht ausgeliefert werden darf; sowie **Cloud-/Kompatibilitäts-** und weitere Store-Regeln. Die Prüfungen sind an die [Plugin-Store-Checkliste](docs/PLUGIN-STORE-CHECKLIST.md) angelehnt, die auch manuelle Schritte aufführt, die das Skript nicht abdeckt.

**Wann nutzen:** Vor dem Release oder der Einreichung im Store, um Probleme früh zu finden.

**Befehl:**

```bash
./tools/validate-plugin.sh [Plugin-Pfad]
```

- `Plugin-Pfad`: optional; Plugin-Ordner oder Pfad. Ohne Angabe wird das aktuelle Verzeichnis oder das erste erkannte Plugin verwendet.

Ergebnisse und Details erscheinen im Terminal; Logs können unter `/tmp/` geschrieben werden (siehe Skript-Ausgabe).

**Einzelchecks (ohne vollständige Validierung):**

```bash
python3 tools/check-pip-sources.py --strict /pfad/zum/plugin
python3 tools/check-language-keys.py /pfad/zum/plugin
python3 tools/check-template-xss.py /pfad/zum/plugin
python3 tools/check-like-escaping.py /pfad/zum/plugin
python3 tools/check-language-categories.py /pfad/zum/plugin
python3 tools/fix-template-xss-escaping.py /pfad/zum/plugin --dry-run
```

`check-pip-sources.py` spiegelt WoltLab-DevTools-PIP-Ziele offline (ohne ACP-Abgleich). `check-language-keys.py` meldet fehlende **App-Keys** (Präfix aus `package.xml`) mit Fundstelle; Core-`wcf.*`-Texte werden ignoriert.

Details zu Heuristiken und False Positives: [docs/SECURITY-CHECKS.de.md](docs/SECURITY-CHECKS.de.md)  
Sprach-XML (Kategorie/Item): [docs/LANGUAGE-XML.de.md](docs/LANGUAGE-XML.de.md)

---

### prepare-acp-install.sh – Paket für ACP-Upload vorbereiten

**Was es macht:** Findet das neueste `.tar.gz` im Plugin-Ordner, kopiert es in den lokalen Docker-Webserver (`woltlab-web`) und gibt die exakten Schritte für den manuellen ACP-Upload aus.

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

Dokumente in `tools/docs/`:

- **[docs/CROSS-PLATFORM.de.md](docs/CROSS-PLATFORM.de.md)** — Linux, macOS, Windows (WSL2, Git Bash), `tools.cmd`.
- **[docs/PLUGIN-STORE-CHECKLIST.md](docs/PLUGIN-STORE-CHECKLIST.md)** — Checkliste vor der Einreichung eines Plugins im WoltLab-Store: Was `validate-plugin.sh` abdeckt und was du zusätzlich manuell prüfen solltest. Englische Version: [docs/PLUGIN-STORE-CHECKLIST.en.md](docs/PLUGIN-STORE-CHECKLIST.en.md).
- **[docs/SECURITY-CHECKS.de.md](docs/SECURITY-CHECKS.de.md)** — XSS/LIKE/SQL-Heuristiken der Validierung (WoltLab 6.2.x).
- **[docs/LANGUAGE-XML.de.md](docs/LANGUAGE-XML.de.md)** — Sprach-PIP: Item/Kategorie-Zuordnung (verhindert ACP-Update-Fehler).

---

## Sonstiges

- **Logging:** Es wird eine zentrale Debug-Log-Datei verwendet (`tools/docs/logs/woltlab-dev-debug.log`). Level und Pfade sind über `DEBUG_LEVEL`, `DEBUG_LOG_FILE` konfigurierbar. Konvention und Level stehen in [tools/docs/LOGGING.md](docs/LOGGING.md).
- **Sprache:** Die Menüsprache lässt sich über `WOLTLAB_LANG` in `tools/.env` oder die Menüoption „Sprache wechseln“ (L) auf DE oder EN stellen. Übersetzungen liegen in `tools/language/de.txt` und `tools/language/en.txt` (Schlüssel=Wert). Die Funktion `tr "key"` in `common.sh` liefert den Text für die aktuelle Sprache; Skripte können schrittweise darauf umgestellt werden.
- **Repo-Root:** Alle Befehle setzen voraus, dass du dich im Repository-Root (der Ordner, der `tools/` enthält) befindest, sofern nicht anders angegeben.
