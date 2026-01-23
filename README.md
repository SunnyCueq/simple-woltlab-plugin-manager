# WoltLab Development Environment

Vollständige Entwicklungsumgebung für WoltLab Suite 6.1 Plugin-Entwicklung mit automatisierten Tools, Snapshot-System und DDEV-Integration.

## 📋 Inhaltsverzeichnis

- [Übersicht](#übersicht)
- [Ordnerstruktur](#ordnerstruktur)
- [Schnellstart](#schnellstart)
- [Systemanforderungen](#systemanforderungen)
- [Installation](#installation)
- [Workflow](#workflow)
- [Tools](#tools)
- [Troubleshooting](#troubleshooting)

## 🎯 Übersicht

Dieses Repository bietet eine vollständige Entwicklungsumgebung für die WoltLab Suite 6.1 Plugin-Entwicklung:

- **DDEV-basierte Entwicklungsumgebung** - Lokale WoltLab-Installation mit Docker
- **Automatisierte Build-Tools** - Plugin-Bau, Versionierung, Git-Integration
- **Snapshot-System** - Blitzschnelle Wiederherstellung in 8 Sekunden
- **TypeScript-Unterstützung** - Moderne Frontend-Entwicklung
- **Vollständige Dokumentation** - Alle Tools sind dokumentiert

## 📁 Ordnerstruktur

```
woltlab-development/
├── basis-plugin/          # Das eigene Plugin (Archiv) - wird bearbeitet
├── mein-plugin/           # Plugins (Archive) die basis-plugin voraussetzen, sonst leer
├── plugins-integrieren/  # Plugins (Archive) aus denen Funktionen adaptiert werden
├── woltlab-docs/          # WoltLab Dokumentation (wahlweise direkt geklont von GitHub)
├── woltlab-github/        # WoltLab WCF (wahlweise direkt geklont von GitHub)
├── woltlab-core/          # Installationsdateien von woltlab.com
└── tools/                 # Development Tools
    ├── woltlab-dev/       # DDEV Entwicklungsumgebung
    ├── woltlab-snapshot/  # Snapshot-Daten
    └── woltlab-snapshot-tools/  # Snapshot-Tools
```

### Ordner-Beschreibungen

- **`basis-plugin/`**: Das eigene Plugin (Archiv) das bearbeitet wird. Enthält alle Plugin-Dateien, Build-Scripts und Konfigurationen.
- **`mein-plugin/`**: Plugins (Archive) die basis-plugin voraussetzen, ansonsten leer lassen.
- **`plugins-integrieren/`**: Sämtliche Plugins (Archive) aus denen Funktionen adaptiert werden. Referenz-Implementierungen und Inspiration.
- **`woltlab-docs/`**: WoltLab Dokumentation (wahlweise direkt geklont von https://github.com/WoltLab/docs.woltlab.com/).
- **`woltlab-github/`**: WoltLab WCF (wahlweise direkt geklont von https://github.com/WoltLab/WCF). Referenz für Core-Funktionalität.
- **`woltlab-core/`**: Installationsdateien von https://www.woltlab.com/de/woltlab-suite-download/. Wird für die Installation verwendet.
- **`tools/`**: Development Tools - siehe [Tools](#tools) für Details.

## 🚀 Schnellstart

### 1. Repository klonen oder herunterladen

```bash
cd ~/Dokumente
git clone <repository-url> woltlab-development
cd woltlab-development
```

### 2. Setup ausführen

```bash
./tools/setup.sh
```

Das Setup-Script führt automatisch durch:
- DDEV Installation
- HeidiSQL Installation
- Node.js/npm Installation
- WoltLab Core Download
- .env Datei erstellen
- Git Repository initialisieren
- Ersten Snapshot erstellen

### 3. Entwicklungsumgebung starten

```bash
./tools.sh
# Wähle Option 4: DDEV
```

Oder direkt:

```bash
./tools/start-ddev.sh
```

### 4. Plugin entwickeln

```bash
cd basis-plugin
# Plugin-Dateien bearbeiten
./build.sh
# Plugin im ACP installieren und testen
```

## 💻 Systemanforderungen

- **Linux** (Arch Linux, Debian, Ubuntu, etc.)
- **Docker** & **Docker Compose** (für DDEV)
- **Git**
- **Node.js** & **npm** (für TypeScript-Kompilierung)
- **HeidiSQL** (optional, für Datenbank-Verwaltung)
- **Firefox** (optional, für automatische Browser-Öffnung)

## 📦 Installation

### Automatische Installation

```bash
./tools/setup.sh
```

Das Setup-Script bietet zwei Modi:
- **Vollautomatisch**: Alles ohne Fragen installieren (Standard-Werte)
- **Interaktiv**: Vorkonfiguration mit Fragen zu jedem Schritt

### Manuelle Installation

#### 1. DDEV installieren

```bash
curl -fsSL https://ddev.com/install.sh | bash
```

#### 2. HeidiSQL installieren

**Arch Linux:**
```bash
sudo pacman -S heidisql heidisql-qt6
```

**Debian/Ubuntu:**
```bash
sudo apt install heidisql
```

#### 3. Node.js/npm installieren

**Arch Linux:**
```bash
sudo pacman -S nodejs npm
```

**Debian/Ubuntu:**
```bash
sudo apt install nodejs npm
```

#### 4. WoltLab Core herunterladen

```bash
./tools/download-woltlab.sh
```

Oder manuell von https://www.woltlab.com/de/woltlab-suite-download/

#### 5. .env Datei erstellen

```bash
cp tools/.env.example tools/.env
# Bearbeite tools/.env und fülle die Werte aus
```

Oder verwende das Credentials-Tool:

```bash
./tools/credentials.sh
```

#### 6. DDEV-Projekt initialisieren

```bash
cd tools/woltlab-dev
ddev config --project-type=php --php-version=8.3
ddev start
```

#### 7. WoltLab installieren

Folge der Installation im Browser (wird automatisch geöffnet):
- Frontend: https://woltlab.ddev.site
- Datenbank: `db / db / db / db`
- Admin: `Admin / admin@example.com / 123456 / 123456`

#### 8. Ersten Snapshot erstellen

```bash
./tools/snapshot-manager.sh
# Wähle Option 1: Snapshot erstellen
```

## 🔄 Workflow

### Typischer Entwicklungs-Workflow

1. **Plugin entwickeln**
   ```bash
   cd basis-plugin
   # Dateien bearbeiten
   ./build.sh
   ```

2. **Plugin testen**
   - Plugin im ACP installieren
   - Funktionen testen
   - Fehler beheben

3. **Bei Problemen: Snapshot wiederherstellen**
   ```bash
   ./tools/restore-snapshot.sh
   # → 8 Sekunden später: Frische Installation
   ```

4. **Änderungen committen**
   ```bash
   ./tools/gitpush.sh
   ```

### Snapshot-Workflow

Das Snapshot-System erlaubt blitzschnelle Wiederherstellung:

- **Snapshot erstellen**: Einmalig nach Installation
- **Wiederherstellen**: Bei Fehlern oder für saubere Testumgebung
- **Dauer**: ~8 Sekunden für vollständige Wiederherstellung

Siehe [tools/woltlab-snapshot-tools/README.md](tools/woltlab-snapshot-tools/README.md) für Details.

## 🛠️ Tools

### Zentrale Tools-Übersicht

```bash
./tools.sh
```

Verfügbare Tools:

1. **Build** - Plugin bauen & Version erhöhen
2. **Git Push** - Commit & Push mit Release
3. **TypeScript** - Kompilieren & .min.js erstellen
4. **DDEV** - DDEV starten/verwalten
5. **Restore Snapshot** - WoltLab wiederherstellen
6. **Setup** - Vollständige Installation
7. **WoltLab Download** - WoltLab Core herunterladen
8. **Snapshot Manager** - Snapshot-Verwaltung
9. **Credentials** - Zugangsdaten-Verwaltung

### Detaillierte Dokumentation

- **[tools/README.md](tools/README.md)** - Übersicht aller Tools
- **[tools/woltlab-dev/README-DDEV.md](tools/woltlab-dev/README-DDEV.md)** - DDEV-Dokumentation
- **[tools/woltlab-snapshot-tools/README.md](tools/woltlab-snapshot-tools/README.md)** - Snapshot-System

## 🔧 Zugangsdaten-Verwaltung

Zugangsdaten werden in `tools/.env` gespeichert (nicht im Git).

### .env Datei erstellen

```bash
cp tools/.env.example tools/.env
# Bearbeite tools/.env
```

Oder verwende das Credentials-Tool:

```bash
./tools/credentials.sh
```

### Standard-Zugangsdaten

- **Datenbank**: `db / db / db / db`
- **Admin**: `Admin / admin@example.com / 123456 / 123456`
- **HeidiSQL**: `127.0.0.1:3306 / db / db / db`

## 🐛 Troubleshooting

### DDEV startet nicht

```bash
ddev poweroff    # Stoppt alle DDEV-Container
ddev start       # Startet neu
```

### Ports haben sich geändert

Ports werden automatisch erkannt. Bei Problemen:

```bash
ddev describe    # Zeigt aktuelle Ports
```

### Snapshot nicht gefunden

Erst Snapshot erstellen:

```bash
./tools/snapshot-manager.sh
# Wähle Option 1: Snapshot erstellen
```

### HeidiSQL fragt nach Passwort

Beim ersten Mal:
1. Passwort `db` eingeben
2. ✅ "Passwort speichern" aktivieren
3. Beim nächsten Restore wird es automatisch genutzt

### Logs prüfen

```bash
# DDEV-Logs
cd tools/woltlab-dev && ddev logs

# WoltLab-Logs
ls -la tools/woltlab-dev/public/log/
```

## 📚 Weitere Ressourcen

- [WoltLab Suite 6.1 Dokumentation](https://docs.woltlab.com/)
- [DDEV Dokumentation](https://ddev.readthedocs.io/)
- [WoltLab WCF GitHub](https://github.com/WoltLab/WCF)
- [WoltLab Docs GitHub](https://github.com/WoltLab/docs.woltlab.com/)

## 📝 Lizenz

Dieses Repository enthält Development Tools für die WoltLab Suite Plugin-Entwicklung.

Die WoltLab Suite selbst ist proprietäre Software von WoltLab GmbH.

## 🤝 Beitragen

Bei Fragen oder Problemen:
1. Prüfe die Logs
2. Schaue in die detaillierten READMEs
3. Erstelle ein Issue im Repository

---

**Viel Erfolg mit deiner WoltLab Plugin-Entwicklung! 🚀**
