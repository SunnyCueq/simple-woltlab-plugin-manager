# Simple WoltLab Plugin Manager

**Letzte Aktualisierung:** 2025-01-08  
**Version:** 1.0.0  
**Status:** Aktuell

**Letzte Änderung:** Initiale Version
- Grund: Erstellung des Repository für WoltLab Plugin-Entwicklung

---

Ein umfassendes Toolkit für die Entwicklung von WoltLab Suite Plugins mit generischen Build-Scripts, Workspace-Templates und detaillierten Installationsanleitungen.

## 🚀 Schnellstart

1. **Repository klonen:**
   ```bash
   git clone https://github.com/your-username/simple-woltlab-plugin-manager.git
   cd simple-woltlab-plugin-manager
   ```

2. **Installation ausführen:**
   ```bash
   ./install.sh
   ```

3. **Workspace öffnen:**
   ```bash
   cursor woltlab-plugin-dev.code-workspace
   # oder
   code woltlab-plugin-dev.code-workspace
   ```

## 📦 Features

- **Generische Build-Scripts:** Automatisches Erstellen von TAR-Archiven und Releases
- **Workspace-Templates:** Vorkonfigurierte Multi-Root Workspaces für Cursor/VSCode
- **IDE-Setup:** Automatische Konfiguration von Intelephense für WoltLab-Klassen
- **Install-Script:** Interaktive Einrichtung der Entwicklungsumgebung
- **Beispiel-Plugin:** Vollständiges Beispiel basierend auf WoltLab Getting Started
- **Umfassende Dokumentation:** Anleitungen für verschiedene Betriebssysteme und IDEs

## 📚 Dokumentation

### Installation & Setup

- **[INSTALLATION.md](docs/INSTALLATION.md)** - Detaillierte Installationsanleitung (DE)
- **[INSTALLATION_EN.md](docs/INSTALLATION_EN.md)** - Detailed installation guide (EN)

### Workspace & IDE

- **[WORKSPACE-SETUP.md](docs/WORKSPACE-SETUP.md)** - Workspace-Konfiguration (DE)
- **[WORKSPACE-SETUP_EN.md](docs/WORKSPACE-SETUP_EN.md)** - Workspace configuration (EN)
- **[IDE-SETUP-CURSOR.md](docs/IDE-SETUP-CURSOR.md)** - Cursor IDE Setup (DE)
- **[IDE-SETUP-CURSOR_EN.md](docs/IDE-SETUP-CURSOR_EN.md)** - Cursor IDE Setup (EN)
- **[IDE-SETUP-VSCODE.md](docs/IDE-SETUP-VSCODE.md)** - VSCode Setup (DE)
- **[IDE-SETUP-VSCODE_EN.md](docs/IDE-SETUP-VSCODE_EN.md)** - VSCode Setup (EN)

### Betriebssystem-spezifisch

- **[LINUX-CACHYOS.md](docs/LINUX-CACHYOS.md)** - CachyOS-spezifische Anleitung (DE)
- **[LINUX-CACHYOS_EN.md](docs/LINUX-CACHYOS_EN.md)** - CachyOS-specific guide (EN)
- **[WINDOWS-WSL.md](docs/WINDOWS-WSL.md)** - Windows WSL Setup (DE)
- **[WINDOWS-WSL_EN.md](docs/WINDOWS-WSL_EN.md)** - Windows WSL Setup (EN)

## 🛠️ Scripts

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

### setup-workspace.sh

Erstellt Multi-Root Workspace für die IDE.

```bash
./scripts/setup-workspace.sh
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

## 🔧 Voraussetzungen

- PHP 8.0 oder höher
- Git
- tar (meist vorinstalliert)
- Cursor oder VSCode
- WoltLab Suite Core (für Referenz)

### WoltLab Suite Core herunterladen

**Aktuelle Version:**
- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- Direkter Download: [woltlab-suite-6.1.14.zip](https://assets.woltlab.com/release/woltlab-suite-6.1.14.zip)

**Ältere Versionen:**
- Beispiel: [woltlab-suite-6.0.22.zip](https://assets.woltlab.com/release/woltlab-suite-6.0.22.zip)
- URL-Pattern: `https://assets.woltlab.com/release/woltlab-suite-{VERSION}.zip`

**Systemüberprüfung:**
- [Systemüberprüfungs-Skript](https://www.woltlab.com/media/302-test-php/) - Prüft Systemvoraussetzungen vor Installation

## 📖 Beispiel-Plugin

Das Repository enthält ein vollständiges Beispiel-Plugin in `example-plugin/`, basierend auf der [WoltLab Getting Started Dokumentation](https://docs.woltlab.com/6.0/getting-started/).

Siehe [example-plugin/README.md](example-plugin/README.md) für Details.

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

## 💡 Support

Bei Fragen oder Problemen:
- Öffne ein Issue auf GitHub
- Schau in die Dokumentation in `docs/`
- Kontaktiere die Community

---

**Entwickelt mit ❤️ für die WoltLab Community**

