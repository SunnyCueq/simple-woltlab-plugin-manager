# 🛠️ WoltLab Development Tools - Komplette Anleitung

## 📖 Was ist das?

Die **WoltLab Development Tools** sind eine Sammlung von automatisierten Skripten, die die Entwicklung von WoltLab Suite Plugins erheblich vereinfachen. Statt manuell viele Schritte durchzuführen, kannst du alles über ein zentrales Menü steuern.

### 🎯 Was kann es?

- ✅ **Plugins automatisch bauen** - Erstellt Plugin-Archive (.tar Dateien) für die Installation
- ✅ **Versionen automatisch erhöhen** - Patch, Minor oder Major Versionen
- ✅ **Git-Operationen automatisieren** - Commit, Push und GitHub Releases erstellen
- ✅ **TypeScript kompilieren** - Konvertiert TypeScript zu JavaScript
- ✅ **DDEV verwalten** - Startet und verwaltet die lokale Entwicklungsumgebung
- ✅ **Snapshots erstellen/wiederherstellen** - Schnelle Backups der kompletten Installation
- ✅ **Zugangsdaten verwalten** - Zentrale Verwaltung aller Passwörter und Einstellungen
- ✅ **Dockge verwalten** - Container-Management für Docker (moderne Alternative zu Portainer)
- ✅ **HeidiSQL konfigurieren** - Automatische Datenbank-Verbindung einrichten
- ✅ **Plugin Validierung** - Security-Checks & Plugin Store Compliance prüfen

---

## 📚 Installation & Systemvoraussetzungen

### 🎯 Für Einsteiger: Was wird benötigt?

Bevor du mit den WoltLab Development Tools arbeiten kannst, müssen einige Tools installiert sein. **Keine Sorge** - das Setup-Skript (Option 6) macht das meiste automatisch! Aber falls du es manuell machen möchtest oder Probleme hast, findest du hier detaillierte Anleitungen.

### 📋 Systemvoraussetzungen (WoltLab Suite)

**WoltLab Suite benötigt:**
- **PHP:** Version 8.1.2 oder höher (64 Bit)
- **MySQL:** Version 8.0.30 oder höher ODER MariaDB 10.5.15 oder höher
- **PHP-Erweiterungen:** gd oder imagick (mit WebP-Support), pdo_mysql, mbstring, und viele weitere
- **TLS-Verschlüsselung:** Gültiges Zertifikat
- **Ausgehende HTTPS-Verbindungen:** Für Updates und Paket-Server

**📖 Offizielle Dokumentation:**
- **Systemvoraussetzungen:** https://manual.woltlab.com/de/requirements/
- **Installation:** https://manual.woltlab.com/de/installation/

**💡 Tipp:** Wenn du DDEV verwendest (empfohlen), werden PHP und MySQL automatisch in Docker-Containern bereitgestellt. Du musst sie nicht separat installieren!

---

### 🐳 DDEV - Lokale Entwicklungsumgebung

**Was ist DDEV?**
DDEV ist eine lokale Entwicklungsumgebung, die eine komplette WoltLab Suite auf deinem Computer simuliert - ohne dass du einen Server installieren musst. Es verwendet Docker-Container, um PHP, MySQL und alle anderen benötigten Services bereitzustellen.

**Warum DDEV?**
- ✅ Keine manuelle Server-Konfiguration nötig
- ✅ Identische Umgebung auf allen Rechnern
- ✅ Einfaches Starten/Stoppen mit einem Befehl
- ✅ Automatische HTTPS-Unterstützung
- ✅ Isoliert von deinem System (keine Konflikte)

**Installation:**

**Automatisch (empfohlen):**
1. Führe das Setup-Skript aus (Option 6 im Hauptmenü)
2. Das Skript installiert DDEV automatisch für dich

**Manuell (Linux):**
```bash
# Installiere DDEV
curl -fsSL https://ddev.com/install.sh | bash

# Oder mit Homebrew (macOS)
brew install ddev/ddev/ddev

# Prüfe Installation
ddev version
```

**📖 Offizielle Dokumentation:**
- **GitHub:** https://github.com/ddev/ddev
- **Installation:** https://ddev.readthedocs.io/en/stable/users/install/
- **Dokumentation:** https://ddev.readthedocs.io/

**Voraussetzungen für DDEV:**
- Docker muss installiert sein (wird normalerweise automatisch installiert)
- Genug Speicherplatz (~2GB für WoltLab)

**Erste Schritte mit DDEV:**
1. Wechsle ins DDEV-Verzeichnis: `cd tools/woltlab-dev`
2. Starte DDEV: `ddev start` (oder über das Menü: Option 4)
3. Warte, bis DDEV vollständig gestartet ist
4. Öffne die URL im Browser: `https://woltlab.ddev.site`

**Troubleshooting:**
- Falls DDEV nicht startet: `ddev restart`
- Falls Ports belegt sind: `ddev stop` und dann `ddev start`
- Logs ansehen: `ddev logs` oder im Menü Option 4 → "logs"

---

### 🐳 Dockge - Container-Management (Optional)

**Was ist Dockge?**
Dockge ist eine moderne, schnellere Alternative zu Portainer für die Verwaltung von Docker-Containern. Es bietet eine intuitive Web-Oberfläche zur Verwaltung von DDEV und anderen Containern - perfekt für Einsteiger!

**Warum Dockge?**
- ✅ Visuelle Verwaltung von Docker-Containern
- ✅ Einfaches Starten/Stoppen von Containern
- ✅ Logs und Status auf einen Blick
- ✅ Keine Kommandozeile nötig

**Installation:**

**Automatisch (empfohlen):**
1. Starte Dockge über das Menü (Option 10)
2. Beim ersten Start wird Dockge automatisch installiert

**Migration von Portainer (falls noch vorhanden):**
Falls du noch Portainer verwendest, kannst du mit dem Migrations-Script umsteigen:
```bash
./tools/migrate-to-dockge.sh
```
Dies entfernt Portainer automatisch und installiert Dockge.

**Manuell:**
```bash
# Starte Dockge Container
docker run -d \
  -p 5001:5001 \
  --name dockge \
  --restart=always \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -v ./dockge/stacks:/app/data/stacks \
  -v ./dockge:/data \
  louislam/dockge:latest
```

**Zugriff:**
- **HTTP:** http://localhost:5001

**📖 Offizielle Dokumentation:**
- **Dokumentation:** https://dockge.kuma.pet/
- **GitHub:** https://github.com/louislam/dockge
- **Installation:** https://dockge.kuma.pet/guide/getting-started/

**Voraussetzungen:**
- Docker muss installiert und laufen
- Port 5001 muss frei sein

**Erste Schritte mit Dockge:**
1. Starte Dockge über das Menü (Option 10)
2. Öffne http://localhost:5001 im Browser
3. Dockge ist sofort einsatzbereit - keine zusätzliche Konfiguration nötig!
5. Du siehst jetzt alle Docker-Container, inklusive DDEV!

---

### 🗄️ HeidiSQL - Datenbank-Verwaltung (Optional)

**Was ist HeidiSQL?**
HeidiSQL ist ein kostenloses Datenbank-Verwaltungstool für MySQL, MariaDB und andere Datenbanken. Es ermöglicht es dir, die WoltLab-Datenbank visuell zu verwalten.

**Warum HeidiSQL?**
- ✅ Kostenlos und Open Source
- ✅ Einfache Bedienung
- ✅ Visuelle Datenbank-Verwaltung
- ✅ SQL-Abfragen ausführen
- ✅ Daten exportieren/importieren

**Installation:**

**Automatisch (empfohlen):**
1. Führe das Setup-Skript aus (Option 6)
2. Das Skript installiert HeidiSQL automatisch (falls verfügbar)
3. Die Verbindung wird automatisch konfiguriert

**Manuell (Linux):**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install heidisql

# Arch Linux (AUR)
yay -S heidisql

# Oder lade von der offiziellen Website herunter
```

**Windows:**
1. Lade HeidiSQL von https://www.heidisql.com/download.php herunter
2. Installiere das Programm
3. Die Verbindung wird automatisch vom Setup-Skript konfiguriert

**Verbindung konfigurieren:**
1. Öffne HeidiSQL
2. Erstelle eine neue Verbindung:
   - **Host:** 127.0.0.1 (oder localhost)
   - **Port:** 3306 (oder der MySQL-Port von DDEV)
   - **Benutzer:** db (Standard-DDEV-Benutzer)
   - **Passwort:** db (Standard-DDEV-Passwort)
   - **Datenbank:** db (Standard-DDEV-Datenbank)

**📖 Offizielle Dokumentation:**
- **Hilfe:** https://www.heidisql.com/help.php
- **Download:** https://www.heidisql.com/download.php
- **Dokumentation:** https://www.heidisql.com/doc/

**Tipp:** Das Setup-Skript (Option 6) konfiguriert HeidiSQL automatisch mit den richtigen Zugangsdaten!

---

### 📦 WoltLab Suite - Installation

**Was ist WoltLab Suite?**
WoltLab Suite ist ein Content Management System (CMS) und Forum-Software. Die Development Tools helfen dir dabei, Plugins für WoltLab zu entwickeln.

**Installation:**

**Automatisch (empfohlen):**
1. Führe das Setup-Skript aus (Option 6)
2. Das Skript lädt WoltLab automatisch herunter
3. Oder verwende Option 7 "WoltLab Download"

**Manuell:**
1. Lade WoltLab Suite von https://www.woltlab.com/ herunter
2. Du benötigst eine gültige Lizenz
3. Extrahiere die Dateien nach `tools/woltlab-dev/public/`
4. Führe die Installation über den Browser durch: `https://woltlab.ddev.site/install.php`

**📖 Offizielle Dokumentation:**
- **Installation:** https://manual.woltlab.com/de/installation/
- **Systemvoraussetzungen:** https://manual.woltlab.com/de/requirements/
- **Handbuch:** https://manual.woltlab.com/

**Installationsschritte:**
1. Stelle sicher, dass DDEV läuft (Option 4 im Menü)
2. Öffne `https://woltlab.ddev.site/install.php` im Browser
3. Folge der Installationsanleitung
4. **Wichtig:** Installiere KEINE Plugins während der Installation (für saubere Datenbank)
5. Nach der Installation: Erstelle einen Snapshot (Option 8)

---

### 🛠️ Weitere Tools

**Node.js & npm:**
- Wird für TypeScript-Kompilierung benötigt
- Wird automatisch vom Setup-Skript installiert
- **Dokumentation:** https://nodejs.org/

**Git:**
- Wird für Version Control benötigt
- Wird normalerweise automatisch installiert
- **Dokumentation:** https://git-scm.com/

**Docker:**
- Wird für DDEV benötigt
- Wird normalerweise automatisch mit DDEV installiert
- **Dokumentation:** https://docs.docker.com/

---

## 🚀 Erste Schritte

### Schritt 1: Ins Verzeichnis wechseln

Öffne ein Terminal (Konsole) und wechsle ins Hauptverzeichnis:

```bash
cd ~/Dokumente/woltlab-development
```

### Schritt 2: Das Hauptmenü starten

Führe das Hauptskript aus:

```bash
./tools.sh
```

Oder direkt im Tools-Verzeichnis:

```bash
cd tools
./tools.sh
```

### Schritt 3: Im Menü navigieren

Du siehst jetzt ein Menü mit nummerierten Optionen. Wähle eine Option durch Eingabe der Nummer (z.B. `1` für Build) und drücke Enter.

**Tipp:** Du kannst jederzeit `0` eingeben, um zum Hauptmenü zurückzukehren oder das Programm zu beenden.

---

## 📋 Verfügbare Tools im Detail

### 1️⃣ Build - Plugin bauen

**Was macht es?**
Erstellt ein installierbares Plugin-Archiv (.tar Datei) aus deinem Plugin-Code.

**Was passiert dabei?**
1. TypeScript wird zu JavaScript kompiliert
2. Minimierte JavaScript-Dateien werden erstellt
3. Alle Plugin-Dateien werden in ein Archiv gepackt
4. Die Version in der `package.xml` wird automatisch erhöht

**Version-Typen erklärt:**
- **Patch** (1.0.0 → 1.0.1): Kleine Fehlerbehebungen
- **Minor** (1.0.0 → 1.1.0): Neue Features, rückwärtskompatibel
- **Major** (1.0.0 → 2.0.0): Große Änderungen, möglicherweise nicht kompatibel

**Verwendung:**
- Im Menü: Option `1` wählen
- Automatisch: Das erste gefundene Plugin wird gebaut
- Manuell: Plugin-Name eingeben (z.B. `basis-plugin`)
- Alle: `all` eingeben, um alle Plugins zu bauen

**Wo finde ich das gebaute Plugin?**
Im Plugin-Verzeichnis als `.tar` Datei, z.B. `basis-plugin/com.example.plugin.tar`

---

### 2️⃣ Git Push - Commit & Push mit Release

**Was macht es?**
Führt alle Git-Operationen automatisch durch, die normalerweise manuell gemacht werden müssten.

**Was passiert dabei?**
1. Erkennt automatisch, welche Plugins geändert wurden
2. Erstellt oder aktualisiert eine Changelog-Datei
3. Committet alle Änderungen mit einer automatisch generierten Nachricht
4. Pusht die Änderungen zu GitHub
5. Erstellt einen Git-Tag für die neue Version
6. Erstellt ein GitHub Release mit den Release-Notes

**Verwendung:**
- Im Menü: Option `2` wählen
- Automatisch: `auto` (Standard) - erkennt Änderungen selbst
- Manuell: Plugin-Name eingeben (z.B. `basis-plugin`)
- Alle: `all` eingeben, um alle Plugins zu pushen
- Custom Message: Optional eine eigene Commit-Nachricht eingeben

**Wichtig:** Du musst vorher Git konfiguriert haben (Benutzername, E-Mail, GitHub-Token in `.env`)

---

### 3️⃣ TypeScript - Kompilieren

**Was macht es?**
Konvertiert TypeScript-Code (.ts Dateien) zu JavaScript (.js Dateien), damit er im Browser ausgeführt werden kann.

**Was passiert dabei?**
1. Sucht nach allen TypeScript-Dateien in den Plugin-Verzeichnissen
2. Kompiliert sie zu JavaScript
3. Erstellt minimierte Versionen (.min.js) für die Produktion

**Modi:**
- **Normal**: Kompiliert einmalig alle Dateien
- **Watch**: Überwacht Dateien und kompiliert automatisch bei Änderungen

**Verwendung:**
- Im Menü: Option `3` wählen
- Normal: Leer lassen oder `normal` eingeben
- Watch: `watch` eingeben (beendet mit Ctrl+C)

**Wo finde ich die kompilierten Dateien?**
Im `js/` Verzeichnis deines Plugins, z.B. `basis-plugin/js/`

---

### 4️⃣ DDEV - Verwalten

**Was ist DDEV?**
DDEV ist eine lokale Entwicklungsumgebung, die eine komplette WoltLab Suite auf deinem Computer simuliert - ohne dass du einen Server installieren musst.

**Was macht es?**
Startet, stoppt und verwaltet deine lokale WoltLab-Installation in Docker-Containern.

**Optionen:**
- **Start** (Standard): Startet DDEV oder zeigt den aktuellen Status
- **Logs**: Startet DDEV und zeigt die Log-Ausgaben (für Fehlersuche)
- **Stop**: Stoppt DDEV (Container bleiben erhalten)
- **Restart**: Startet DDEV neu
- **Status**: Zeigt detaillierte Informationen über den aktuellen Status

**Was wird angezeigt?**
- 🌐 **Frontend-URL**: Die Adresse deiner WoltLab-Installation (z.B. `https://woltlab.ddev.site`)
- 🔧 **ACP-URL**: Die Adresse des Admin-Bereichs (z.B. `https://woltlab.ddev.site/acp/`)
- 📡 **Ports**: 
  - HTTP: Für unverschlüsselte Verbindungen
  - HTTPS: Für verschlüsselte Verbindungen
  - MySQL: Für Datenbank-Verbindungen
- 🐳 **Dockge**: Falls Dockge läuft, wird die URL angezeigt

**Verwendung:**
- Im Menü: Option `4` wählen
- Kommando eingeben (oder Leer lassen für Start)

**Erste Verwendung:**
Wenn DDEV noch nicht installiert ist, führt das Setup-Skript (Option 6) die Installation automatisch durch.

---

### 5️⃣ Restore Snapshot - WoltLab wiederherstellen

**Was ist ein Snapshot?**
Ein Snapshot ist ein komplettes Backup deiner WoltLab-Installation zu einem bestimmten Zeitpunkt - inklusive aller Dateien und der Datenbank.

**Was macht es?**
Stellt eine komplette WoltLab-Installation aus einem vorher erstellten Snapshot wieder her.

**Was wird wiederhergestellt?**
- ✅ Alle Dateien im `public/` Verzeichnis (~7420 Dateien)
- ✅ Die komplette Datenbank (161 Tabellen)
- ✅ Alle Einstellungen und Konfigurationen
- ✅ Alle hochgeladenen Dateien (Attachments)

**Was wird dabei gemacht?**
1. Datenbank wird zurückgesetzt
2. Alle Dateien werden kopiert
3. Caches werden geleert
4. HeidiSQL-Konfiguration wird aktualisiert
5. Optional: Firefox mit ACP wird geöffnet

**Dauer:** Ca. 8-10 Sekunden

**Verwendung:**
- Im Menü: Option `5` wählen
- Bestätigung: `j` oder `J` eingeben, um fortzufahren
- Abbrechen: Leer lassen oder `n` eingeben

**Wichtig:** ⚠️ Dies überschreibt deine aktuelle Installation komplett! Stelle sicher, dass du keine ungespeicherten Änderungen hast.

**Voraussetzung:** Du musst vorher einen Snapshot erstellt haben (Option 8: Snapshot Manager)

---

### 6️⃣ Setup - Vollständige Installation

**Was macht es?**
Installiert und konfiguriert automatisch alle benötigten Tools für die WoltLab-Entwicklung. **Perfekt für Einsteiger!** Das Setup-Skript führt dich Schritt für Schritt durch die Installation.

**Was wird installiert?**

1. **DDEV** - Lokale Entwicklungsumgebung
   - Installiert DDEV automatisch (falls nicht vorhanden)
   - Konfiguriert Docker-Container für WoltLab
   - Erstellt `.ddev` Konfiguration
   - **Dokumentation:** https://github.com/ddev/ddev

2. **HeidiSQL** (falls nicht vorhanden)
   - Versucht HeidiSQL zu installieren (falls verfügbar)
   - Konfiguriert automatisch die Datenbank-Verbindung
   - Speichert Passwort in HeidiSQL-Konfiguration
   - **Dokumentation:** https://www.heidisql.com/help.php

3. **Node.js und npm**
   - Installiert Node.js für TypeScript-Kompilierung
   - Installiert npm für Package-Management
   - **Dokumentation:** https://nodejs.org/

4. **WoltLab Core**
   - Lädt aktuelle WoltLab Suite herunter (falls gewünscht)
   - Extrahiert Dateien nach `woltlab-core/`
   - **Dokumentation:** https://manual.woltlab.com/de/installation/

5. **.env Datei**
   - Erstellt Konfigurationsdatei mit allen Zugangsdaten
   - Generiert sichere Passwörter (optional)
   - Speichert DDEV, MySQL, HeidiSQL und WoltLab Zugangsdaten

6. **Git Repository**
   - Initialisiert Git (falls noch nicht geschehen)
   - Konfiguriert Remote-Repository (falls gewünscht)
   - **Dokumentation:** https://git-scm.com/

7. **Erster Snapshot** (optional)
   - Erstellt automatisch einen ersten Snapshot
   - Dient als Backup-Basis für spätere Restores

**Verwendung:**
- Im Menü: Option `6` wählen
- Folge den Anweisungen auf dem Bildschirm
- Bei Fragen: Standardwerte verwenden (Enter drücken)
- Das Skript fragt dich bei jedem Schritt, was installiert werden soll

**Dauer:** 5-15 Minuten (abhängig von Internetgeschwindigkeit)

**Voraussetzungen:**
- ✅ Internetverbindung (für Downloads)
- ✅ Administrator-Rechte (sudo) für einige Installationen
- ✅ Genug Speicherplatz (~2GB für WoltLab + DDEV)
- ✅ Docker installiert (wird normalerweise automatisch mit DDEV installiert)

**Schritt-für-Schritt Anleitung:**

1. **Starte das Setup:**
   ```bash
   ./tools.sh
   # Wähle Option 6
   ```

2. **Folge den Fragen:**
   - Soll DDEV installiert werden? → **Ja** (empfohlen)
   - Soll HeidiSQL installiert werden? → **Ja** (optional, aber empfohlen)
   - Soll Node.js installiert werden? → **Ja** (für TypeScript)
   - Soll WoltLab heruntergeladen werden? → **Ja** (falls noch nicht vorhanden)
   - Sollen Passwörter generiert werden? → **Ja** (für Sicherheit)

3. **Warte auf Installation:**
   - Das Skript installiert alles automatisch
   - Du siehst Fortschrittsanzeigen
   - Bei Fehlern: Prüfe die Fehlermeldungen

4. **Nach der Installation:**
   - Starte DDEV (Option 4 im Menü)
   - Installiere WoltLab über den Browser: `https://woltlab.ddev.site/install.php`
   - Erstelle einen Snapshot (Option 8)

**Troubleshooting:**

- **DDEV Installation schlägt fehl:**
  - Prüfe, ob Docker installiert ist: `docker --version`
  - Installiere Docker manuell: https://docs.docker.com/get-docker/
  - Versuche DDEV manuell zu installieren: https://github.com/ddev/ddev

- **HeidiSQL Installation schlägt fehl:**
  - Kein Problem! HeidiSQL ist optional
  - Du kannst es später manuell installieren: https://www.heidisql.com/download.php
  - Die Datenbank-Verbindung kann auch später konfiguriert werden

- **WoltLab Download schlägt fehl:**
  - Prüfe deine Internetverbindung
  - Stelle sicher, dass du auf woltlab.com eingeloggt bist
  - Lade WoltLab manuell herunter: https://www.woltlab.com/

**Weitere Hilfe:**
- **DDEV:** https://github.com/ddev/ddev
- **Dockge:** https://dockge.kuma.pet/
- **HeidiSQL:** https://www.heidisql.com/help.php
- **WoltLab:** https://manual.woltlab.com/de/installation/

---

### 7️⃣ WoltLab Download - Core herunterladen

**Was macht es?**
Lädt die aktuelle WoltLab Suite automatisch von woltlab.com herunter.

**Was passiert dabei?**
1. Ruft die Download-Seite von woltlab.com auf
2. Findet automatisch den aktuellsten Download-Link
3. Lädt die Datei herunter
4. Extrahiert `WCFSetup.tar.gz`
5. Kopiert alles nach `woltlab-core/`

**Verwendung:**
- Im Menü: Option `7` wählen
- Warte, bis der Download abgeschlossen ist

**Wichtig:** 
- Du benötigst eine gültige WoltLab-Lizenz
- Du musst auf woltlab.com eingeloggt sein (im Browser)
- Der Download kann mehrere Minuten dauern

---

### 8️⃣ Snapshot Manager - Snapshot-Verwaltung

**Was ist ein Snapshot?**
Ein Snapshot ist ein **komplettes Backup** deiner WoltLab-Installation zu einem bestimmten Zeitpunkt. Es ist wie eine "Momentaufnahme" deines Systems - inklusive aller Dateien, der Datenbank, Einstellungen und Konfigurationen.

**Was ist eine "saubere" Datenbank?**
Eine saubere Datenbank ist eine **frische, neu installierte WoltLab-Datenbank** direkt nach der Installation - ohne jegliche Änderungen oder Test-Daten.

**Was bedeutet "sauber" genau?**

✅ **Eine saubere Datenbank enthält:**
- ✅ Nur den Admin-Benutzer (den du bei der Installation erstellt hast)
- ✅ Standard-WoltLab-Einstellungen (unverändert)
- ✅ Standard-Plugins (die bei der Installation automatisch installiert werden)
- ✅ Leere Tabellen für Content (Foren, Artikel, etc. - aber keine Inhalte)
- ✅ System-Tabellen (für WoltLab-Funktionalität)

❌ **Eine saubere Datenbank enthält NICHT:**
- ❌ Test-Beiträge, Test-Artikel, Test-Foren
- ❌ Zusätzliche Benutzer (außer dem Admin)
- ❌ Installierte Custom-Plugins (deine eigenen Plugins)
- ❌ Geänderte Einstellungen
- ❌ Hochgeladene Dateien (außer System-Dateien)
- ❌ Angepasste Templates
- ❌ Custom-CSS oder JavaScript

**Warum ist das wichtig?**
Wenn du einen Snapshot mit einer sauberen Datenbank erstellst, kannst du **jederzeit** zu diesem identischen Ausgangszustand zurückkehren. Das ist perfekt für:
- Plugin-Tests (jeder Test startet mit der gleichen Basis)
- Fehlerbehebung (wenn etwas schiefgeht, einfach zurücksetzen)
- Experimente (teste Features ohne Angst vor Datenverlust)
- Reproduzierbare Tests (immer der gleiche Ausgangszustand)

**Wie stelle ich sicher, dass meine Datenbank sauber ist?**
1. **Führe eine NEUE Installation durch:**
   - Verwende das Snapshot-Skript (es leert automatisch alles)
   - Oder lösche manuell die Datenbank und installiere neu

2. **Installiere WoltLab FRISCH:**
   - Verwende die Installations-URL: `https://woltlab.ddev.site/install.php`
   - Folge der Installation Schritt für Schritt
   - **WICHTIG:** Installiere KEINE Plugins während der Installation
   - **WICHTIG:** Erstelle KEINE Test-Daten

3. **Prüfe die Datenbank:**
   - Öffne HeidiSQL
   - Verbinde dich mit der Datenbank
   - Prüfe die Tabellen:
     - `wcf1_user` sollte nur 1 Benutzer enthalten (dein Admin)
     - `wcf1_post` sollte leer sein (0 Zeilen)
     - `wcf1_thread` sollte leer sein (0 Zeilen)
     - `wcf1_article` sollte leer sein (0 Zeilen)

4. **Erstelle den Snapshot SOFORT:**
   - Direkt nach der Installation
   - Bevor du irgendwelche Änderungen machst
   - Bevor du Plugins installierst

**Warum ist das wichtig?**
Ein Snapshot mit einer sauberen Datenbank ermöglicht es dir, immer wieder zu einem **identischen Ausgangszustand** zurückzukehren. Das ist perfekt für:
- Plugin-Tests (jeder Test startet mit der gleichen Basis)
- Fehlerbehebung (wenn etwas schiefgeht, einfach zurücksetzen)
- Experimente (teste Features ohne Angst vor Datenverlust)

---

#### 📸 Snapshot erstellen - Schritt für Schritt

**Voraussetzungen - Was wird benötigt?**

Bevor du einen Snapshot erstellen kannst, müssen folgende Dinge vorhanden und funktionsfähig sein:

1. **✅ DDEV installiert und funktionsfähig**
   - Prüfung: `cd tools/woltlab-dev && ddev describe`
   - Falls nicht installiert: Führe Setup aus (Option 6)
   - Falls nicht funktionsfähig: `ddev restart`

2. **✅ WoltLab Core heruntergeladen**
   - Prüfung: `ls -la woltlab-core/WCFSetup.tar.gz`
   - Falls nicht vorhanden: Führe Option 7 (WoltLab Download) aus
   - Benötigt: Gültige WoltLab-Lizenz und Login auf woltlab.com

3. **✅ Docker läuft**
   - Prüfung: `sudo systemctl status docker`
   - Falls nicht: `sudo systemctl start docker`

4. **✅ Genug Speicherplatz**
   - Benötigt: ~100 MB freier Speicherplatz
   - Prüfung: `df -h`

5. **✅ Firefox installiert** (für automatisches Öffnen)
   - Falls nicht: Installation funktioniert trotzdem, du musst Browser manuell öffnen

6. **✅ Eine frische, saubere WoltLab-Installation**
   - Das Snapshot-Skript leert automatisch alles
   - Du musst WoltLab dann manuell installieren (siehe unten)

**Schritt 1: Snapshot Manager öffnen**
1. Im Hauptmenü: Option `8` (Snapshot Manager) wählen
2. Option `1` (Snapshot erstellen) wählen

**Schritt 2: Automatische Vorbereitung**
Das Skript führt automatisch aus:
1. ✅ **DDEV starten** - Startet die Entwicklungsumgebung
2. ✅ **Public-Ordner leeren** - Entfernt alle Dateien (außer Installer)
3. ✅ **Datenbank leeren** - Löscht die komplette Datenbank und erstellt sie neu
4. ✅ **Firefox öffnen** - Öffnet automatisch den WoltLab-Installer

**Schritt 3: WoltLab manuell installieren**
Jetzt musst du WoltLab **einmalig manuell installieren**:

1. **Im Firefox-Browser** (wurde automatisch geöffnet):
   - URL: `https://woltlab.ddev.site/install.php`

2. **Installationsschritte durchführen:**
   - **Schritt 1 - Sprache:** Wähle `Deutsch` (oder deine bevorzugte Sprache)
   - **Schritt 2 - Lizenz:** Akzeptiere die Lizenzbedingungen
   - **Schritt 3 - Systemcheck:** Alle Prüfungen sollten grün sein
   - **Schritt 4 - Datenbank:**
     ```
     Host:     db
     Benutzer: db
     Passwort: db
     Datenbank: db
     ```
     *(Oder verwende die Werte aus deiner `.env` Datei)*
   - **Schritt 5 - Admin-Benutzer:**
     ```
     Benutzername: Admin
     E-Mail:       admin@example.com
     Passwort:     123456
     Passwort bestätigen: 123456
     ```
     *(Oder verwende die Werte aus deiner `.env` Datei)*
   - **Schritt 6 - Lizenz:** 
     - Wähle: **"Ohne Lizenzdaten fortfahren"** (für Entwicklung)
     - Oder gib deine Lizenzdaten ein
   - **Schritt 7 - Einstellungen:**
     - Alle Standardwerte übernehmen
     - Einfach auf "Absenden" klicken
   - **Schritt 8 - Fertig:**
     - Installation ist abgeschlossen
     - Du siehst die Erfolgsmeldung

3. **WICHTIG:** Lass die Installation **KOMPLETT durchlaufen** bis zur Erfolgsmeldung!

**Schritt 4: Snapshot-Erstellung bestätigen**
1. **Zurück im Terminal:**
   - Du siehst die Meldung: "Drücke ENTER wenn Installation KOMPLETT FERTIG ist..."
   - **Warte**, bis die Installation im Browser **vollständig abgeschlossen** ist
   - **Dann** drücke ENTER im Terminal

2. **Automatische Snapshot-Erstellung:**
   Das Skript erstellt jetzt automatisch:
   - ✅ **Public-Ordner Backup** (~7420 Dateien, ~54 MB)
   - ✅ **Datenbank-Export** (161 Tabellen, ~684 KB komprimiert)
   - ✅ **Metadaten** (Datum, Version, etc.)

**Schritt 5: Snapshot ist fertig!**
Du siehst eine Zusammenfassung:
```
✓ Public-Ordner: 7420 Dateien, 54M
✓ Datenbank: 161 Tabellen, 684K
✓ Snapshot erstellt: [Datum/Zeit]
```

**Wo wird der Snapshot gespeichert?**
```
tools/woltlab-snapshot/
├── public/              # Alle WoltLab-Dateien
│   ├── acp/            # Admin-Bereich
│   ├── lib/            # PHP-Bibliotheken
│   ├── templates/      # Templates
│   └── ...             # Alle anderen Dateien
├── database.sql.gz     # Datenbank-Dump (komprimiert)
└── metadata.txt        # Informationen über den Snapshot
```

**Dauer:** 
- Manuelle Installation: ~2-5 Minuten (einmalig)
- Automatische Snapshot-Erstellung: ~30 Sekunden

---

#### 🔄 Snapshot wiederherstellen - Schritt für Schritt

**Voraussetzungen - Was wird benötigt?**

Bevor du einen Snapshot wiederherstellen kannst, müssen folgende Dinge vorhanden und funktionsfähig sein:

1. **✅ Ein Snapshot muss existieren**
   - Prüfung: `ls -la tools/woltlab-snapshot/database.sql.gz`
   - Falls nicht vorhanden: Erstelle zuerst einen Snapshot (Option 8 → Option 1)
   - Der Snapshot muss vollständig sein (Dateien + Datenbank)

2. **✅ DDEV muss installiert sein**
   - Prüfung: `cd tools/woltlab-dev && ddev describe`
   - Falls nicht installiert: Führe Setup aus (Option 6)

3. **✅ Docker läuft**
   - Prüfung: `sudo systemctl status docker`
   - Falls nicht: `sudo systemctl start docker`

4. **✅ Genug Speicherplatz vorhanden**
   - Benötigt: ~100 MB freier Speicherplatz
   - Prüfung: `df -h`

5. **✅ MySQL-Port erreichbar**
   - Das Skript ermittelt den Port automatisch
   - Falls Probleme: Prüfe DDEV-Status

**Was passiert mit meiner aktuellen Installation?**
⚠️ **WICHTIG:** Der Restore **überschreibt KOMPLETT** deine aktuelle Installation:
- ❌ Alle Dateien im `public/` Ordner werden **gelöscht**
- ❌ Die komplette Datenbank wird **gelöscht** und neu erstellt
- ❌ Alle Änderungen gehen **verloren**
- ✅ Dann wird alles aus dem Snapshot wiederhergestellt

**Backup-Empfehlung:**
Falls du wichtige Änderungen hast, die du behalten möchtest:
1. Erstelle einen neuen Snapshot von deinem aktuellen Zustand
2. Oder speichere wichtige Dateien manuell
3. Dann führe den Restore durch

**Was passiert beim Restore?**
Der Restore **überschreibt komplett** deine aktuelle Installation:
- ❌ Alle Dateien im `public/` Ordner werden gelöscht
- ❌ Die komplette Datenbank wird gelöscht
- ✅ Dann wird alles aus dem Snapshot wiederhergestellt

**Schritt 1: Restore starten**
1. Im Hauptmenü: Option `5` (Restore Snapshot) wählen
2. Oder direkt: Option `8` → Option `2` (Snapshot wiederherstellen)

**Schritt 2: Bestätigung**
- Du siehst eine Warnung: "Dies wird die komplette WoltLab-Installation wiederherstellen!"
- Bestätige mit `j` oder `J`
- Oder breche ab mit `n` oder einfach Enter

**Schritt 3: Automatischer Restore-Prozess**
Das Skript führt automatisch aus (in ~8 Sekunden):

1. **[1/6] DDEV stoppen**
   - Stoppt die laufende DDEV-Instanz
   - Dauer: ~1 Sekunde

2. **[2/6] Public-Ordner löschen**
   - Löscht **alle** Dateien im `public/` Verzeichnis
   - Dauer: ~1 Sekunde

3. **[3/6] DDEV starten**
   - Startet DDEV neu
   - Wartet bis alle Container bereit sind
   - Dauer: ~3 Sekunden

4. **[4/6] Public-Ordner kopieren**
   - Kopiert alle Dateien aus dem Snapshot
   - ~7420 Dateien werden kopiert
   - Dauer: ~2 Sekunden

5. **[5/6] Datenbank importieren**
   - Löscht die aktuelle Datenbank
   - Erstellt eine neue, leere Datenbank
   - Importiert den Datenbank-Dump aus dem Snapshot
   - 161 Tabellen werden wiederhergestellt
   - Dauer: ~1 Sekunde

6. **[6/6] Aufräumen & Konfiguration**
   - Leert Caches (für sauberen Start)
   - Konfiguriert HeidiSQL automatisch
   - Öffnet optional Firefox mit ACP
   - Dauer: ~1 Sekunde

**Schritt 4: Fertig!**
Nach dem Restore hast du:
- ✅ Eine **identische Kopie** der Installation zum Zeitpunkt des Snapshots
- ✅ Alle Dateien wiederhergestellt
- ✅ Die komplette Datenbank wiederhergestellt
- ✅ HeidiSQL konfiguriert (Passwort automatisch gespeichert)
- ✅ Optional: Firefox mit ACP geöffnet

**Zugriff:**
- 🌐 Frontend: `https://woltlab.ddev.site/`
- 🔧 ACP: `https://woltlab.ddev.site/acp/`
- 👤 Admin: `Admin` / Passwort: `123456` (oder aus `.env`)

**Dauer gesamt:** ~8-10 Sekunden

---

#### 📊 Was genau wird gesichert?

**1. Public-Ordner (~7420 Dateien, ~54 MB)**
Enthält:
- ✅ Alle PHP-Dateien (`lib/`, `acp/`, etc.)
- ✅ Alle Templates (`templates/`)
- ✅ JavaScript und CSS (`js/`, `style/`)
- ✅ Bilder und Icons (`images/`, `icon/`)
- ✅ Konfigurationsdateien (`config.inc.php`, `options.inc.php`)
- ✅ Sprachdateien (`language/`)
- ✅ Alle anderen WoltLab-Dateien

**NICHT gesichert:**
- ❌ `WCFSetup.tar.gz` (zu groß, wird beim Restore nicht benötigt)
- ❌ Temporäre Dateien (`tmp/`, `cache/` - werden beim Restore geleert)

**2. Datenbank (161 Tabellen, ~684 KB komprimiert)**
Enthält:
- ✅ Alle WoltLab-System-Tabellen
- ✅ Admin-Benutzer und Einstellungen
- ✅ Installierte Standard-Plugins
- ✅ Alle Konfigurationen

**NICHT gesichert:**
- ❌ Test-Daten (Beiträge, Benutzer, etc.) - wenn du eine saubere Installation hast
- ❌ Installierte Custom-Plugins - wenn du eine saubere Installation hast

**3. Metadaten**
Enthält:
- ✅ Datum und Uhrzeit der Erstellung
- ✅ Anzahl der Dateien
- ✅ Anzahl der Tabellen
- ✅ Größe der Backups
- ✅ WoltLab-Version

---

#### 🔍 Snapshots auflisten

**Verwendung:**
1. Im Menü: Option `8` (Snapshot Manager) wählen
2. Option `3` (Snapshots auflisten) wählen

**Was wird angezeigt:**
- Datum und Uhrzeit der Erstellung
- Anzahl der Dateien
- Anzahl der Tabellen
- Größe der Backups
- WoltLab-Version

**Wo finde ich die Informationen?**
Im Terminal oder in der Datei: `tools/woltlab-snapshot/metadata.txt`

---

#### 🗑️ Snapshot löschen

**Wann sollte ich einen Snapshot löschen?**
- Wenn du einen neuen, besseren Snapshot erstellt hast
- Wenn du Speicherplatz benötigst
- Wenn der Snapshot veraltet ist

**Verwendung:**
1. Im Menü: Option `8` (Snapshot Manager) wählen
2. Option `4` (Snapshot löschen) wählen
3. Bestätige die Löschung

**Was wird gelöscht?**
- ✅ Der komplette `woltlab-snapshot/` Ordner
- ✅ Alle Dateien und die Datenbank

**Wichtig:** ⚠️ Diese Aktion kann nicht rückgängig gemacht werden!

---

#### ✅ Status prüfen

**Verwendung:**
1. Im Menü: Option `8` (Snapshot Manager) wählen
2. Option `5` (Status prüfen) wählen

**Was wird geprüft:**
- ✅ Existiert der Snapshot-Ordner?
- ✅ Existiert die Datenbank-Datei?
- ✅ Existiert der Public-Ordner?
- ✅ Sind alle Dateien vorhanden?
- ✅ Ist die Datenbank-Datei gültig?

**Bei Problemen:**
- Wenn Dateien fehlen: Erstelle einen neuen Snapshot
- Wenn die Datenbank-Datei beschädigt ist: Erstelle einen neuen Snapshot

---

#### 💡 Best Practices für Snapshots

**1. Erstelle einen Snapshot nach der ersten Installation**
- Direkt nach der frischen WoltLab-Installation
- Bevor du Plugins installierst oder testest
- Dies ist dein "Sauberer Ausgangszustand"

**2. Erstelle regelmäßig neue Snapshots**
- Nach größeren Änderungen
- Wenn du zufrieden mit dem aktuellen Zustand bist
- Als Backup vor Experimenten

**3. Teste deine Snapshots**
- Führe gelegentlich einen Restore durch
- Stelle sicher, dass alles funktioniert
- So weißt du, dass dein Snapshot gültig ist

**4. Verwende beschreibende Namen**
- Wenn du mehrere Snapshots hast (in Zukunft)
- Dokumentiere, was im Snapshot enthalten ist

---

#### ❓ Häufige Fragen zu Snapshots

**Q: Wie oft sollte ich einen Snapshot erstellen?**
**A:** 
- **Einmalig:** Nach der ersten, sauberen Installation
- **Regelmäßig:** Nach größeren Änderungen oder wenn du zufrieden bist
- **Vor Experimenten:** Immer vor größeren Tests oder Änderungen

**Q: Wie groß ist ein Snapshot?**
**A:**
- Public-Ordner: ~54 MB (7420 Dateien)
- Datenbank: ~684 KB (komprimiert)
- **Gesamt:** ~55 MB

**Q: Kann ich mehrere Snapshots haben?**
**A:** Aktuell wird nur ein Snapshot unterstützt. Wenn du einen neuen erstellst, wird der alte überschrieben. (In Zukunft könnte Multi-Snapshot-Support hinzugefügt werden)

**Q: Was ist, wenn mein Snapshot beschädigt ist?**
**A:**
1. Prüfe den Status (Option 8 → Option 5)
2. Wenn beschädigt: Erstelle einen neuen Snapshot
3. Stelle sicher, dass DDEV läuft und WoltLab installiert ist

**Q: Kann ich einen Snapshot auf einem anderen Computer verwenden?**
**A:** 
- Theoretisch ja, aber:
- Die DDEV-Konfiguration muss identisch sein
- Die Pfade müssen angepasst werden
- Es ist einfacher, einen neuen Snapshot zu erstellen

**Q: Was ist, wenn die Installation beim Snapshot-Erstellen fehlschlägt?**
**A:**
1. Prüfe, ob DDEV läuft: `cd tools/woltlab-dev && ddev describe`
2. Prüfe die Logs: `cd tools/woltlab-dev && ddev logs`
3. Stelle sicher, dass WoltLab Core heruntergeladen ist (Option 7)
4. Versuche es erneut

**Q: Kann ich einen Snapshot während DDEV läuft erstellen?**
**A:** Ja, das Snapshot-Skript startet DDEV automatisch, falls es nicht läuft.

**Q: Was passiert mit meinen Plugins beim Restore?**
**A:** 
- Wenn dein Snapshot eine **saubere Installation** enthält: Alle Plugins werden entfernt
- Wenn dein Snapshot Plugins enthält: Diese werden wiederhergestellt
- **Empfehlung:** Erstelle einen Snapshot **ohne** Plugins als Basis

---

#### 🐛 Fehlerbehebung bei Snapshots

**Problem: "Snapshot nicht gefunden"**
**Lösung:**
1. Prüfe, ob der Ordner existiert: `ls -la tools/woltlab-snapshot/`
2. Wenn nicht vorhanden: Erstelle einen neuen Snapshot (Option 8 → Option 1)
3. Stelle sicher, dass der Snapshot-Erstellungsprozess vollständig durchlaufen ist

**Problem: "Datenbank-Import fehlgeschlagen"**
**Lösung:**
1. Prüfe, ob DDEV läuft: `cd tools/woltlab-dev && ddev describe`
2. Prüfe MySQL-Logs: `cd tools/woltlab-dev && ddev logs db`
3. Prüfe, ob die Datenbank-Datei existiert: `ls -lh tools/woltlab-snapshot/database.sql.gz`
4. Wenn beschädigt: Erstelle einen neuen Snapshot

**Problem: "Public-Ordner konnte nicht kopiert werden"**
**Lösung:**
1. Prüfe Speicherplatz: `df -h`
2. Prüfe Berechtigungen: `ls -la tools/woltlab-dev/public/`
3. Stelle sicher, dass DDEV gestoppt ist während des Kopiervorgangs
4. Versuche es erneut

**Problem: "HeidiSQL-Konfiguration fehlgeschlagen"**
**Lösung:**
1. Prüfe, ob HeidiSQL installiert ist: `which heidisql`
2. Manuell konfigurieren: Im Menü Option `9` → Option `5` (HeidiSQL Passwort speichern)
3. Prüfe MySQL-Port: `cd tools/woltlab-dev && ddev describe`

---

#### 📝 Zusammenfassung: Snapshot-Workflow

**Erstmalige Einrichtung:**
1. ✅ Setup durchführen (Option 6)
2. ✅ WoltLab Core herunterladen (Option 7)
3. ✅ Snapshot erstellen (Option 8 → Option 1)
4. ✅ WoltLab manuell installieren (im Browser)
5. ✅ Snapshot-Erstellung bestätigen

**Tägliche Nutzung:**
1. ✅ Plugin entwickeln und testen
2. ✅ Bei Problemen: Restore durchführen (Option 5)
3. ✅ Nach größeren Änderungen: Neuen Snapshot erstellen

**Das war's!** Du hast jetzt ein professionelles Snapshot-System! 🎉

---

#### 📋 Checkliste: Snapshot erstellen

Verwende diese Checkliste, um sicherzustellen, dass alles korrekt ist:

**Vorbereitung:**
- [ ] DDEV ist installiert und funktionsfähig
- [ ] WoltLab Core ist heruntergeladen (Option 7)
- [ ] Docker läuft
- [ ] Genug Speicherplatz vorhanden (~100 MB)

**Während der Installation:**
- [ ] Firefox wurde automatisch geöffnet
- [ ] Installation wurde KOMPLETT durchgeführt
- [ ] Alle Installationsschritte wurden abgeschlossen
- [ ] Erfolgsmeldung im Browser wurde gesehen

**Nach der Installation:**
- [ ] ENTER im Terminal gedrückt (nach vollständiger Installation)
- [ ] Snapshot-Erstellung wurde erfolgreich abgeschlossen
- [ ] Metadaten wurden angezeigt
- [ ] Snapshot-Verzeichnis existiert: `tools/woltlab-snapshot/`

**Prüfung:**
- [ ] `tools/woltlab-snapshot/database.sql.gz` existiert
- [ ] `tools/woltlab-snapshot/public/` existiert und enthält Dateien
- [ ] `tools/woltlab-snapshot/metadata.txt` existiert

---

#### 📋 Checkliste: Snapshot wiederherstellen

Verwende diese Checkliste vor einem Restore:

**Vorbereitung:**
- [ ] Snapshot existiert (`tools/woltlab-snapshot/`)
- [ ] Datenbank-Datei existiert (`database.sql.gz`)
- [ ] Public-Ordner existiert (`public/`)
- [ ] DDEV ist installiert
- [ ] Docker läuft
- [ ] Wichtige Änderungen wurden gesichert (falls vorhanden)

**Während des Restores:**
- [ ] Restore wurde bestätigt (`j` oder `J`)
- [ ] Alle 6 Schritte wurden durchgeführt
- [ ] Keine Fehlermeldungen aufgetreten

**Nach dem Restore:**
- [ ] Frontend ist erreichbar: `https://woltlab.ddev.site/`
- [ ] ACP ist erreichbar: `https://woltlab.ddev.site/acp/`
- [ ] Login funktioniert (Admin / Passwort)
- [ ] HeidiSQL-Verbindung funktioniert (falls konfiguriert)

---

#### 🎓 Beispiel-Workflow: Vom Setup zum ersten Snapshot

**Schritt 1: Alles installieren**
```
1. Öffne Terminal
2. cd ~/Dokumente/woltlab-development
3. ./tools.sh
4. Option 6 (Setup) wählen
5. Warte bis alles installiert ist (~10 Minuten)
```

**Schritt 2: WoltLab Core herunterladen**
```
1. Im Menü: Option 7 (WoltLab Download) wählen
2. Warte bis Download abgeschlossen ist (~5 Minuten)
3. Stelle sicher, dass du auf woltlab.com eingeloggt bist
```

**Schritt 3: Snapshot erstellen**
```
1. Im Menü: Option 8 (Snapshot Manager) wählen
2. Option 1 (Snapshot erstellen) wählen
3. Warte bis Firefox geöffnet wird
4. Installiere WoltLab im Browser (siehe Anleitung oben)
5. Drücke ENTER im Terminal wenn Installation fertig ist
6. Warte bis Snapshot erstellt ist (~30 Sekunden)
```

**Schritt 4: Snapshot testen**
```
1. Im Menü: Option 5 (Restore Snapshot) wählen
2. Bestätige mit 'j'
3. Warte ~8 Sekunden
4. Prüfe ob alles funktioniert (Frontend, ACP, Login)
```

**Fertig!** Du hast jetzt eine funktionierende Entwicklungsumgebung mit Snapshot-System! 🎉

---

### 9️⃣ Credentials - Zugangsdaten-Verwaltung

**Was macht es?**
Verwaltet alle Passwörter, Benutzernamen und Einstellungen zentral in einer `.env` Datei.

**Funktionen:**

1. **.env Datei erstellen**
   - Erstellt eine neue Konfigurationsdatei
   - Basierend auf einem Template
   - Kann sichere Passwörter automatisch generieren

2. **Zugangsdaten anzeigen**
   - Zeigt alle gespeicherten Zugangsdaten
   - Passwörter werden maskiert (als `****` angezeigt)
   - Zeigt MySQL, WoltLab Admin, HeidiSQL, etc.

3. **Zugangsdaten validieren**
   - Prüft, ob MySQL-Verbindung funktioniert
   - Prüft, ob DDEV läuft
   - Zeigt Fehler an, falls vorhanden

4. **Passwort generieren**
   - Erstellt ein sicheres, zufälliges Passwort
   - Kann für verschiedene Zwecke verwendet werden

5. **HeidiSQL Passwort speichern**
   - Speichert Datenbank-Passwort automatisch in HeidiSQL
   - Konfiguriert HeidiSQL-Verbindung
   - Ermittelt automatisch MySQL-Port von DDEV

**Verwendung:**
- Im Menü: Option `9` wählen
- Wähle die gewünschte Aktion aus dem Untermenü

**Wo wird die .env Datei gespeichert?**
Im Verzeichnis `tools/.env` (wird nicht in Git gespeichert, da sie Passwörter enthält)

**Wichtig:** 
- Die `.env` Datei enthält sensible Daten - teile sie niemals!
- Erstelle regelmäßig Backups der `.env` Datei
- Verwende sichere Passwörter

---

### 🔟 Dockge - Container-Management

**Was ist Dockge?**
Dockge ist eine moderne, schnellere Alternative zu Portainer für die Verwaltung von Docker-Containern. Es bietet eine intuitive Web-Oberfläche zur Verwaltung von DDEV und anderen Containern.

**Was macht es?**
Startet, stoppt und verwaltet den Dockge-Container, der eine moderne Web-Oberfläche für Docker bereitstellt.

**Optionen:**
- **Start** (Standard): Startet Dockge oder zeigt den Status
- **Stop**: Stoppt Dockge
- **Restart**: Startet Dockge neu
- **Status**: Zeigt detaillierte Informationen
- **Open**: Öffnet Dockge im Browser

**Verwendung:**
- Im Menü: Option `10` wählen
- Kommando eingeben (oder Leer lassen für Start)

**Erste Verwendung:**
Beim ersten Start wird der Dockge-Container automatisch erstellt und gestartet.

**Zugriff:**
Nach dem Start ist Dockge unter `http://localhost:5001` erreichbar.

**Voraussetzung:** Docker muss installiert und laufen.

---

### 1️⃣1️⃣ Plugin Validierung - Security & Store-Compliance

**Was macht es?**
Prüft dein Plugin automatisch auf Sicherheitsprobleme, Code-Qualität und Plugin Store Compliance.

**Was wird geprüft?**

1. **Struktur & Syntax:**
   - ✅ `package.xml` vorhanden und syntaktisch korrekt
   - ✅ Package-Name im korrekten Format (com.domain.pluginname)
   - ✅ Version vorhanden
   - ✅ Minversion unterstützt (6.0+)
   - ✅ XML-Dateien (PIPs) syntaktisch korrekt
   - ✅ PHP-Dateien syntaktisch korrekt

2. **Security-Checks:**
   - 🛡️ **SQL-Injection:** Prüft auf gefährliche Query-Patterns
   - 🛡️ **XSS:** Prüft Templates auf unescaped Variablen
   - 🛡️ **Test-Credentials:** Prüft auf hardcoded Passwörter

3. **Code-Qualität:**
   - 🧹 **Debug-Code:** Prüft auf var_dump(), print_r(), console.log()
   - 🧹 **Best Practices:** Prüft auf WoltLab API-Nutzung (HTTPRequest statt curl)

4. **Plugin Store Compliance:**
   - 📦 **Übersetzungen:** DE + EN müssen vorhanden sein
   - 📦 **Package-Server:** Kein packageUpdateServer PIP erlaubt
   - 📦 **Excludedpackages:** Empfehlung für WoltLab 7.0 Alpha

**Verwendung:**
- Im Menü: Option `12` wählen
- Plugin-Verzeichnis eingeben (oder Leer lassen für aktuelles Verzeichnis)

**Beispiel:**
```bash
# Im Hauptmenü: Option 12 wählen
# Dann Plugin-Name eingeben: basis-plugin
# Oder direkt:
./tools/validate-plugin.sh basis-plugin
```

**Ergebnis:**
- ✅ **Erfolg:** Keine Fehler oder Warnungen
- ⚠️ **Warnungen:** Nicht kritisch, aber sollten geprüft werden
- ❌ **Fehler:** Müssen vor Release behoben werden

**Log-Datei:**
Alle Prüfungen werden in `/tmp/validate-plugin-YYYYMMDD-HHMMSS.log` gespeichert.

**Tipp:** Führe die Validierung **vor jedem Release** durch, um Plugin Store Ablehnungen zu vermeiden!

**Weitere Informationen:**
Siehe auch: `tools/docs/PLUGIN-STORE-CHECKLIST.md`

---

## 📁 Verzeichnisstruktur

```
woltlab-development/
│
├── tools.sh                    # Quick Access (im Hauptverzeichnis)
│
├── tools/                      # Hauptverzeichnis aller Tools
│   │
│   ├── tools.sh               # Zentrales Menü (Hauptskript)
│   ├── README.md              # Diese Datei
│   ├── common.sh              # Gemeinsame Funktionen (Farben, etc.)
│   │
│   ├── build.sh               # Plugin Builder
│   ├── gitpush.sh             # Git Push & Release
│   ├── typescript.sh          # TypeScript Kompilierung
│   ├── start-ddev.sh          # DDEV Manager
│   ├── dockge.sh              # Dockge Verwaltung
│   ├── migrate-to-dockge.sh   # Migration von Portainer zu Dockge
│   ├── validate-plugin.sh     # Plugin Validierung (Security & Compliance)
│   ├── restore-snapshot.sh   # Snapshot wiederherstellen
│   ├── setup.sh               # Vollständige Installation
│   ├── download-woltlab.sh   # WoltLab Core Download
│   ├── snapshot-manager.sh    # Snapshot-Verwaltung
│   ├── credentials.sh         # Zugangsdaten-Verwaltung
│   │
│   ├── .env.example           # Template für .env Datei
│   ├── .env                   # Deine Zugangsdaten (wird erstellt)
│   │
│   ├── docs/                  # Dokumentation
│   │   └── PLUGIN-STORE-CHECKLIST.md  # Plugin Store Checkliste
│   │
│   ├── woltlab-dev/           # DDEV Entwicklungsumgebung
│   │   ├── .ddev/             # DDEV Konfiguration
│   │   ├── public/            # WoltLab Installation (Dateien)
│   │   └── start.sh           # DDEV Start-Skript
│   │
│   ├── woltlab-snapshot/      # Snapshots (Backups)
│   │   ├── database.sql.gz    # Datenbank-Backup
│   │   ├── metadata.txt       # Metadaten
│   │   └── public/            # Dateien-Backup
│   │
│   └── woltlab-snapshot-tools/ # Snapshot-Tools
│       ├── snapshot.sh        # Snapshot erstellen
│       └── restore.sh         # Snapshot wiederherstellen
│
├── basis-plugin/              # Dein erstes Plugin
│   ├── package.xml           # Plugin-Definition
│   ├── lib/                  # PHP-Code
│   ├── templates/            # Templates
│   └── js/                   # JavaScript/TypeScript
│
├── mein-plugin/              # Weitere Plugins
│   └── extracted_plugin/     # Extrahierte Plugins
│
└── plugins-integrieren/      # Plugins zum Integrieren
    └── [verschiedene Plugins]
```

---

## 🔧 Was wird installiert?

### Automatisch durch Setup (Option 6):

1. **DDEV**
   - Version: Neueste stabile Version
   - Was: Lokale Entwicklungsumgebung
   - Größe: ~500MB
   - Benötigt: Docker

2. **Docker** (falls nicht vorhanden)
   - Version: Neueste stabile Version
   - Was: Container-Plattform
   - Größe: ~200MB
   - Benötigt: Administrator-Rechte

3. **HeidiSQL** (falls nicht vorhanden)
   - Version: Neueste stabile Version
   - Was: Datenbank-Verwaltungstool
   - Größe: ~50MB
   - Installation: Via pacman (Arch Linux)

4. **Node.js und npm**
   - Version: LTS (Long Term Support)
   - Was: JavaScript-Runtime und Package-Manager
   - Größe: ~100MB
   - Benötigt: Für TypeScript-Kompilierung

5. **WoltLab Core**
   - Version: Neueste verfügbare Version
   - Was: WoltLab Suite Installation
   - Größe: ~150MB (komprimiert)
   - Benötigt: Gültige WoltLab-Lizenz

### Manuell konfiguriert:

1. **.env Datei**
   - Enthält: Passwörter, Benutzernamen, Einstellungen
   - Wird erstellt: Durch Setup oder Credentials-Manager
   - Wichtig: Enthält sensible Daten!

2. **Git Repository**
   - Wird initialisiert: Durch Setup
   - Remote: Muss manuell konfiguriert werden
   - Benötigt: GitHub-Token in .env

---

## 🎓 Schritt-für-Schritt: Erste Verwendung

### Für absolute Anfänger:

#### Schritt 1: Setup durchführen
1. Öffne ein Terminal
2. Wechsle ins Verzeichnis: `cd ~/Dokumente/woltlab-development`
3. Starte das Menü: `./tools.sh`
4. Wähle Option `6` (Setup)
5. Folge den Anweisungen auf dem Bildschirm
6. Warte, bis alles installiert ist (5-15 Minuten)

#### Schritt 2: DDEV starten
1. Im Menü: Option `4` (DDEV) wählen
2. Leer lassen oder `start` eingeben
3. Warte, bis DDEV gestartet ist (~30 Sekunden)
4. Notiere dir die angezeigten URLs

#### Schritt 3: Plugin bauen
1. Im Menü: Option `1` (Build) wählen
2. Plugin-Name eingeben (z.B. `basis-plugin`) oder Leer lassen für Auto
3. Version-Typ wählen (Standard: `patch`)
4. Warte, bis der Build abgeschlossen ist

#### Schritt 4: Plugin installieren
1. Öffne die ACP-URL im Browser (aus Schritt 2)
2. Gehe zu: Pakete → Paket installieren
3. Lade die `.tar` Datei hoch (aus Schritt 3)
4. Folge der Installationsanleitung

#### Schritt 5: Snapshot erstellen
1. Im Menü: Option `8` (Snapshot Manager) wählen
2. Option `1` (Snapshot erstellen) wählen
3. Warte, bis der Snapshot erstellt ist (~10 Sekunden)

**Glückwunsch!** Du hast jetzt eine funktionierende Entwicklungsumgebung! 🎉

---

## ❓ Häufige Fragen (FAQ)

### Q: Was ist, wenn DDEV nicht startet?
**A:** 
1. Prüfe, ob Docker läuft: `sudo systemctl status docker`
2. Starte Docker: `sudo systemctl start docker`
3. Prüfe DDEV-Status: `cd tools/woltlab-dev && ddev describe`

### Q: Wo finde ich meine gebauten Plugins?
**A:** Im Plugin-Verzeichnis als `.tar` Datei, z.B. `basis-plugin/com.example.plugin.tar`

### Q: Wie ändere ich Passwörter?
**A:** 
1. Im Menü: Option `9` (Credentials) wählen
2. Option `1` (.env Datei erstellen) wählen
3. Oder bearbeite `tools/.env` manuell

### Q: Was ist, wenn TypeScript nicht kompiliert?
**A:**
1. Prüfe, ob Node.js installiert ist: `node --version`
2. Installiere Node.js: `sudo pacman -S nodejs npm`
3. Führe Setup erneut aus (Option 6)

### Q: Wie erstelle ich einen Snapshot?
**A:**
1. Im Menü: Option `8` (Snapshot Manager) wählen
2. Option `1` (Snapshot erstellen) wählen
3. Warte, bis fertig (~10 Sekunden)

### Q: Was ist, wenn Git Push fehlschlägt?
**A:**
1. Prüfe, ob GitHub-Token in `.env` gesetzt ist
2. Prüfe Git-Konfiguration: `git config --list`
3. Prüfe Internetverbindung

### Q: Wie öffne ich HeidiSQL?
**A:**
1. Starte HeidiSQL (im System-Menü oder Terminal: `heidisql`)
2. Die Verbindung "WoltLab DDEV" sollte automatisch vorhanden sein
3. Falls nicht: Im Menü Option `9` → Option `5` (HeidiSQL Passwort speichern)

### Q: Was ist Dockge und brauche ich es?
**A:** Dockge ist optional. Es bietet eine moderne Web-Oberfläche zur Verwaltung von Docker-Containern. Du kannst es verwenden, um DDEV visuell zu verwalten, aber es ist nicht zwingend notwendig. Dockge ist eine schnellere, modernere Alternative zu Portainer.

---

## 🐛 Fehlerbehebung

### Problem: "DDEV ist nicht installiert"
**Lösung:** Führe Setup aus (Option 6) oder installiere DDEV manuell:
```bash
curl -fsSL https://ddev.com/install.sh | bash
```

### Problem: "Docker läuft nicht"
**Lösung:** Starte Docker:
```bash
sudo systemctl start docker
sudo systemctl enable docker  # Für automatischen Start
```

### Problem: "Permission denied"
**Lösung:** Stelle sicher, dass die Skripte ausführbar sind:
```bash
chmod +x tools/*.sh
```

### Problem: "Ports werden nicht angezeigt"
**Lösung:** 
1. Prüfe, ob DDEV läuft: `cd tools/woltlab-dev && ddev describe`
2. Starte DDEV neu: `ddev restart`
3. Prüfe, ob jq installiert ist: `sudo pacman -S jq`

### Problem: "HeidiSQL findet keine Verbindung"
**Lösung:**
1. Stelle sicher, dass DDEV läuft
2. Im Menü: Option `9` → Option `5` (HeidiSQL Passwort speichern)
3. Starte HeidiSQL neu

---

## 💡 Tipps & Tricks

### Tipp 1: Regelmäßige Snapshots
Erstelle vor größeren Änderungen immer einen Snapshot. So kannst du schnell zurückkehren, falls etwas schiefgeht.

### Tipp 2: Versionen sinnvoll erhöhen
- **Patch**: Für kleine Bugfixes
- **Minor**: Für neue Features
- **Major**: Für große Änderungen oder Breaking Changes

### Tipp 3: Git Push mit sinnvollen Nachrichten
Auch wenn automatische Nachrichten generiert werden, kannst du eine eigene Nachricht eingeben, die besser beschreibt, was geändert wurde.

### Tipp 4: TypeScript Watch-Modus
Verwende den Watch-Modus beim Entwickeln, damit Änderungen automatisch kompiliert werden.

### Tipp 5: Dockge für visuelle Verwaltung
Wenn du Docker visuell verwalten möchtest, nutze Dockge. Es ist eine moderne, schnellere Alternative zu Portainer und macht viele Operationen einfacher.

---

## 📞 Support

Bei Problemen oder Fragen:
1. Prüfe diese README zuerst
2. Prüfe die Fehlerbehebung (siehe oben)
3. Prüfe die Logs: `cd tools/woltlab-dev && ddev logs`
4. Prüfe DDEV-Status: `cd tools/woltlab-dev && ddev describe`

---

## 📝 Changelog

### Version 2.1 (Aktuell)
- ✅ Plugin Validierung mit Security-Checks hinzugefügt
- ✅ Plugin Store Compliance-Prüfungen
- ✅ SQL-Injection & XSS-Detection
- ✅ Plugin Store Checkliste hinzugefügt

### Version 2.0
- ✅ Dockge-Integration hinzugefügt (Migration von Portainer)
- ✅ HeidiSQL automatische Konfiguration
- ✅ Verbesserte Plugin-Suche (auch in Unterverzeichnissen)
- ✅ Gruppierte Plugin-Anzeige
- ✅ Verbesserte Navigation mit Zurück-Optionen
- ✅ Robuste Port-Extraktion für DDEV
- ✅ Erweiterte .env Konfiguration

### Version 1.0
- ✅ Basis-Funktionalität
- ✅ Build, Git Push, DDEV, Snapshots
- ✅ Setup-Automatisierung

---

## 📄 Lizenz

Diese Tools sind für die private Entwicklung von WoltLab Plugins gedacht.

---

**Viel Erfolg bei der Entwicklung! 🚀**
