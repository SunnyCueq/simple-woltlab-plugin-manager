# Simple WoltLab Plugin Manager

<div align="center">

**🌍 Language / Sprache:** [🇬🇧 English](#) | [🇩🇪 Deutsch](README.md) | [⚙️ Advanced](README_ADVANCED.md)

</div>

---

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ IMPORTANT:** This copyright notice must not be removed. This project is open source under the MIT License, but the copyright attribution must be preserved in all copies and substantial portions of the software.

---

**Last Updated:** 2025-01-08  
**Version:** 1.0.0  
**Status:** Current

---

## 📖 What is this?

The **Simple WoltLab Plugin Manager** is a free toolkit that helps you develop plugins for the WoltLab Suite.

**For beginners explained:**
- A **plugin** extends the WoltLab Suite with new features
- This toolkit helps you create, test, and publish plugins
- You don't need to be an expert – the toolkit guides you step by step through everything

**What you get:**
- ✅ Automatic scripts to create and package your plugins
- ✅ Pre-configured development environment for your IDE (Cursor/VSCode)
- ✅ Complete example plugin to learn from
- ✅ Detailed guides in English and German
- ✅ Easy installation with a single command

## 🚀 Quick Start (3 Simple Steps)

### Step 1: Download the Project

Open a terminal (on Linux/Mac) or PowerShell (on Windows) and run:

```bash
git clone https://github.com/SunnyCueq/simple-woltlab-plugin-manager.git
cd simple-woltlab-plugin-manager
```

**💡 Tip:** If you don't have Git installed, you can also download the project as a ZIP file from GitHub.

### Step 2: Start Installation

The installation script guides you **automatically through everything** – from A to Z:

```bash
./install.sh
```

**What happens?**
- ✅ The script checks if all required programs are installed (PHP, Git, etc.)
- ✅ It asks you for the required paths (WoltLab Core, plugin directory)
- ✅ It automatically creates configuration files
- ✅ It sets up your development environment
- ✅ It copies all required scripts to the right places

**You only need to answer the questions – the rest happens automatically!**

### Step 3: Open Development Environment

After installation, simply open the created workspace file:

```bash
# With Cursor (recommended)
cursor woltlab-plugin-dev.code-workspace

# Or with VSCode
code woltlab-plugin-dev.code-workspace
```

**Done!** 🎉 You can now start developing plugins.

## ✨ What can this toolkit do?

### For Beginners

- **🎯 Easy Installation:** One script does everything for you
- **📚 Step-by-step Guides:** Everything is explained in detail
- **💡 Example Plugin:** Learn from a complete example
- **🔧 Automatic Configuration:** Your IDE is set up automatically

### For Developers

- **📦 Generic Build Scripts:** Automatic creation of TAR archives and releases
- **🏗️ Workspace Templates:** Pre-configured multi-root workspaces for Cursor/VSCode
- **🧠 IDE Setup:** Automatic configuration of Intelephense for WoltLab classes
- **🚀 Release Management:** Create releases with a single command
- **📖 Comprehensive Documentation:** Guides for different operating systems and IDEs (EN/DE)

## 📚 Documentation

### 📖 For Beginners

**Start here if you're new:**

1. **[INSTALLATION_EN.md](docs/INSTALLATION_EN.md)** - 📥 Complete Installation Guide
   - Step-by-step explained
   - What you need and how to install it
   - Common problems and solutions

2. **[WORKSPACE-SETUP_EN.md](docs/WORKSPACE-SETUP_EN.md)** - 🏗️ Workspace Configuration
   - How to set up your development environment
   - What is a workspace and why do you need it?

### 🛠️ For Advanced Users

**Additional Guides:**

- **[IDE-SETUP-CURSOR_EN.md](docs/IDE-SETUP-CURSOR_EN.md)** - Cursor IDE Setup (EN)
- **[IDE-SETUP-CURSOR.md](docs/IDE-SETUP-CURSOR.md)** - Cursor IDE Setup (DE)
- **[IDE-SETUP-VSCODE_EN.md](docs/IDE-SETUP-VSCODE_EN.md)** - VSCode Setup (EN)
- **[IDE-SETUP-VSCODE.md](docs/IDE-SETUP-VSCODE.md)** - VSCode Setup (DE)

### 💻 Operating System Specific

- **[LINUX-CACHYOS_EN.md](docs/LINUX-CACHYOS_EN.md)** - CachyOS-specific guide (EN)
- **[LINUX-CACHYOS.md](docs/LINUX-CACHYOS.md)** - CachyOS-specific guide (DE)
- **[WINDOWS-WSL_EN.md](docs/WINDOWS-WSL_EN.md)** - Windows WSL Setup (EN)
- **[WINDOWS-WSL.md](docs/WINDOWS-WSL.md)** - Windows WSL Setup (DE)

### 🇩🇪 German Versions

- **[INSTALLATION.md](docs/INSTALLATION.md)** - Detaillierte Installationsanleitung (DE)
- **[WORKSPACE-SETUP.md](docs/WORKSPACE-SETUP.md)** - Workspace-Konfiguration (DE)

### 📚 Additional Guides

- **[VERSIONING.md](docs/VERSIONING.md)** - Version management and Semantic Versioning
- **[PLUGIN-NAMING.md](docs/PLUGIN-NAMING.md)** - Plugin naming conventions
- **[DEVELOPER-TOOLS.md](docs/DEVELOPER-TOOLS.md)** - Developer tools and debug options

### ⚙️ For Experts

- **[README_ADVANCED.md](README_ADVANCED.md)** - Advanced technical documentation (EN only)
  - Architecture and design principles
  - Scripts reference and internals
  - Advanced usage and customization
  - Troubleshooting guide

## 🛠️ Scripts

### version.sh

Manages version numbers for the repository (Semantic Versioning).

```bash
# Increment patch version (bugfix)
./scripts/version.sh patch

# Increment minor version (new feature)
./scripts/version.sh minor

# Increment major version (breaking change)
./scripts/version.sh major

# Dry-run (show only)
./scripts/version.sh patch --dry-run
```

See [VERSIONING.md](docs/VERSIONING.md) for details.

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

### plugin-version.sh

Automatic version management for plugins. Increments the version in `package.xml` and optionally creates a package.

```bash
# Increment patch version and create package
./scripts/plugin-version.sh patch

# Increment minor version without creating package
./scripts/plugin-version.sh minor --no-release

# Increment major version for a specific plugin
./scripts/plugin-version.sh major /path/to/plugin
```

**Features:**
- ✅ Automatic version update in `package.xml`
- ✅ Automatic date update
- ✅ Optional: Automatic package creation
- ✅ Backup of last package

### create-release.sh

Creates plugin package and optionally GitHub release. Automatically updates the version in `package.xml` and creates a backup of the last package.

```bash
./scripts/create-release.sh VERSION [PLUGIN_DIR] [GITHUB_REPO]
```

Example:
```bash
./scripts/create-release.sh 1.0.1 /path/to/plugin owner/repo-name
```

**Features:**
- ✅ Automatic version update in `package.xml`
- ✅ Automatic date update
- ✅ Backup of last TAR archive (in `.package-backups/`)
- ✅ Optional: GitHub release creation

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

## 🔧 What You Need

### Required (checked by install script)

- **PHP 8.0 or higher** - Programming language for WoltLab plugins
- **Git** - To download the project
- **tar** - Usually already installed on your system

**💡 Don't worry:** The install script automatically checks if everything is installed and tells you what's missing!

### Recommended

- **Cursor IDE** or **VSCode** - Code editor (free)
- **WoltLab Suite Core** - For reference and auto-completion (asked during installation)

### 📥 Download WoltLab Suite Core

**What is this?**
The WoltLab Suite Core is the base software for which you develop plugins. You need a local copy as a reference.

**How do I get it?**

1. **Download current version:**
   - [WoltLab Suite Download Page](https://www.woltlab.com/de/woltlab-suite-download/)
   - Direct Download: [woltlab-suite-6.1.14.zip](https://assets.woltlab.com/release/woltlab-suite-6.1.14.zip)

2. **Extract ZIP file:**
   - Extract the ZIP file to a folder of your choice (e.g., `~/Documents/woltlab core`)
   - **Important:** Remember the path – the install script will ask for it!

3. **System Check (optional):**
   - [System Check Script](https://www.woltlab.com/media/302-test-php/) - Checks if your system meets all requirements

**💡 Tip:** If you don't have the Core yet, you can still start the install script. It will remind you to download it.

## 📖 Example Plugin

**Learn from a real example!**

The repository contains a complete, working example plugin in `example-plugin/`.

**What you'll learn:**
- ✅ How a plugin is structured
- ✅ How to create a new page
- ✅ How to use PHP classes and templates
- ✅ The structure of a WoltLab plugin

**Based on:** [WoltLab Getting Started Documentation](https://docs.woltlab.com/6.0/getting-started/)

📄 See [example-plugin/README.md](example-plugin/README.md) for details.

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

## ❓ Help & Support

**Have questions or problems?**

1. **📖 Check Documentation:** Look in the `docs/` folders for detailed guides
2. **🐛 Report Issue:** Open an [Issue on GitHub](https://github.com/SunnyCueq/simple-woltlab-plugin-manager/issues)
3. **💬 Community:** Contact the WoltLab community

**Frequently Asked Questions:**

- **"The install script doesn't work"** → See [Troubleshooting in INSTALLATION_EN.md](docs/INSTALLATION_EN.md#troubleshooting)
- **"How do I create a new plugin?"** → See [Example Plugin](#-example-plugin) and [WoltLab Getting Started](https://docs.woltlab.com/6.0/getting-started/)
- **"Where can I find the documentation?"** → See [Documentation](#-documentation) above

---

<div align="center">

**Developed with ❤️ for the WoltLab Community**

[⬆️ Back to top](#simple-woltlab-plugin-manager) | [🇩🇪 Deutsche Version](README.md)

</div>

