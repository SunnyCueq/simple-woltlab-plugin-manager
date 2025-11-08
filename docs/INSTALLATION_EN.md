# Installation - Simple WoltLab Plugin Manager

**Last Updated:** 2025-01-08  
**Status:** Current

**Last Change:** Initial version
- Reason: Creation of installation guide

---

This guide will walk you through the installation and setup of the Simple WoltLab Plugin Manager step by step.

## Prerequisites

Before you begin, make sure the following software is installed:

### Required

- **PHP 8.0 or higher**
  ```bash
  php --version
  ```
  
- **Git**
  ```bash
  git --version
  ```
  
- **tar** (usually pre-installed)
  ```bash
  tar --version
  ```

### Optional (but recommended)

- **Cursor IDE** or **VSCode**
- **WoltLab Suite Core** (for reference and auto-completion)

### Download WoltLab Suite Core

For plugin development, you need a local copy of WoltLab Suite Core.

**Current Version:**
- Download: [WoltLab Suite Download](https://www.woltlab.com/de/woltlab-suite-download/)
- Direct Download (latest version): [woltlab-suite-6.1.14.zip](https://assets.woltlab.com/release/woltlab-suite-6.1.14.zip)

**Older Versions:**
If you want to work with an older version, you can download specific versions:
- Example: [woltlab-suite-6.0.22.zip](https://assets.woltlab.com/release/woltlab-suite-6.0.22.zip)
- URL Pattern: `https://assets.woltlab.com/release/woltlab-suite-{VERSION}.zip`

**System Check:**
Before installing WoltLab Suite, we recommend using the system check script:
- Download: [test.php](https://www.woltlab.com/media/302-test-php/)
- Upload the file to your server and open it in your browser
- The script checks all system requirements for WoltLab Suite

## Step 1: Clone Repository

```bash
git clone https://github.com/your-username/simple-woltlab-plugin-manager.git
cd simple-woltlab-plugin-manager
```

## Step 2: Run Installation Script

The installation script will guide you interactively through the setup:

```bash
./install.sh
```

The script will ask you for:

1. **WoltLab Core Directory:** Path to your WoltLab Suite Core installation
2. **Plugin Directory:** Path to your plugin directory (or empty for new plugin)
3. **Main Plugins:** Optional paths to main plugins that serve as reference

### Example Answers

```
WoltLab Core Directory: /home/user/Documents/woltlab core
Plugin Directory: /home/user/Documents/my-plugin
Main Plugin 1: /home/user/Documents/base-plugin
```

## Step 3: Open Workspace

After installation, a workspace file is created. Open it in your IDE:

```bash
# Cursor
cursor woltlab-plugin-dev.code-workspace

# VSCode
code woltlab-plugin-dev.code-workspace
```

## Step 4: Install IDE Extensions

The recommended extensions will be automatically suggested:

- **Intelephense** - PHP Auto-Completion
- **Xdebug** - PHP Debugging
- **EditorConfig** - Code Formatting

Install these extensions and restart your IDE.

## Step 5: Copy Scripts to Plugin Directory

If you have an existing plugin, the scripts were already copied. If not:

```bash
cp scripts/*.sh /path/to/your/plugin/
chmod +x /path/to/your/plugin/*.sh
```

## Step 6: Verify Configuration

The configuration is saved in `~/.woltlab-config`. You can edit it anytime:

```bash
cat ~/.woltlab-config
```

## Directory Structure

After installation, your directory structure should look something like this:

```
~/Documents/
├── woltlab core/              # WoltLab Suite Core
├── my-plugin/                # Your plugin
│   ├── extract-plugin-files.sh
│   ├── update-tars.sh
│   ├── create-release.sh
│   └── ...
└── woltlab-plugin-dev.code-workspace  # Workspace file
```

## Next Steps

- **[WORKSPACE-SETUP_EN.md](WORKSPACE-SETUP_EN.md)** - Workspace configuration in detail
- **[IDE-SETUP-CURSOR_EN.md](IDE-SETUP-CURSOR_EN.md)** - Cursor IDE Setup
- **[IDE-SETUP-VSCODE_EN.md](IDE-SETUP-VSCODE_EN.md)** - VSCode Setup
- **[LINUX-CACHYOS_EN.md](LINUX-CACHYOS_EN.md)** - CachyOS-specific guide

## Troubleshooting

### "PHP not found"

Install PHP:
```bash
# CachyOS/Arch
sudo pacman -S php

# Ubuntu/Debian
sudo apt install php

# macOS
brew install php
```

### "Git not found"

Install Git:
```bash
# CachyOS/Arch
sudo pacman -S git

# Ubuntu/Debian
sudo apt install git

# macOS
brew install git
```

### "Workspace won't open"

Check if the path is correct:
```bash
ls -la woltlab-plugin-dev.code-workspace
```

If the file doesn't exist, run `./install.sh` again.

### "Intelephense shows no auto-completion"

1. Check `intelephense.environment.includePaths` in workspace
2. Restart IDE
3. Clear Intelephense cache: `rm -rf ~/.cache/intelephense/`

## Further Help

- See [README_EN.md](../README_EN.md) for an overview
- Open an issue on GitHub for problems
- Contact the community

