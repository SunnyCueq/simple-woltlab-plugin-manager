# Simple WoltLab Plugin Manager - Advanced Documentation

<div align="center">

**🌍 Language:** [🇬🇧 English](#) | [🇩🇪 Deutsch](../README.md) | [📖 Standard Documentation](../README_EN.md)

</div>

---

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ IMPORTANT:** This copyright notice must not be removed. This project is open source under the MIT License, but the copyright attribution must be preserved in all copies and substantial portions of the software.

---

## Overview

This document provides technical documentation for experienced developers who want to understand the internals, architecture, and advanced usage of the Simple WoltLab Plugin Manager.

## Table of Contents

1. [Architecture](#architecture)
2. [Scripts Reference](#scripts-reference)
3. [Configuration System](#configuration-system)
4. [Workspace Structure](#workspace-structure)
5. [Automated Installation](#automated-installation)
6. [Advanced Usage](#advanced-usage)
7. [Extending the Toolkit](#extending-the-toolkit)
8. [Troubleshooting](#troubleshooting)

---

## Architecture

### Project Structure

```
simple-woltlab-plugin-manager/
├── scripts/                      # Build and release automation
│   ├── extract-plugin-files.sh  # TAR extraction utility
│   ├── update-tars.sh           # TAR archive builder
│   ├── create-release.sh        # Release packaging and GitHub integration
│   ├── parse-package-xml.sh    # Dynamic package.xml parser
│   ├── pip-defaults.sh          # PIP type to filename mapping
│   └── setup-workspace.sh       # Workspace generator (deprecated, integrated in install.sh)
├── templates/                    # IDE and workspace templates
│   ├── workspace.code-workspace # Multi-root workspace template
│   └── .vscode/
│       └── settings.json        # Intelephense configuration template
├── docs/                        # Documentation (DE/EN)
├── example-plugin/              # Reference implementation
├── install.sh                   # Automated installation script
└── .woltlab-config             # User configuration (generated)
```

### Design Principles

1. **Generic Scripts:** All scripts are designed to work with any WoltLab plugin structure
2. **Configuration-Driven:** Settings stored in `~/.woltlab-config` for persistence
3. **Multi-Root Workspace:** Enables simultaneous access to plugin, core, and reference plugins
4. **Automated Setup:** Minimal manual intervention required

---

## Scripts Reference

### extract-plugin-files.sh

**Purpose:** Extracts TAR archives from WoltLab plugin packages into a standardized `_extracted/` directory.

**Usage:**
```bash
./extract-plugin-files.sh [PLUGIN_DIR]
```

**Behavior:**
- Scans for `.tar` files in the plugin directory
- Extracts to `_extracted/` subdirectory
- Preserves directory structure
- Generic: works with any plugin structure

**Implementation Details:**
- Uses `tar -xf` for extraction
- Creates `_extracted/` if it doesn't exist
- Handles multiple TAR files sequentially

### update-tars.sh

**Purpose:** Creates TAR archives from the `_extracted/` directory structure.

**Usage:**
```bash
./update-tars.sh [PLUGIN_DIR]
```

**Behavior:**
- Reads plugin structure from `_extracted/`
- Creates TAR archives matching WoltLab package format
- Preserves file permissions and structure

**Implementation Details:**
- Uses `tar -cf` for archive creation
- Maintains WoltLab package structure conventions
- Handles nested directories correctly

### create-release.sh

**Purpose:** Packages plugin for distribution and optionally creates GitHub release.

**Usage:**
```bash
./create-release.sh VERSION [PLUGIN_DIR] [GITHUB_REPO]
```

**Parameters:**
- `VERSION`: Semantic version (e.g., `1.0.0`)
- `PLUGIN_DIR`: Optional, defaults to configured plugin directory
- `GITHUB_REPO`: Optional, format: `owner/repo-name`

**Behavior:**
- **Automatically parses `package.xml`** to find all required files
- Analyzes `<instruction>` tags to determine needed files
- Shows package structure before packaging
- Creates `{plugin}-{version}.tar.gz` package
- Validates plugin structure
- Optionally uses GitHub CLI for release creation
- Updates version in `package.xml` if present

**Implementation Details:**
- Uses `parse-package-xml.sh` for dynamic file discovery
- Uses `pip-defaults.sh` for PIP type to filename mapping
- Case-insensitive file search
- Uses `tar -czf` for compressed archives
- Integrates with `gh` CLI if available
- Validates package structure before packaging

**New Features (v1.0.1+):**
- Dynamic `package.xml` parsing (no static file lists)
- Automatic PIP type recognition
- Tree structure output
- Support for all standard WoltLab PIP types

### install.sh

**Purpose:** Automated setup of the development environment.

**Features:**
- Dependency detection and installation
- Interactive configuration
- Workspace generation
- Script deployment

**See [Automated Installation](#automated-installation) for details.**

---

## Configuration System

### Configuration File: `~/.woltlab-config`

**Location:** `$HOME/.woltlab-config`  
**Permissions:** `600` (read/write for owner only)  
**Format:** Shell script with environment variables

**Structure:**
```bash
# WoltLab Plugin Development Configuration
# Generated by install.sh

WOLTLAB_CORE="/path/to/woltlab/core"
PLUGIN_DIR="/path/to/plugin"

# Optional: Reference plugins
MAIN_PLUGIN_1="/path/to/reference/plugin1"
MAIN_PLUGIN_2="/path/to/reference/plugin2"
```

**Usage in Scripts:**
```bash
source ~/.woltlab-config
# Variables are now available
```

**Security:**
- File permissions restrict access to owner
- Contains absolute paths (potential information disclosure)
- Should not be committed to version control

---

## Workspace Structure

### Multi-Root Workspace

The generated workspace (`woltlab-plugin-dev.code-workspace`) is a JSON file defining multiple root folders for the IDE.

**Structure:**
```json
{
  "folders": [
    {
      "name": "🎯 Mein Plugin",
      "path": "/path/to/plugin"
    },
    {
      "name": "🔧 WoltLab Suite Core (Read-Only Referenz)",
      "path": "/path/to/core"
    }
  ],
  "settings": { ... },
  "extensions": { ... }
}
```

### Intelephense Configuration

**Purpose:** Enable auto-completion for WoltLab classes.

**Key Settings:**
- `intelephense.environment.includePaths`: Points to WoltLab Core `lib/` directory
- `intelephense.environment.phpVersion`: Set to `8.4.0` (or your PHP version)
- Diagnostics disabled for undefined types (WoltLab uses dynamic class loading)

**Why Read-Only?**
- Prevents accidental modifications to core files
- Clear visual indication in IDE
- Maintains separation of concerns

---

## Automated Installation

### Dependency Management

The `install.sh` script automatically handles:

1. **Dependency Detection:**
   - Checks for PHP, Git, tar, curl, unzip
   - Validates versions where applicable

2. **Automatic Installation (when possible):**
   - **Linux (Arch/CachyOS):** Uses `pacman` with sudo
   - **Linux (Debian/Ubuntu):** Uses `apt` with sudo
   - **macOS:** Uses `brew` (requires Homebrew)
   - **Windows:** Provides manual instructions

3. **Post-Installation Verification:**
   - Re-checks installed dependencies
   - Validates functionality (e.g., `php --version`)

### WoltLab Core Download

**Automated Process:**
1. Detects if Core is missing
2. Prompts for download confirmation
3. Downloads latest version from WoltLab CDN
4. Extracts to specified directory
5. Validates extraction success
6. Updates configuration

**Manual Override:**
- Users can provide existing Core path
- Script validates path before proceeding

### Plugin Directory Setup

**Automatic Actions:**
- Creates plugin directory if it doesn't exist
- Copies build scripts to plugin directory
- Sets executable permissions
- Creates initial structure if needed

**Manual Setup:**
- Users can provide existing plugin path
- Script validates structure

---

## Advanced Usage

### Custom Workspace Configuration

Edit `woltlab-plugin-dev.code-workspace` directly:

```json
{
  "folders": [
    {
      "name": "Custom Name",
      "path": "/custom/path",
      "settings": {
        "files.exclude": {
          "**/.git": true,
          "**/node_modules": true
        }
      }
    }
  ]
}
```

### Environment Variables

Scripts respect these environment variables:

- `WOLTLAB_CORE`: Override core path
- `PLUGIN_DIR`: Override plugin directory
- `WOLTLAB_VERSION`: Specify WoltLab version for Core download

### Batch Operations

Process multiple plugins:

```bash
for plugin in /path/to/plugins/*; do
    ./create-release.sh 1.0.0 "$plugin"
done
```

### CI/CD Integration

Example GitHub Actions workflow:

```yaml
- name: Create Release
  run: |
    ./scripts/create-release.sh ${{ github.ref_name }} ${{ env.PLUGIN_DIR }}
```

---

## Extending the Toolkit

### Adding New Scripts

1. Create script in `scripts/` directory
2. Make executable: `chmod +x scripts/new-script.sh`
3. Source configuration: `source ~/.woltlab-config`
4. Document in this file

### Custom Templates

Modify templates in `templates/`:
- `workspace.code-workspace`: Workspace structure
- `.vscode/settings.json`: IDE settings

Changes will be reflected in generated files.

### Plugin Structure Validation

Add validation to scripts:

```bash
validate_plugin_structure() {
    local plugin_dir="$1"
    [ -f "$plugin_dir/package.xml" ] || return 1
    [ -d "$plugin_dir/files" ] || return 1
    return 0
}
```

---

## Troubleshooting

### Configuration Issues

**Problem:** Scripts can't find configuration  
**Solution:**
```bash
source ~/.woltlab-config
# Or re-run install.sh
```

**Problem:** Wrong paths in configuration  
**Solution:**
```bash
# Edit manually
nano ~/.woltlab-config
# Or re-run install.sh
```

### Workspace Issues

**Problem:** Intelephense not working  
**Solution:**
1. Check `intelephense.environment.includePaths` in workspace
2. Verify Core path is correct
3. Restart IDE
4. Clear Intelephense cache: `rm -rf ~/.cache/intelephense/`

**Problem:** Workspace not loading  
**Solution:**
1. Validate JSON syntax: `cat woltlab-plugin-dev.code-workspace | jq .`
2. Check all paths exist
3. Verify file permissions

### Script Execution Issues

**Problem:** Permission denied  
**Solution:**
```bash
chmod +x scripts/*.sh
```

**Problem:** Script fails silently  
**Solution:**
- Add `set -e` for error handling
- Add `set -x` for debugging
- Check exit codes

### Dependency Issues

**Problem:** Automatic installation fails  
**Solution:**
- Check sudo permissions
- Verify package manager availability
- Review error messages for specific issues
- Fall back to manual installation instructions

---

## Technical Specifications

### Supported Systems

- **Linux:** Arch, CachyOS, Debian, Ubuntu (and derivatives)
- **macOS:** 10.15+ (with Homebrew)
- **Windows:** WSL 2 (Ubuntu/Debian)

### Requirements

- **PHP:** 8.0+ (detected automatically)
- **Git:** Any recent version
- **tar:** Standard Unix utility
- **curl:** For Core download
- **unzip:** For Core extraction

### File Permissions

- Configuration: `600` (owner read/write)
- Scripts: `755` (executable)
- Workspace: `644` (readable)

---

## Contributing

This project is open source. When contributing:

1. **Preserve Copyright:** The copyright notice must remain in all files
2. **Follow Structure:** Maintain existing architecture
3. **Document Changes:** Update this file for significant changes
4. **Test Thoroughly:** Test on multiple systems

---

## License

MIT License

Copyright (c) 2025 SunnyCueq

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

---

<div align="center">

**Developed with ❤️ by SunnyCueq for the WoltLab Community**

[⬆️ Back to top](#simple-woltlab-plugin-manager---advanced-documentation) | [📖 Standard Documentation](README_EN.md) | [🇩🇪 Deutsch](README.md)

</div>

