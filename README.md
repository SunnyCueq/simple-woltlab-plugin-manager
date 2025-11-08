# Simple WoltLab Plugin Manager

<div align="center">

**🌍 Sprache / Language:** [🇩🇪 Deutsch](#) | [🇬🇧 English](README_EN.md) | [⚙️ Advanced](README_ADVANCED.md)

</div>

---

**Copyright (c) 2025 SunnyCueq**  
**Lizenz:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden. Dieses Projekt ist Open Source unter der MIT-Lizenz, aber die Copyright-Zuschreibung muss in allen Kopien und wesentlichen Teilen der Software erhalten bleiben.

---

**Letzte Aktualisierung:** 2025-01-08  
**Version:** 1.0.0  
**Status:** Aktuell

---

## 📖 Was ist das?

Der **Simple WoltLab Plugin Manager** ist ein kostenloses Toolkit, das Ihnen hilft, Plugins für die WoltLab Suite zu entwickeln. 

**Für Einsteiger erklärt:**
- Ein **Plugin** erweitert die WoltLab Suite mit neuen Funktionen
- Dieses Toolkit hilft Ihnen dabei, Plugins zu erstellen, zu testen und zu veröffentlichen
- Sie müssen kein Experte sein – das Toolkit führt Sie Schritt für Schritt durch alles

**Was Sie bekommen:**
- ✅ Automatische Scripts zum Erstellen und Verpacken Ihrer Plugins
- ✅ Vorkonfigurierte Entwicklungsumgebung für Ihre IDE (Cursor/VSCode)
- ✅ Vollständiges Beispiel-Plugin zum Lernen
- ✅ Detaillierte Anleitungen in Deutsch und Englisch
- ✅ Einfache Installation mit einem einzigen Befehl

## 🚀 Schnellstart (3 einfache Schritte)

### Schritt 1: Projekt herunterladen

Öffnen Sie ein Terminal (auf Linux/Mac) oder PowerShell (auf Windows) und führen Sie aus:

```bash
git clone https://github.com/SunnyCueq/simple-woltlab-plugin-manager.git
cd simple-woltlab-plugin-manager
```

**💡 Tipp:** Falls Sie Git nicht installiert haben, können Sie das Projekt auch als ZIP-Datei von GitHub herunterladen.

### Schritt 2: Installation starten

Das Installations-Script führt Sie **automatisch durch alles** – von A bis Z:

```bash
./install.sh
```

**Was passiert dabei?**
- ✅ Das Script prüft, ob alle benötigten Programme installiert sind (PHP, Git, etc.)
- ✅ Es fragt Sie nach den benötigten Pfaden (WoltLab Core, Plugin-Verzeichnis)
- ✅ Es erstellt automatisch die Konfigurationsdateien
- ✅ Es richtet Ihre Entwicklungsumgebung ein
- ✅ Es kopiert alle benötigten Scripts an die richtigen Stellen

**Sie müssen nur die Fragen beantworten – der Rest passiert automatisch!**

### Schritt 3: Entwicklungsumgebung öffnen

Nach der Installation öffnen Sie einfach das erstellte Workspace-File:

```bash
# Mit Cursor (empfohlen)
cursor woltlab-plugin-dev.code-workspace

# Oder mit VSCode
code woltlab-plugin-dev.code-workspace
```

**Fertig!** 🎉 Sie können jetzt mit der Plugin-Entwicklung beginnen.

## ✨ Was kann das Toolkit?

### Für Einsteiger

- **🎯 Einfache Installation:** Ein Script macht alles für Sie
- **📚 Schritt-für-Schritt Anleitungen:** Alles wird genau erklärt
- **💡 Beispiel-Plugin:** Lernen Sie anhand eines vollständigen Beispiels
- **🔧 Automatische Konfiguration:** Ihre IDE wird automatisch eingerichtet

### Für Entwickler

- **📦 Generische Build-Scripts:** Automatisches Erstellen von TAR-Archiven und Releases
- **🏗️ Workspace-Templates:** Vorkonfigurierte Multi-Root Workspaces für Cursor/VSCode
- **🧠 IDE-Setup:** Automatische Konfiguration von Intelephense für WoltLab-Klassen
- **🚀 Release-Management:** Erstellen Sie Releases mit einem einzigen Befehl
- **📖 Umfassende Dokumentation:** Anleitungen für verschiedene Betriebssysteme und IDEs (DE/EN)

## 📚 Dokumentation

### 📖 Für Einsteiger

**Beginnen Sie hier, wenn Sie neu sind:**

1. **[INSTALLATION.md](docs/INSTALLATION.md)** - 📥 Vollständige Installationsanleitung
   - Schritt-für-Schritt erklärt
   - Was Sie brauchen und wie Sie es installieren
   - Häufige Probleme und Lösungen

2. **[WORKSPACE-SETUP.md](docs/WORKSPACE-SETUP.md)** - 🏗️ Workspace-Konfiguration
   - Wie Sie Ihre Entwicklungsumgebung einrichten
   - Was ist ein Workspace und warum brauchen Sie ihn?

### 🛠️ Für Fortgeschrittene

**Weitere Anleitungen:**

- **[IDE-SETUP-CURSOR.md](docs/IDE-SETUP-CURSOR.md)** - Cursor IDE Setup (DE)
- **[IDE-SETUP-CURSOR_EN.md](docs/IDE-SETUP-CURSOR_EN.md)** - Cursor IDE Setup (EN)
- **[IDE-SETUP-VSCODE.md](docs/IDE-SETUP-VSCODE.md)** - VSCode Setup (DE)
- **[IDE-SETUP-VSCODE_EN.md](docs/IDE-SETUP-VSCODE_EN.md)** - VSCode Setup (EN)

### 💻 Betriebssystem-spezifisch

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

### ⚙️ Für Experten

- **[README_ADVANCED.md](README_ADVANCED.md)** - Advanced technical documentation (EN only)
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

### create-release.sh

Erstellt Plugin-Package und optional GitHub Release.

```bash
./scripts/create-release.sh VERSION [PLUGIN_DIR] [GITHUB_REPO]
```

Beispiel:
```bash
./scripts/create-release.sh 1.0.0 /path/to/plugin owner/repo-name
```

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

## 🔧 Was Sie brauchen

### Erforderlich (wird vom Install-Script geprüft)

- **PHP 8.0 oder höher** - Programmiersprache für WoltLab Plugins
- **Git** - Zum Herunterladen des Projekts
- **tar** - Meist bereits auf Ihrem System installiert

**💡 Keine Sorge:** Das Install-Script prüft automatisch, ob alles installiert ist und sagt Ihnen, was fehlt!

### Empfohlen

- **Cursor IDE** oder **VSCode** - Code-Editor (kostenlos)
- **WoltLab Suite Core** - Für Referenz und Auto-Completion (wird während der Installation abgefragt)

### 📥 WoltLab Suite Core herunterladen

**Was ist das?**
Der WoltLab Suite Core ist die Basis-Software, für die Sie Plugins entwickeln. Sie benötigen eine lokale Kopie als Referenz.

**Wie bekomme ich ihn?**

1. **Aktuelle Version herunterladen:**
   - [WoltLab Suite Download-Seite](https://www.woltlab.com/de/woltlab-suite-download/)
   - Direkter Download: [woltlab-suite-6.1.14.zip](https://assets.woltlab.com/release/woltlab-suite-6.1.14.zip)

2. **ZIP-Datei entpacken:**
   - Entpacken Sie die ZIP-Datei in einen Ordner Ihrer Wahl (z.B. `~/Dokumente/woltlab core`)
   - **Wichtig:** Merken Sie sich den Pfad – das Install-Script fragt danach!

3. **Systemüberprüfung (optional):**
   - [Systemüberprüfungs-Skript](https://www.woltlab.com/media/302-test-php/) - Prüft, ob Ihr System alle Voraussetzungen erfüllt

**💡 Tipp:** Falls Sie den Core noch nicht haben, können Sie das Install-Script trotzdem starten. Es erinnert Sie daran, ihn herunterzuladen.

## 📖 Beispiel-Plugin

**Lernen Sie anhand eines echten Beispiels!**

Das Repository enthält ein vollständiges, funktionierendes Beispiel-Plugin in `example-plugin/`. 

**Was Sie lernen:**
- ✅ Wie ein Plugin aufgebaut ist
- ✅ Wie Sie eine neue Seite erstellen
- ✅ Wie Sie PHP-Klassen und Templates verwenden
- ✅ Die Struktur eines WoltLab Plugins

**Basierend auf:** [WoltLab Getting Started Dokumentation](https://docs.woltlab.com/6.0/getting-started/)

📄 Siehe [example-plugin/README.md](example-plugin/README.md) für Details.

## 🤝 Beitragen

Dieses Projekt ist Open Source und Community-getrieben. Beiträge sind willkommen!

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

**Haben Sie Fragen oder Probleme?**

1. **📖 Dokumentation prüfen:** Schauen Sie in die `docs/`-Ordner für detaillierte Anleitungen
2. **🐛 Problem melden:** Öffnen Sie ein [Issue auf GitHub](https://github.com/SunnyCueq/simple-woltlab-plugin-manager/issues)
3. **💬 Community:** Kontaktieren Sie die WoltLab Community

**Häufige Fragen:**

- **"Das Install-Script funktioniert nicht"** → Siehe [Troubleshooting in INSTALLATION.md](docs/INSTALLATION.md#troubleshooting)
- **"Wie erstelle ich ein neues Plugin?"** → Siehe [Beispiel-Plugin](#-beispiel-plugin) und [WoltLab Getting Started](https://docs.woltlab.com/6.0/getting-started/)
- **"Wo finde ich die Dokumentation?"** → Siehe [Dokumentation](#-dokumentation) oben

---

<div align="center">

**Entwickelt mit ❤️ für die WoltLab Community**

[⬆️ Nach oben](#simple-woltlab-plugin-manager) | [🇬🇧 English Version](README_EN.md)

</div>

