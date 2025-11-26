# Simple WoltLab Plugin Manager

<div align="center">

**🌍 Sprache / Language:** [🇩🇪 Deutsch](#) | [🇬🇧 English](README.md) | [⚙️ Advanced](docs/README_ADVANCED.md)

</div>

---

**Copyright (c) 2025 SunnyCueq**  
**Lizenz:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden. Dieses Projekt ist Open Source unter der MIT-Lizenz, aber die Copyright-Zuschreibung muss in allen Kopien und wesentlichen Teilen der Software erhalten bleiben.

---

**Letzte Aktualisierung:** 2025-11-26  
**Version:** 1.5.0  
**Status:** Aktuell

---

## 📖 Was ist das?

Der **Simple WoltLab Plugin Manager** ist ein kostenloses Toolkit, das dir hilft, Plugins für die WoltLab Suite zu entwickeln. 

**Für Einsteiger erklärt:**
- Ein **Plugin** erweitert die WoltLab Suite mit neuen Funktionen
- Dieses Toolkit hilft dir dabei, Plugins zu erstellen, zu testen und zu veröffentlichen
- Du musst kein Experte sein – das Toolkit führt dich Schritt für Schritt durch alles

**Was du bekommst:**
- ✅ Automatische Scripts zum Erstellen und Verpacken deiner Plugins
- ✅ Vorkonfigurierte Entwicklungsumgebung für deine IDE (Cursor/VSCode)
- ✅ Vollständiges Beispiel-Plugin zum Lernen
- ✅ Detaillierte Anleitungen in Deutsch und Englisch
- ✅ Einfache Installation mit einem einzigen Befehl

## 🚀 Schnellstart (4 einfache Schritte)

### Schritt 1: Was du brauchst

**Bevor du startest, lade dir diese Programme herunter:**

1. **Git** (zum Herunterladen des Projekts)
   - Download: https://git-scm.com/downloads
   - Wähle dein Betriebssystem (Windows, Mac, Linux)
   - Installiere Git nach dem Download

2. **Eine IDE** (Code-Editor - wähle einen):
   - **Cursor** (empfohlen): https://cursor.sh/ - Download und installieren
   - **VSCode** (Alternative): https://code.visualstudio.com/ - Download und installieren

**💡 Keine Sorge:** Falls du Git noch nicht hast, kannst du das Projekt auch als ZIP-Datei von GitHub herunterladen (siehe unten).

### Schritt 2: Projekt herunterladen

**Wo öffne ich ein Terminal?**

**Option A: Terminal im System öffnen**
- **Windows:** Drücke `Windows + R`, tippe `cmd` oder `powershell` und drücke Enter
- **Mac:** Drücke `Cmd + Leertaste`, tippe "Terminal" und drücke Enter
- **Linux:** Drücke `Ctrl + Alt + T` oder suche nach "Terminal" im Menü

**Option B: Terminal in der IDE öffnen (nach Installation)**
- **Cursor/VSCode:** Drücke `Ctrl + ~` (Windows/Linux) oder `Cmd + ~` (Mac)
- Oder: Menü → Terminal → Neues Terminal

**Jetzt führe diese Befehle aus:**

```bash
git clone https://github.com/SunnyCueq/simple-woltlab-plugin-manager.git
cd simple-woltlab-plugin-manager
```

**💡 Alternative ohne Git:**
1. Gehe zu: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
2. Klicke auf "Code" → "Download ZIP"
3. Entpacke die ZIP-Datei
4. Öffne ein Terminal im entpackten Ordner

### Schritt 3: Installation starten

**Wichtig:** Du musst im richtigen Verzeichnis sein! Falls du nicht sicher bist, führe zuerst aus:
```bash
cd simple-woltlab-plugin-manager
```

**Jetzt starte die Installation:**

```bash
./install.sh
```

**Was passiert dabei?**
- ✅ Das Script prüft, ob alle benötigten Programme installiert sind (PHP, Git, etc.)
- ✅ Es fragt dich nach den benötigten Pfaden (WoltLab Core, Plugin-Verzeichnis)
- ✅ Es erstellt automatisch die Konfigurationsdateien
- ✅ Es richtet deine Entwicklungsumgebung ein
- ✅ Es kopiert alle benötigten Scripts an die richtigen Stellen

**Du musst nur die Fragen beantworten – der Rest passiert automatisch!**

**💡 Falls der Befehl nicht funktioniert:**
- **Windows:** Versuche `bash install.sh` oder `sh install.sh`
- **Mac/Linux:** Stelle sicher, dass die Datei ausführbar ist: `chmod +x install.sh`

### Schritt 4: Entwicklungsumgebung öffnen

**Nach der Installation findest du eine Datei namens `woltlab-plugin-dev.code-workspace`**

**Wo finde ich diese Datei?**
- Meist im übergeordneten Verzeichnis deines Plugins
- Oder in deinem Home-Verzeichnis (`~/` auf Mac/Linux, `C:\Users\DeinName\` auf Windows)

**Wie öffne ich sie?**

**Option A: Über das Terminal (einfachste Methode)**

1. Öffne ein Terminal (siehe Schritt 2)
2. Navigiere zum Verzeichnis wo die `.code-workspace` Datei liegt:
   ```bash
   cd ~/Dokumente  # Beispiel - passe den Pfad an
   ```
3. Führe einen dieser Befehle aus:

```bash
# Mit Cursor (empfohlen)
cursor woltlab-plugin-dev.code-workspace

# Oder mit VSCode
code woltlab-plugin-dev.code-workspace
```

**Option B: Über den Datei-Explorer/Finder**

1. Öffne den Datei-Explorer (Windows) oder Finder (Mac)
2. Navigiere zum Verzeichnis wo die `.code-workspace` Datei liegt
3. **Doppelklicke** auf die Datei `woltlab-plugin-dev.code-workspace`
4. Falls sich nichts tut, klicke mit Rechtsklick → "Öffnen mit" → Cursor oder VSCode

**💡 Falls der Befehl nicht funktioniert:**
- Stelle sicher, dass Cursor/VSCode installiert ist
- Prüfe ob der Pfad zur Datei korrekt ist: `ls -la woltlab-plugin-dev.code-workspace`
- Versuche die Datei per Doppelklick zu öffnen (siehe Option B)

**Fertig!** 🎉 Du kannst jetzt mit der Plugin-Entwicklung beginnen.

## ✨ Was kann das Toolkit?

### Für Einsteiger

- **🎯 Einfache Installation:** Ein Script macht alles für dich
- **📚 Schritt-für-Schritt Anleitungen:** Alles wird genau erklärt
- **💡 Beispiel-Plugin:** Lerne anhand eines vollständigen Beispiels
- **🔧 Automatische Konfiguration:** Deine IDE wird automatisch eingerichtet

### Für Entwickler

- **📦 Generische Build-Scripts:** Automatisches Erstellen von TAR-Archiven und Releases
- **🏗️ Workspace-Templates:** Vorkonfigurierte Multi-Root Workspaces für Cursor/VSCode
- **🧠 IDE-Setup:** Automatische Konfiguration von Intelephense für WoltLab-Klassen
- **🚀 Release-Management:** Erstelle Releases mit einem einzigen Befehl
- **🔒 Security-Validierung:** Automatische Prüfung auf SQL-Injection und XSS-Risiken
- **✅ Plugin Store Compliance:** Prüft alle Plugin Store Anforderungen automatisch
- **🌍 Übersetzungs-Check:** Validiert Deutsch + Englisch (Pflicht)
- **🎯 API Best Practices:** Prüft auf WoltLab API-Nutzung (Cloud-kompatibel)
- **🧹 Quality-Checks:** Findet Debug-Code, Test-Credentials, ineffiziente Patterns
- **📖 Umfassende Dokumentation:** Anleitungen für verschiedene Betriebssysteme und IDEs (DE/EN)

## 📚 Dokumentation

### 📖 Für Einsteiger

**Beginne hier, wenn du neu bist:**

1. **[INSTALLATION.md](docs/INSTALLATION.md)** - 📥 Vollständige Installationsanleitung
   - Schritt-für-Schritt erklärt
   - Was du brauchst und wie du es installierst
   - Häufige Probleme und Lösungen

2. **[WORKSPACE-SETUP.md](docs/WORKSPACE-SETUP.md)** - 🏗️ Workspace-Konfiguration
   - Wie du deine Entwicklungsumgebung einrichtest
   - Was ist ein Workspace und warum brauchst du ihn?

### 🛠️ Für Fortgeschrittene

**Weitere Anleitungen:**

- **[IDE-SETUP-CURSOR.md](docs/IDE-SETUP-CURSOR.md)** - Cursor IDE Setup (DE)
- **[IDE-SETUP-CURSOR_EN.md](docs/IDE-SETUP-CURSOR_EN.md)** - Cursor IDE Setup (EN)
- **[IDE-SETUP-VSCODE.md](docs/IDE-SETUP-VSCODE.md)** - VSCode Setup (DE)
- **[IDE-SETUP-VSCODE_EN.md](docs/IDE-SETUP-VSCODE_EN.md)** - VSCode Setup (EN)

### 💻 Betriebssystem-spezifisch

- **[MACOS.md](docs/MACOS.md)** - macOS-spezifische Anleitung (DE)
- **[MACOS_EN.md](docs/MACOS_EN.md)** - macOS-specific guide (EN)
- **[LINUX-CACHYOS.md](docs/LINUX-CACHYOS.md)** - CachyOS-spezifische Anleitung (DE)
- **[LINUX-CACHYOS_EN.md](docs/LINUX-CACHYOS_EN.md)** - CachyOS-specific guide (EN)
- **[WINDOWS-WSL.md](docs/WINDOWS-WSL.md)** - Windows WSL Setup (DE)
- **[WINDOWS-WSL_EN.md](docs/WINDOWS-WSL_EN.md)** - Windows WSL Setup (EN)

### 🌍 Englische Versionen

- **[INSTALLATION_EN.md](docs/INSTALLATION_EN.md)** - Detailed installation guide (EN)
- **[WORKSPACE-SETUP_EN.md](docs/WORKSPACE-SETUP_EN.md)** - Workspace configuration (EN)

### 📚 Weitere Anleitungen

- **[VERSIONING.md](docs/VERSIONING.md)** - Versionsverwaltung und Semantic Versioning
- **[PLUGIN-NAMING.md](docs/PLUGIN-NAMING.md)** - Plugin-Namenskonventionen
- **[DEVELOPER-TOOLS.md](docs/DEVELOPER-TOOLS.md)** - Entwickler-Werkzeuge und Debug-Optionen
- **[PACKAGING.md](docs/PACKAGING.md)** - Kompletter Packaging-Workflow (Entwicklung → Package → Release)
- **[PIP-TYPES.md](docs/PIP-TYPES.md)** - Unterstützte PIP-Typen und Standard-Dateinamen
- **[PLUGIN-STORE-CHECKLIST.md](docs/PLUGIN-STORE-CHECKLIST.md)** - 🆕 Checkliste für Plugin Store Submission
- **[WOLTLAB-VERSIONS.md](docs/WOLTLAB-VERSIONS.md)** - 🆕 WoltLab 6.0/6.1/6.2 Kompatibilität

### ⚙️ Für Experten

- **[README_ADVANCED.md](docs/README_ADVANCED.md)** - Advanced technical documentation (EN only)
  - Architecture and design principles
  - Scripts reference and internals
  - Advanced usage and customization
  - Troubleshooting guide

## 🛠️ Scripts

### version.sh

Verwaltet Versionsnummern für das Repository (Semantic Versioning).

```bash
# Patch-Version erhöhen (Bugfix)
./scripts/version.sh patch

# Minor-Version erhöhen (Neues Feature)
./scripts/version.sh minor

# Major-Version erhöhen (Breaking Change)
./scripts/version.sh major

# Dry-Run (nur anzeigen)
./scripts/version.sh patch --dry-run
```

Siehe [VERSIONING.md](docs/VERSIONING.md) für Details.

### extract-plugin-files.sh

Entpackt TAR-Dateien in `_extracted/` Verzeichnis.

```bash
./scripts/extract-plugin-files.sh [PLUGIN_DIR]
```

### update-tars.sh

Erstellt TAR-Archive aus `_extracted/` Verzeichnis.

```bash
./scripts/update-tars.sh [PLUGIN_DIR]
```

### plugin-version.sh

Automatische Versionsverwaltung für Plugins. Erhöht die Version in `package.xml` und erstellt optional ein Package.

```bash
# Patch-Version erhöhen und Package erstellen
./scripts/plugin-version.sh patch

# Minor-Version erhöhen ohne Package zu erstellen
./scripts/plugin-version.sh minor --no-release

# Major-Version erhöhen für ein bestimmtes Plugin
./scripts/plugin-version.sh major /path/to/plugin
```

**Features:**
- ✅ Automatische Versionsaktualisierung in `package.xml`
- ✅ Automatische Datumsaktualisierung
- ✅ Optional: Automatisches Package-Erstellen
- ✅ Backup des letzten Packages

### create-release.sh

Erstellt Plugin-Package und optional GitHub Release. Aktualisiert automatisch die Version in `package.xml` und erstellt ein Backup des letzten Packages.

```bash
./scripts/create-release.sh VERSION [PLUGIN_DIR] [GITHUB_REPO]
```

Beispiel:
```bash
./scripts/create-release.sh 1.0.1 /path/to/plugin owner/repo-name
```

**Features:**
- ✅ **Automatisches package.xml Parsing** - Findet alle benötigten Dateien automatisch
- ✅ **XML-Syntax-Validierung** - Prüft package.xml auf Fehler
- ✅ **Package-Name-Format-Validierung** - Prüft Format (com.domain.pluginname)
- ✅ **Datei-Existenz-Prüfung** - Prüft ob alle benötigten Dateien vorhanden sind
- ✅ Automatische Versionsaktualisierung in `package.xml`
- ✅ Automatische Datumsaktualisierung
- ✅ Backup des letzten TAR-Archivs (in `.package-backups/`)
- ✅ Optional: GitHub Release erstellen
- ✅ Zeigt Package-Struktur vor dem Packen

### create-plugin.sh

Erstellt automatisch eine vollständige Plugin-Grundstruktur basierend auf dem Package-Identifier.

```bash
./scripts/create-plugin.sh PACKAGE_IDENTIFIER [TARGET_DIR]
```

Beispiel:
```bash
./scripts/create-plugin.sh com.example.myplugin
```

**Was wird erstellt:**
- ✅ Plugin-Verzeichnisstruktur (`files/`, `templates/`, `language/`)
- ✅ `package.xml` mit korrektem Identifier
- ✅ `page.xml` mit Beispiel-Seite
- ✅ PHP-Klasse (`ExamplePage.class.php`)
- ✅ Beispiel-Template (`example.tpl`)
- ✅ `README.md` mit Dokumentation

**Features:**
- ✅ Validierung des Package-Identifier-Formats
- ✅ Vollständige WoltLab-konforme Struktur
- ✅ Bereit für sofortige Entwicklung

## 📁 Struktur

```
simple-woltlab-plugin-manager/
├── scripts/              # Build- und Release-Scripts
├── templates/            # Workspace- und IDE-Templates
├── docs/                 # Dokumentation (DE/EN)
├── example-plugin/       # Beispiel-Plugin
├── install.sh            # Installations-Script
├── README.md             # Diese Datei (DE)
├── README_EN.md          # English README
└── LICENSE               # Lizenz
```

## 🔧 Was du brauchst

### Erforderlich (wird vom Install-Script geprüft)

- **PHP 8.0 oder höher** - Programmiersprache für WoltLab Plugins
  - Download: https://www.php.net/downloads.php
  - Oder installiere es über deinen Paket-Manager (siehe [INSTALLATION.md](docs/INSTALLATION.md))
- **Git** - Zum Herunterladen des Projekts
  - Download: https://git-scm.com/downloads
  - Wähle dein Betriebssystem und installiere Git
- **tar** - Meist bereits auf deinem System installiert

**💡 Keine Sorge:** Das Install-Script prüft automatisch, ob alles installiert ist und sagt dir, was fehlt!

### Empfohlen

- **Cursor IDE** (empfohlen) - Code-Editor (kostenlos)
  - Download: https://cursor.sh/
  - Installiere Cursor nach dem Download
- **VSCode** (Alternative) - Code-Editor (kostenlos)
  - Download: https://code.visualstudio.com/
  - Installiere VSCode nach dem Download
- **WoltLab Suite Core** - Für Referenz und Auto-Completion (wird während der Installation abgefragt)

### 📥 WoltLab Suite Core herunterladen

**Was ist das?**
Der WoltLab Suite Core ist die Basis-Software, für die du Plugins entwickelst. Du benötigst eine lokale Kopie als Referenz.

**Wie bekommst du ihn?**

1. **Aktuelle Version herunterladen:**
   - [WoltLab Suite Download-Seite](https://www.woltlab.com/de/woltlab-suite-download/)
   - Direkter Download: [woltlab-suite-6.1.14.zip](https://assets.woltlab.com/release/woltlab-suite-6.1.14.zip)

2. **ZIP-Datei entpacken:**
   - Entpacke die ZIP-Datei in einen Ordner deiner Wahl (z.B. `~/Dokumente/woltlab core` auf Mac/Linux oder `C:\Users\DeinName\Documents\woltlab core` auf Windows)
   - **Wichtig:** Merke dir den Pfad – das Install-Script fragt danach!

3. **Systemüberprüfung (optional):**
   - [Systemüberprüfungs-Skript](https://www.woltlab.com/media/302-test-php/) - Prüft, ob dein System alle Voraussetzungen erfüllt

**💡 Tipp:** Falls du den Core noch nicht hast, kannst du das Install-Script trotzdem starten. Es erinnert dich daran, ihn herunterzuladen.

## 📖 Beispiel-Plugin

**Lerne anhand eines echten Beispiels!**

Das Repository enthält ein vollständiges, funktionierendes Beispiel-Plugin in `example-plugin/`. 

**Was du lernst:**
- ✅ Wie ein Plugin aufgebaut ist
- ✅ Wie du eine neue Seite erstellst
- ✅ Wie du PHP-Klassen und Templates verwendest
- ✅ Die Struktur eines WoltLab Plugins

**Basierend auf:** [WoltLab Getting Started Dokumentation](https://docs.woltlab.com/6.0/getting-started/)

📄 Siehe [example-plugin/README.md](example-plugin/README.md) für Details.

## 🤝 Beitragen

Dieses Projekt ist Open Source und Community-getrieben. Beiträge sind willkommen!

**📖 Siehe [CONTRIBUTING.md](CONTRIBUTING.md) für detaillierte Informationen zum Beitragen.**

Kurzfassung:
1. Fork das Repository
2. Erstelle einen Feature-Branch
3. Committe deine Änderungen
4. Erstelle einen Pull Request

## 📝 Lizenz

Dieses Projekt ist unter der MIT-Lizenz lizenziert. Siehe [LICENSE](LICENSE) für Details.

## 🔗 Links

- [WoltLab Suite Dokumentation](https://docs.woltlab.com/6.0/)
- [WoltLab Getting Started](https://docs.woltlab.com/6.0/getting-started/)
- [WoltLab PHP API](https://docs.woltlab.com/6.0/api/)

## ❓ Hilfe & Support

**Hast du Fragen oder Probleme?**

1. **📖 Dokumentation prüfen:** Schau in die `docs/`-Ordner für detaillierte Anleitungen
2. **🐛 Problem melden:** Öffne ein [Issue auf GitHub](https://github.com/SunnyCueq/simple-woltlab-plugin-manager/issues)
3. **💬 Community:** Kontaktiere die WoltLab Community

**Häufige Fragen:**

- **"Das Install-Script funktioniert nicht"** → Siehe [Troubleshooting in INSTALLATION.md](docs/INSTALLATION.md#troubleshooting)
- **"Wie erstelle ich ein neues Plugin?"** → Siehe [Beispiel-Plugin](#-beispiel-plugin) und [WoltLab Getting Started](https://docs.woltlab.com/6.0/getting-started/)
- **"Wo finde ich die Dokumentation?"** → Siehe [Dokumentation](#-dokumentation) oben

---

<div align="center">

**Entwickelt mit ❤️ für die WoltLab Community**

[⬆️ Nach oben](#simple-woltlab-plugin-manager) | [🇬🇧 English Version](README.md)

</div>

