# Linux CachyOS - Simple WoltLab Plugin Manager

**Letzte Aktualisierung:** 2025-11-08  
**Status:** Aktuell

**Letzte Änderung:** Initiale Version
- Grund: Erstellung der CachyOS-spezifischen Anleitung

---

Diese Anleitung ist speziell für CachyOS (Arch-basiert) optimiert, funktioniert aber auch mit anderen Arch-basierten Distributionen.

## Voraussetzungen

### PHP installieren

```bash
sudo pacman -S php
```

Prüfe die Version:
```bash
php --version
```

Für PHP 8.4 (falls verfügbar):
```bash
sudo pacman -S php84
```

### Git installieren

```bash
sudo pacman -S git
```

### tar (meist vorinstalliert)

```bash
tar --version
```

Falls nicht vorhanden:
```bash
sudo pacman -S tar
```

## Cursor IDE installieren

### Option 1: AUR (Empfohlen)

```bash
yay -S cursor-bin
# oder
paru -S cursor-bin
```

### Option 2: Manuell

```bash
# Download von https://cursor.sh
# Entpacken und installieren
```

### Option 3: VSCode (Alternative)

```bash
sudo pacman -S code
```

## Verzeichnisstruktur-Empfehlungen

Empfohlene Struktur für CachyOS:

```
~/Dokumente/
├── woltlab core/              # WoltLab Suite Core
├── mein-plugin/              # Ihr Plugin
├── basis-plugin/             # Hauptplugin (optional)
└── woltlab-plugin-dev.code-workspace
```

### WoltLab Core herunterladen

Falls du WoltLab Core noch nicht hast:

```bash
cd ~/Dokumente
# WoltLab Suite herunterladen und entpacken
# Oder von GitHub klonen:
git clone https://github.com/WoltLab/WCF.git "woltlab core"
cd "woltlab core"
git checkout 6.0  # Oder entsprechende Version
```

## Installation

1. **Repository klonen:**
   ```bash
   cd ~/Dokumente
   git clone https://github.com/your-username/simple-woltlab-plugin-manager.git
   cd simple-woltlab-plugin-manager
   ```

2. **Installations-Script ausführen:**
   ```bash
   chmod +x install.sh
   ./install.sh
   ```

3. **Workspace öffnen:**
   ```bash
   cursor ~/Dokumente/woltlab-plugin-dev.code-workspace
   ```

## Paket-Manager-spezifische Befehle

### PHP-Extensions installieren

Falls benötigt:
```bash
sudo pacman -S php-gd php-mysql php-xml
```

### GitHub CLI (für Releases)

```bash
sudo pacman -S github-cli
```

## Troubleshooting

### "PHP nicht gefunden"

Prüfe den PHP-Pfad:
```bash
which php
# Sollte zeigen: /usr/bin/php
```

Falls nicht, füge PHP zum PATH hinzu oder verwende den vollständigen Pfad im Workspace.

### "Permission denied" bei Scripts

```bash
chmod +x scripts/*.sh
chmod +x install.sh
```

### Intelephense-Cache

Bei Problemen mit Auto-Completion:
```bash
rm -rf ~/.cache/intelephense/
```

## Performance-Tipps

CachyOS ist optimiert für Performance. Für noch bessere IDE-Performance:

1. **SSD verwenden:** WoltLab Core auf SSD
2. **RAM:** Mindestens 8GB empfohlen
3. **Swap:** Falls nötig, Swap-Datei erstellen

## Weitere Informationen

- **[INSTALLATION.md](INSTALLATION.md)** - Allgemeine Installationsanleitung
- **[IDE-SETUP-CURSOR.md](IDE-SETUP-CURSOR.md)** - Cursor Setup
- [CachyOS Dokumentation](https://cachyos.org/)

