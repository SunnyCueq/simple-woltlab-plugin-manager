# Windows WSL - Simple WoltLab Plugin Manager

**Letzte Aktualisierung:** 2025-01-08  
**Status:** Aktuell

**Letzte Änderung:** Initiale Version
- Grund: Erstellung der Windows WSL Anleitung

---

Diese Anleitung erklärt die Einrichtung des Simple WoltLab Plugin Managers unter Windows mit WSL (Windows Subsystem for Linux).

## Voraussetzungen

### WSL installieren

1. **WSL aktivieren:**
   ```powershell
   wsl --install
   ```

2. **Distribution wählen:**
   - Ubuntu (empfohlen)
   - Debian
   - Oder andere Linux-Distribution

3. **WSL starten:**
   ```powershell
   wsl
   ```

### PHP installieren (in WSL)

```bash
sudo apt update
sudo apt install php php-cli php-xml php-mbstring
```

### Git installieren (in WSL)

```bash
sudo apt install git
```

### tar (meist vorinstalliert)

```bash
tar --version
```

## Verzeichnisstruktur

WSL verwendet Linux-Pfade. Windows-Laufwerke sind unter `/mnt/` gemountet:

```
/mnt/c/Users/YourName/Documents/
├── woltlab core/
├── mein-plugin/
└── woltlab-plugin-dev.code-workspace
```

### Windows-Pfade in WSL

Windows-Pfade werden so konvertiert:
- `C:\Users\Benny\Documents` → `/mnt/c/Users/Benny/Documents`
- `D:\Projects` → `/mnt/d/Projects`

## Installation

1. **WSL öffnen:**
   ```powershell
   wsl
   ```

2. **Repository klonen:**
   ```bash
   cd /mnt/c/Users/YourName/Documents
   git clone https://github.com/your-username/simple-woltlab-plugin-manager.git
   cd simple-woltlab-plugin-manager
   ```

3. **Installations-Script ausführen:**
   ```bash
   chmod +x install.sh
   ./install.sh
   ```

## IDE Setup

### Cursor/VSCode in Windows

Die IDE läuft in Windows, aber greift auf WSL-Dateien zu:

1. **Cursor/VSCode in Windows installieren**
2. **WSL Extension installieren:**
   - `Remote - WSL` Extension
3. **Workspace öffnen:**
   ```bash
   # In WSL
   code /mnt/c/Users/YourName/Documents/woltlab-plugin-dev.code-workspace
   ```

### Pfad-Anpassungen

WSL-Pfade müssen im Workspace angepasst werden:

```json
{
  "folders": [
    {
      "name": "🎯 Mein Plugin",
      "path": "/mnt/c/Users/YourName/Documents/mein-plugin"
    }
  ]
}
```

## Scripts in WSL ausführen

Alle Scripts müssen in WSL ausgeführt werden:

```bash
# In WSL
cd /mnt/c/Users/YourName/Documents/mein-plugin
./extract-plugin-files.sh
./update-tars.sh
```

## Troubleshooting

### "PHP nicht gefunden"

Prüfe den PHP-Pfad in WSL:
```bash
which php
```

Falls nicht gefunden:
```bash
sudo apt install php
```

### "Permission denied"

```bash
chmod +x scripts/*.sh
```

### Windows-Pfade funktionieren nicht

Verwende immer WSL-Pfade (`/mnt/c/...`) statt Windows-Pfade (`C:\...`).

### IDE findet Dateien nicht

Stelle sicher, dass die IDE im WSL-Modus läuft (siehe "Remote - WSL" Extension).

## Best Practices

1. **WSL für alles:** Führe alle Scripts in WSL aus
2. **Windows für IDE:** Verwende Windows für Cursor/VSCode
3. **WSL Extension:** Verwende die Remote-WSL Extension
4. **Pfade:** Verwende immer WSL-Pfade (`/mnt/c/...`)

## Weitere Informationen

- **[INSTALLATION.md](INSTALLATION.md)** - Allgemeine Installationsanleitung
- **[IDE-SETUP-CURSOR.md](IDE-SETUP-CURSOR.md)** - Cursor Setup
- [WSL Dokumentation](https://docs.microsoft.com/en-us/windows/wsl/)

