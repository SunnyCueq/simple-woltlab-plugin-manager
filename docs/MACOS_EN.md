# macOS Installation - Simple WoltLab Plugin Manager

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ IMPORTANT:** This copyright notice must not be removed.

---

## Overview

This guide walks you through the installation on macOS step by step.

---

## Prerequisites

### 1. Install Homebrew (recommended)

Homebrew is a package manager for macOS that simplifies installing programs.

**Installation:**
1. Open Terminal (see below)
2. Run this command:
   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```
3. Follow the on-screen instructions

**💡 Alternative:** If you don't want to use Homebrew, you can install programs manually (see below).

### 2. Open Terminal

**Where do I find Terminal on Mac?**

**Option A: Via Spotlight (fastest method)**
1. Press `Cmd + Space`
2. Type "Terminal"
3. Press Enter

**Option B: Via Finder**
1. Open Finder
2. Go to: Applications → Utilities
3. Double-click "Terminal"

**Option C: Via Launchpad**
1. Open Launchpad (F4 key or swipe gesture)
2. Type "Terminal"
3. Click on Terminal

---

## Installing Prerequisites

### Install PHP

**With Homebrew (recommended):**
```bash
brew install php
```

**Manually:**
1. Go to: https://www.php.net/downloads.php
2. Select macOS and download the latest version
3. Install PHP after downloading

**Check if PHP is installed:**
```bash
php --version
```

**💡 If PHP is not found:**
- Make sure PHP is in your PATH
- Restart Terminal
- Check: `which php`

### Install Git

**With Homebrew (recommended):**
```bash
brew install git
```

**Manually:**
1. Go to: https://git-scm.com/download/mac
2. Download Git for macOS
3. Install Git after downloading

**Check if Git is installed:**
```bash
git --version
```

**💡 If Git is not found:**
- macOS sometimes has an older Git version pre-installed
- Install the latest version via Homebrew or the official website

### Install Cursor or VSCode

**Cursor (recommended):**
1. Go to: https://cursor.sh/
2. Click "Download for Mac"
3. Open the downloaded `.dmg` file
4. Drag Cursor to the Applications folder
5. Open Cursor from the Applications folder

**VSCode (Alternative):**
1. Go to: https://code.visualstudio.com/
2. Click "Download for Mac"
3. Open the downloaded `.zip` file
4. Drag VSCode to the Applications folder
5. Open VSCode from the Applications folder

**💡 Enable Terminal commands:**
After installation, you need to enable terminal commands:
- **Cursor:** Open Cursor → Settings → Search for "Shell Command" → Enable "Install 'cursor' command"
- **VSCode:** Open VSCode → Press `Cmd + Shift + P` → Type "Shell Command" → Select "Install 'code' command"

---

## Download Project

### Option A: With Git (recommended)

1. Open Terminal (see above)
2. Navigate to a directory of your choice:
   ```bash
   cd ~/Documents
   ```
3. Clone the repository:
   ```bash
   git clone https://github.com/SunnyCueq/simple-woltlab-plugin-manager.git
   cd simple-woltlab-plugin-manager
   ```

### Option B: As ZIP file

1. Go to: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
2. Click "Code" → "Download ZIP"
3. Extract the ZIP file (double-click)
4. Open Terminal in the extracted folder:
   - Right-click on the folder → "Services" → "New Terminal at Folder"
   - Or: Open Terminal and `cd` to the folder

---

## Start Installation

1. Make sure you're in the right directory:
   ```bash
   cd ~/Documents/simple-woltlab-plugin-manager
   # Or wherever you downloaded the project
   ```

2. Start the installation:
   ```bash
   ./install.sh
   ```

**💡 If the command doesn't work:**
- Make sure the file is executable: `chmod +x install.sh`
- Try: `bash install.sh` or `sh install.sh`

---

## Open Workspace

After installation, you'll find a file named `woltlab-plugin-dev.code-workspace`.

**Where do I find it?**
- Usually in `~/Documents/` or in the parent directory of your plugin

**How do I open it?**

**Option A: Via Terminal**
```bash
cd ~/Documents  # Or wherever the file is
cursor woltlab-plugin-dev.code-workspace
# Or:
code woltlab-plugin-dev.code-workspace
```

**Option B: Via Finder**
1. Open Finder
2. Navigate to the directory where the `.code-workspace` file is located
3. Double-click the file
4. If nothing happens: Right-click → "Open With" → Cursor or VSCode

**💡 If the command doesn't work:**
- Make sure Cursor/VSCode is installed
- Enable terminal commands (see above)
- Try opening the file by double-clicking

---

## macOS-Specific Tips

### Paths on macOS

- **Home directory:** `~/` or `/Users/YourName/`
- **Documents:** `~/Documents/`
- **Downloads:** `~/Downloads/`
- **Applications:** `/Applications/`

### Terminal Shortcuts

- **New Terminal:** `Cmd + T`
- **Close Terminal:** `Cmd + W`
- **Quit Terminal:** `Cmd + Q`
- **Cancel command:** `Ctrl + C`
- **Clear Terminal:** `Cmd + K`

### Permissions

If you have permission issues:
```bash
# Make file executable
chmod +x install.sh

# Make scripts executable
chmod +x scripts/*.sh
```

---

## Troubleshooting

### "Command not found"

**Problem:** Terminal can't find a command

**Solution:**
1. Check if the program is installed: `which php` (or `which git`, etc.)
2. Make sure PATH is correct: `echo $PATH`
3. Restart Terminal
4. With Homebrew: Run `brew doctor`

### "Permission denied"

**Problem:** File is not executable

**Solution:**
```bash
chmod +x filename.sh
```

### "Homebrew not found"

**Problem:** Homebrew commands don't work

**Solution:**
1. Install Homebrew (see above)
2. Check installation: `brew doctor`
3. Make sure Homebrew is in PATH

### Workspace won't open

**Problem:** Double-click on `.code-workspace` file doesn't work

**Solution:**
1. Right-click → "Open With" → Cursor or VSCode
2. Or: Enable terminal commands and open via Terminal
3. Check if Cursor/VSCode is installed

---

## Next Steps

- **[INSTALLATION_EN.md](INSTALLATION_EN.md)** - Complete installation guide
- **[WORKSPACE-SETUP_EN.md](WORKSPACE-SETUP_EN.md)** - Workspace configuration
- **[IDE-SETUP-CURSOR_EN.md](IDE-SETUP-CURSOR_EN.md)** - Cursor IDE Setup
- **[IDE-SETUP-VSCODE_EN.md](IDE-SETUP-VSCODE_EN.md)** - VSCode Setup

---

**Last Updated:** 2025-01-08

