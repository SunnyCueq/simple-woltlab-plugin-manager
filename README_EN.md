# Simple WoltLab Plugin Manager

**Last Updated:** 2025-01-08  
**Version:** 1.0.0  
**Status:** Current

**Last Change:** Initial version
- Reason: Repository creation for WoltLab plugin development

---

A comprehensive toolkit for developing WoltLab Suite plugins with generic build scripts, workspace templates, and detailed installation guides.

## 🚀 Quick Start

1. **Clone the repository:**
   ```bash
   git clone https://github.com/SunnyCueq/simple-woltlab-plugin-manager.git
   cd simple-woltlab-plugin-manager
   ```

2. **Run installation:**
   ```bash
   ./install.sh
   ```

3. **Open workspace:**
   ```bash
   cursor woltlab-plugin-dev.code-workspace
   # or
   code woltlab-plugin-dev.code-workspace
   ```

## 📦 Features

- **Generic Build Scripts:** Automatic creation of TAR archives and releases
- **Workspace Templates:** Pre-configured multi-root workspaces for Cursor/VSCode
- **IDE Setup:** Automatic configuration of Intelephense for WoltLab classes
- **Install Script:** Interactive setup of the development environment
- **Example Plugin:** Complete example based on WoltLab Getting Started
- **Comprehensive Documentation:** Guides for different operating systems and IDEs

## 📚 Documentation

### Installation & Setup

- **[INSTALLATION.md](docs/INSTALLATION.md)** - Detailed installation guide (DE)
- **[INSTALLATION_EN.md](docs/INSTALLATION_EN.md)** - Detailed installation guide (EN)

### Workspace & IDE

- **[WORKSPACE-SETUP.md](docs/WORKSPACE-SETUP.md)** - Workspace configuration (DE)
- **[WORKSPACE-SETUP_EN.md](docs/WORKSPACE-SETUP_EN.md)** - Workspace configuration (EN)
- **[IDE-SETUP-CURSOR.md](docs/IDE-SETUP-CURSOR.md)** - Cursor IDE Setup (DE)
- **[IDE-SETUP-CURSOR_EN.md](docs/IDE-SETUP-CURSOR_EN.md)** - Cursor IDE Setup (EN)
- **[IDE-SETUP-VSCODE.md](docs/IDE-SETUP-VSCODE.md)** - VSCode Setup (DE)
- **[IDE-SETUP-VSCODE_EN.md](docs/IDE-SETUP-VSCODE_EN.md)** - VSCode Setup (EN)

### Operating System Specific

- **[LINUX-CACHYOS.md](docs/LINUX-CACHYOS.md)** - CachyOS-specific guide (DE)
- **[LINUX-CACHYOS_EN.md](docs/LINUX-CACHYOS_EN.md)** - CachyOS-specific guide (EN)
- **[WINDOWS-WSL.md](docs/WINDOWS-WSL.md)** - Windows WSL Setup (DE)
- **[WINDOWS-WSL_EN.md](docs/WINDOWS-WSL_EN.md)** - Windows WSL Setup (EN)

## 🛠️ Scripts

### extract-plugin-files.sh

Extracts TAR files into `_extracted/` directory.

```bash
./scripts/extract-plugin-files.sh [PLUGIN_DIR]
```

### update-tars.sh

Creates TAR archives from `_extracted/` directory.

```bash
./scripts/update-tars.sh [PLUGIN_DIR]
```

### create-release.sh

Creates plugin package and optionally GitHub release.

```bash
./scripts/create-release.sh VERSION [PLUGIN_DIR] [GITHUB_REPO]
```

Example:
```bash
./scripts/create-release.sh 1.0.0 /path/to/plugin owner/repo-name
```

### setup-workspace.sh

Creates multi-root workspace for IDE.

```bash
./scripts/setup-workspace.sh
```

## 📁 Structure

```
simple-woltlab-plugin-manager/
├── scripts/              # Build and release scripts
├── templates/            # Workspace and IDE templates
├── docs/                 # Documentation (DE/EN)
├── example-plugin/       # Example plugin
├── install.sh            # Installation script
├── README.md             # This file (DE)
├── README_EN.md          # English README
└── LICENSE               # License
```

## 🔧 Requirements

- PHP 8.0 or higher
- Git
- tar (usually pre-installed)
- Cursor or VSCode
- WoltLab Suite Core (for reference)

### Download WoltLab Suite Core

**Current Version:**
- [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- Direct Download: [woltlab-suite-6.1.14.zip](https://assets.woltlab.com/release/woltlab-suite-6.1.14.zip)

**Older Versions:**
- Example: [woltlab-suite-6.0.22.zip](https://assets.woltlab.com/release/woltlab-suite-6.0.22.zip)
- URL Pattern: `https://assets.woltlab.com/release/woltlab-suite-{VERSION}.zip`

**System Check:**
- [System Check Script](https://www.woltlab.com/media/302-test-php/) - Checks system requirements before installation

## 📖 Example Plugin

The repository contains a complete example plugin in `example-plugin/`, based on the [WoltLab Getting Started Documentation](https://docs.woltlab.com/6.0/getting-started/).

See [example-plugin/README.md](example-plugin/README.md) for details.

## 🤝 Contributing

This project is open source and community-driven. Contributions are welcome!

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Create a pull request

## 📝 License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## 🔗 Links

- [WoltLab Suite Documentation](https://docs.woltlab.com/6.0/)
- [WoltLab Getting Started](https://docs.woltlab.com/6.0/getting-started/)
- [WoltLab PHP API](https://docs.woltlab.com/6.0/api/)

## 💡 Support

For questions or issues:
- Open an issue on GitHub
- Check the documentation in `docs/`
- Contact the community

---

**Developed with ❤️ for the WoltLab Community**

