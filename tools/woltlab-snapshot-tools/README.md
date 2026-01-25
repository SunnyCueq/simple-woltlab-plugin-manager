# 📸 WoltLab Snapshot Tools

**Blitzschnelle Wiederherstellung deiner WoltLab-Entwicklungsumgebung in 8 Sekunden!**

## 🎯 Was ist das?

Ein Snapshot-System das dir erlaubt, deine WoltLab-Installation jederzeit in Sekundenschnelle wiederherzustellen.

**Perfekt für:**
- Plugin-Entwicklung (Fehler gemacht? → Restore in 8 Sek!)
- Experimente (teste Features ohne Angst)
- Saubere Testumgebung (immer gleicher Ausgangszustand)

## 📁 Dateien

```
woltlab-snapshot-tools/
├── snapshot.sh    # Einmalig: Snapshot erstellen
├── restore.sh     # Bei Fehlern: System wiederherstellen (8 Sek)
└── README.md      # Diese Datei
```

## 🚀 Schnellstart

### 1️⃣ Einmalig: Snapshot erstellen

```bash
cd /home/benny/Dokumente/woltlab-development/tools/woltlab-snapshot-tools
./snapshot.sh
```

**Was passiert:**
1. DDEV startet
2. Public-Ordner & Datenbank werden geleert
3. Firefox öffnet WoltLab-Installer
4. **Du installierst WoltLab manuell**
5. Bei "Fertig" drückst du ENTER
6. Snapshot wird automatisch erstellt

**Zugangsdaten für Installation:**
- Datenbank: `db / db / db / db` (oder aus `.env` Datei)
- Admin: `Admin / admin@example.com / 123456 / 123456` (oder aus `.env` Datei)
- Lizenz: ✅ Ohne Lizenzdaten fortfahren

**Hinweis:** Zugangsdaten können in `tools/.env` konfiguriert werden. Verwende `tools/credentials.sh` zur Verwaltung.

### 2️⃣ Bei Problemen: Wiederherstellen

```bash
cd /home/benny/Dokumente/woltlab-development/tools/woltlab-snapshot-tools
./restore.sh
```

**Was passiert (in ~8 Sekunden):**
1. ✅ Public-Ordner wiederhergestellt (7420 Dateien)
2. ✅ Datenbank wiederhergestellt (161 Tabellen)
3. ✅ Caches geleert
4. ✅ phpMyAdmin-Infos angezeigt
5. ✅ Firefox mit ACP geöffnet

**Ergebnis:**
- 🌐 https://woltlab.ddev.site/
- 🔧 https://woltlab.ddev.site/acp/
- 👤 Admin: `Admin` / 🔑 `123456` (oder aus `.env` Datei)

## 💡 Typischer Workflow

```bash
# 1. Plugin entwickeln
cd basis-plugin
./build.sh

# 2. In WoltLab testen
# ... Plugin installieren, testen ...

# 3. Fehler gemacht? Kein Problem!
cd ../woltlab-snapshot-tools
./restore.sh
# → 8 Sekunden später: Frische Installation

# 4. Zurück zu Schritt 1
```

## 📊 Was wird gesichert?

### Snapshot-Inhalt
- **Public-Ordner:** ~7420 Dateien (~54 MB)
  - Alle PHP-Dateien
  - Templates
  - JavaScript/CSS
  - Bilder & Icons
- **Datenbank:** 161 Tabellen (~684 KB komprimiert)
  - Admin-Benutzer
  - Alle WoltLab-Einstellungen
  - Leere Content-Tabellen

### Gespeichert in
```
../woltlab-snapshot/
├── public/              # Alle WoltLab-Dateien
├── database.sql.gz      # Datenbank-Dump
└── metadata.txt         # Info über Snapshot
```

## ⚡ Performance

| Aktion | Dauer | Details |
|--------|-------|---------|
| **Snapshot erstellen** | ~2 Min | Einmalig (inkl. manuelle Installation) |
| **Restore ausführen** | **~8 Sek** | 7420 Dateien + 161 Tabellen |
| DDEV Start | ~3 Sek | Falls noch nicht läuft |
| phpMyAdmin Zugriff | <1 Sek | URL + Zugangsdaten anzeigen |

## 🔧 Technische Details

### phpMyAdmin Zugriff
- URL: `https://woltlab.ddev.site/phpmyadmin`
- Benutzer: `db`
- Passwort: `db`
- Datenbank: `db`

### DDEV Integration
- Nutzt `ddev describe -j` für Port-Erkennung
- Nutzt `ddev export-db` / `ddev mysql` für DB-Operationen
- Unterstützt Mutagen-Sync

## ❓ Häufige Fragen

### "phpMyAdmin Login"
- Verwende die DDEV-Standardzugangsdaten (`db` / `db`)

### "Snapshot nicht gefunden"
Erst `./snapshot.sh` ausführen!

### "Port hat sich geändert"
Normal bei DDEV-Neustart. `./restore.sh` erkennt den neuen Port automatisch.

## 📝 Logs & Debugging

```bash
# DDEV-Logs
cd ../woltlab-dev && ddev logs

# WoltLab-Logs
ls -la ../woltlab-dev/public/log/

# phpMyAdmin
echo "https://woltlab.ddev.site/phpmyadmin"
```

## 🎉 Fertig!

Du hast jetzt ein **professionelles Snapshot-System** für deine WoltLab-Entwicklung!

Bei Fragen oder Problemen: Schau in die Logs oder starte einfach neu mit `./snapshot.sh`
