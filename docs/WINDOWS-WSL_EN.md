# Windows WSL - Simple WoltLab Plugin Manager

**Last Updated:** 2025-01-08  
**Status:** Current

**Last Change:** Initial version
- Reason: Creation of Windows WSL guide

---

This guide explains how to set up the Simple WoltLab Plugin Manager on Windows with WSL (Windows Subsystem for Linux).

## Prerequisites

### Install WSL

1. **Enable WSL:**
   ```powershell
   wsl --install
   ```

2. **Choose distribution:**
   - Ubuntu (recommended)
   - Debian
   - Or other Linux distribution

3. **Start WSL:**
   ```powershell
   wsl
   ```

### Install PHP (in WSL)

```bash
sudo apt update
sudo apt install php php-cli php-xml php-mbstring
```

### Install Git (in WSL)

```bash
sudo apt install git
```

### tar (usually pre-installed)

```bash
tar --version
```

## Directory Structure

WSL uses Linux paths. Windows drives are mounted under `/mnt/`:

```
/mnt/c/Users/YourName/Documents/
├── woltlab core/
├── my-plugin/
└── woltlab-plugin-dev.code-workspace
```

### Windows Paths in WSL

Windows paths are converted like this:
- `C:\Users\Benny\Documents` → `/mnt/c/Users/Benny/Documents`
- `D:\Projects` → `/mnt/d/Projects`

## Installation

1. **Open WSL:**
   ```powershell
   wsl
   ```

2. **Clone repository:**
   ```bash
   cd /mnt/c/Users/YourName/Documents
   git clone https://github.com/your-username/simple-woltlab-plugin-manager.git
   cd simple-woltlab-plugin-manager
   ```

3. **Run installation script:**
   ```bash
   chmod +x install.sh
   ./install.sh
   ```

## IDE Setup

### Cursor/VSCode in Windows

The IDE runs in Windows but accesses WSL files:

1. **Install Cursor/VSCode in Windows**
2. **Install WSL Extension:**
   - `Remote - WSL` Extension
3. **Open workspace:**
   ```bash
   # In WSL
   code /mnt/c/Users/YourName/Documents/woltlab-plugin-dev.code-workspace
   ```

### Path Adjustments

WSL paths must be adjusted in workspace:

```json
{
  "folders": [
    {
      "name": "🎯 My Plugin",
      "path": "/mnt/c/Users/YourName/Documents/my-plugin"
    }
  ]
}
```

## Running Scripts in WSL

All scripts must be run in WSL:

```bash
# In WSL
cd /mnt/c/Users/YourName/Documents/my-plugin
./extract-plugin-files.sh
./update-tars.sh
```

## Troubleshooting

### "PHP not found"

Check PHP path in WSL:
```bash
which php
```

If not found:
```bash
sudo apt install php
```

### "Permission denied"

```bash
chmod +x scripts/*.sh
```

### Windows paths don't work

Always use WSL paths (`/mnt/c/...`) instead of Windows paths (`C:\...`).

### IDE can't find files

Make sure IDE is running in WSL mode (see "Remote - WSL" Extension).

## Best Practices

1. **WSL for everything:** Run all scripts in WSL
2. **Windows for IDE:** Use Windows for Cursor/VSCode
3. **WSL Extension:** Use Remote-WSL Extension
4. **Paths:** Always use WSL paths (`/mnt/c/...`)

## Further Information

- **[INSTALLATION_EN.md](INSTALLATION_EN.md)** - General installation guide
- **[IDE-SETUP-CURSOR_EN.md](IDE-SETUP-CURSOR_EN.md)** - Cursor setup
- [WSL Documentation](https://docs.microsoft.com/en-us/windows/wsl/)

