# Linux CachyOS - Simple WoltLab Plugin Manager

**Last Updated:** 2025-11-08  
**Status:** Current

**Last Change:** Initial version
- Reason: Creation of CachyOS-specific guide

---

This guide is specifically optimized for CachyOS (Arch-based), but also works with other Arch-based distributions.

## Prerequisites

### Install PHP

```bash
sudo pacman -S php
```

Check version:
```bash
php --version
```

For PHP 8.4 (if available):
```bash
sudo pacman -S php84
```

### Install Git

```bash
sudo pacman -S git
```

### tar (usually pre-installed)

```bash
tar --version
```

If not available:
```bash
sudo pacman -S tar
```

## Install Cursor IDE

### Option 1: AUR (Recommended)

```bash
yay -S cursor-bin
# or
paru -S cursor-bin
```

### Option 2: Manual

```bash
# Download from https://cursor.sh
# Extract and install
```

### Option 3: VSCode (Alternative)

```bash
sudo pacman -S code
```

## Recommended Directory Structure

Recommended structure for CachyOS:

```
~/Documents/
├── woltlab core/              # WoltLab Suite Core
├── my-plugin/                  # Your plugin
├── base-plugin/                # Main plugin (optional)
└── woltlab-plugin-dev.code-workspace
```

### Download WoltLab Core

If you don't have WoltLab Core yet:

```bash
cd ~/Documents
# Download and extract WoltLab Suite
# Or clone from GitHub:
git clone https://github.com/WoltLab/WCF.git "woltlab core"
cd "woltlab core"
git checkout 6.0  # Or corresponding version
```

## Installation

1. **Clone repository:**
   ```bash
   cd ~/Documents
   git clone https://github.com/your-username/simple-woltlab-plugin-manager.git
   cd simple-woltlab-plugin-manager
   ```

2. **Run installation script:**
   ```bash
   chmod +x install.sh
   ./install.sh
   ```

3. **Open workspace:**
   ```bash
   cursor ~/Documents/woltlab-plugin-dev.code-workspace
   ```

## Package Manager Specific Commands

### Install PHP Extensions

If needed:
```bash
sudo pacman -S php-gd php-mysql php-xml
```

### GitHub CLI (for releases)

```bash
sudo pacman -S github-cli
```

## Troubleshooting

### "PHP not found"

Check PHP path:
```bash
which php
# Should show: /usr/bin/php
```

If not, add PHP to PATH or use full path in workspace.

### "Permission denied" on scripts

```bash
chmod +x scripts/*.sh
chmod +x install.sh
```

### Intelephense Cache

If having issues with auto-completion:
```bash
rm -rf ~/.cache/intelephense/
```

## Performance Tips

CachyOS is optimized for performance. For even better IDE performance:

1. **Use SSD:** WoltLab Core on SSD
2. **RAM:** At least 8GB recommended
3. **Swap:** If needed, create swap file

## Further Information

- **[INSTALLATION_EN.md](INSTALLATION_EN.md)** - General installation guide
- **[IDE-SETUP-CURSOR_EN.md](IDE-SETUP-CURSOR_EN.md)** - Cursor setup
- [CachyOS Documentation](https://cachyos.org/)

