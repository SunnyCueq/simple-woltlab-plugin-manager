# DDEV - WoltLab Suite 6.1 Entwicklungsumgebung

## 🚀 Schnellstart

### Start-Script verwenden (empfohlen)

```bash
# Von überall aus:
cd ~/Dokumente/affiliate-plugin
./start-ddev.sh

# Oder direkt im woltlab-dev Verzeichnis:
cd ~/Dokumente/affiliate-plugin/woltlab-dev
./start.sh
```

### Verfügbare Kommandos

```bash
./start.sh          # Startet DDEV (Standard)
./start.sh logs     # Startet DDEV und zeigt Logs
./start.sh stop     # Stoppt DDEV
./start.sh restart  # Startet DDEV neu
./start.sh status   # Zeigt DDEV Status
```

## 📋 Direkte DDEV-Befehle

Falls du DDEV direkt verwenden möchtest:

```bash
cd ~/Dokumente/affiliate-plugin/woltlab-dev

# Start/Stop
ddev start           # Startet DDEV
ddev stop           # Stoppt DDEV
ddev restart        # Startet DDEV neu

# Status & Info
ddev describe       # Zeigt Projekt-Informationen
ddev status         # Zeigt Status aller Services

# Logs
ddev logs           # Zeigt alle Logs
ddev logs -f        # Zeigt Logs live (follow mode)
ddev logs web       # Zeigt nur Web-Logs

# Container-Zugriff
ddev ssh            # SSH in den Web-Container
ddev exec bash      # Führt Befehl im Container aus

# Datenbank
ddev mysql          # Öffnet MySQL-Client
ddev import-db      # Importiert Datenbank
ddev export-db      # Exportiert Datenbank

# Composer & Pakete
ddev composer install    # Installiert Composer-Pakete
ddev composer update     # Aktualisiert Composer-Pakete
```

## 🌐 URLs

Nach dem Start sind folgende URLs verfügbar:

- **Frontend:** https://woltlab.ddev.site
- **ACP:** https://woltlab.ddev.site/acp/
- **Shr1nkr ACP:** https://woltlab.ddev.site/shrinkr/acp/

## 🔧 Wichtige Verzeichnisse

- **WoltLab Root:** `/home/benny/Dokumente/affiliate-plugin/woltlab-dev/public/`
- **Plugin-Entwicklung:** `/home/benny/Dokumente/affiliate-plugin/basis-plugin/`
- **Plugin-Installation:** `/home/benny/Dokumente/affiliate-plugin/woltlab-dev/public/shrinkr/`
- **Logs:** `/home/benny/Dokumente/affiliate-plugin/woltlab-dev/public/log/`

## 📦 Plugin-Installation

1. Plugin bauen:
   ```bash
   cd ~/Dokumente/affiliate-plugin/basis-plugin
   ./build.sh
   ```

2. Plugin installieren:
   - Im ACP: System → Paket-Verwaltung
   - Oder per CLI: `ddev exec php public/cli.php package install de.sunnyc.wsc.shrinkr_v1.0.XX.tar.gz`

## 🐛 Troubleshooting

### DDEV startet nicht
```bash
ddev poweroff    # Stoppt alle DDEV-Container
ddev start       # Startet neu
```

### Logs prüfen
```bash
ddev logs        # Zeigt alle Logs
tail -f public/log/2026-01-*.txt  # WoltLab-Logs
```

### Datenbank zurücksetzen
```bash
ddev import-db --file=backup.sql
```

## 📚 Weitere Infos

- [DDEV Dokumentation](https://ddev.readthedocs.io/)
- [WoltLab Suite 6.1 Dokumentation](https://docs.woltlab.com/)
