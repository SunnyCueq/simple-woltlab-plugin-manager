# GitHub Repository Setup - Anleitung

## Schritt 1: Repository auf GitHub erstellen

1. Gehen Sie zu [GitHub](https://github.com/new)
2. Füllen Sie folgende Felder aus:

### Repository-Name
```
simple-woltlab-plugin-manager
```

### Beschreibung (DE)
```
Toolkit für die WoltLab Suite Plugin-Entwicklung mit generischen Build-Scripts, Workspace-Templates und umfassenden Installationsanleitungen. Unterstützt Cursor, VSCode, Linux (CachyOS) und Windows WSL.
```

### Beschreibung (EN)
```
Toolkit for WoltLab Suite plugin development with generic build scripts, workspace templates, and comprehensive installation guides. Supports Cursor, VSCode, Linux (CachyOS), and Windows WSL.
```

### Einstellungen
- ✅ **Public** (Repository ist öffentlich)
- ❌ **Add a README file** (bereits vorhanden)
- ❌ **Add .gitignore** (bereits vorhanden)
- ❌ **Choose a license** (bereits vorhanden - MIT)

3. Klicken Sie auf **"Create repository"**

## Schritt 2: Topics/Tags hinzufügen

Nach der Erstellung, fügen Sie folgende Topics hinzu:

- `woltlab`
- `woltlab-suite`
- `woltlab-plugin`
- `plugin-development`
- `php`
- `development-tools`
- `cursor-ide`
- `vscode`
- `workspace`
- `build-tools`

## Schritt 3: Repository mit lokalem Git verbinden

```bash
cd /home/benny/Dokumente/simple-woltlab-plugin-manager

# Remote hinzufügen (ersetzen Sie YOUR_USERNAME)
git remote add origin https://github.com/YOUR_USERNAME/simple-woltlab-plugin-manager.git

# Branch auf main setzen (falls nötig)
git branch -M main

# Ersten Commit erstellen
git commit -m "Initial commit: Simple WoltLab Plugin Manager

- Generische Build-Scripts für WoltLab Plugins
- Workspace-Templates für Cursor/VSCode
- Umfassende Dokumentation (DE/EN)
- Beispiel-Plugin basierend auf WoltLab Getting Started
- CI/CD Template für automatische Releases
- Installations-Script für einfache Einrichtung"

# Auf GitHub pushen
git push -u origin main
```

## Schritt 4: GitHub CLI (Alternative)

Falls Sie GitHub CLI installiert haben:

```bash
cd /home/benny/Dokumente/simple-woltlab-plugin-manager

# Repository erstellen
gh repo create simple-woltlab-plugin-manager \
  --public \
  --description "Toolkit für die WoltLab Suite Plugin-Entwicklung mit generischen Build-Scripts, Workspace-Templates und umfassenden Installationsanleitungen. Unterstützt Cursor, VSCode, Linux (CachyOS) und Windows WSL." \
  --source=. \
  --remote=origin \
  --push
```

## Schritt 5: Topics hinzufügen (GitHub CLI)

```bash
gh repo edit --add-topic woltlab --add-topic woltlab-suite --add-topic woltlab-plugin --add-topic plugin-development --add-topic php --add-topic development-tools --add-topic cursor-ide --add-topic vscode --add-topic workspace --add-topic build-tools
```

## Schritt 6: Repository-URL in README aktualisieren

Nach dem Erstellen des Repositories, aktualisieren Sie die URLs in:
- `README.md`
- `README_EN.md`
- `docs/INSTALLATION.md`
- `docs/INSTALLATION_EN.md`

Ersetzen Sie `https://github.com/your-username/simple-woltlab-plugin-manager.git` mit Ihrer tatsächlichen Repository-URL.

## Fertig! 🎉

Ihr Repository ist jetzt auf GitHub verfügbar und kann von der Community genutzt werden.

