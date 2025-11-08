# Packaging-Workflow - WoltLab Suite Plugins

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

**Letzte Aktualisierung:** 2025-01-08  
**Status:** Aktuell

---

Diese Anleitung erklärt den kompletten Workflow von der Entwicklung bis zum fertigen Plugin-Package. Das Toolkit unterstützt dich dabei mit automatischen Scripts.

---

## Übersicht: Der komplette Workflow

```
1. Entwicklung → 2. TAR-Archive erstellen → 3. Package erstellen → 4. Release
```

---

## Schritt 1: Plugin entwickeln

### Plugin-Struktur erstellen

```bash
# 1. Plugin-Verzeichnis erstellen
mkdir -p ~/Dokumente/com.example.myplugin
cd ~/Dokumente/com.example.myplugin

# 2. Basis-Struktur erstellen
mkdir -p files/lib/page
mkdir -p templates
mkdir -p language

# 3. package.xml erstellen
# (siehe example-plugin/ für Beispiel)
```

### package.xml konfigurieren

Die `package.xml` definiert, welche Dateien dein Plugin enthält:

```xml
<instructions type="install">
    <instruction type="file" />        <!-- files.tar -->
    <instruction type="template" />    <!-- templates.tar -->
    <instruction type="page" />        <!-- page.xml -->
    <instruction type="language" />    <!-- language/ -->
</instructions>
```

**Wichtig:** Das Toolkit analysiert automatisch diese Instructions und findet die benötigten Dateien!

Siehe [PIP-TYPES.md](PIP-TYPES.md) für alle unterstützten PIP-Typen.

---

## Schritt 2: TAR-Archive erstellen

### Automatisch mit update-tars.sh

```bash
# Im Plugin-Verzeichnis
cd ~/Dokumente/com.example.myplugin

# TAR-Archive erstellen
./scripts/update-tars.sh
```

**Was macht das Script?**
- Sucht nach `_extracted/` Verzeichnissen
- Erstellt TAR-Archive für jedes Verzeichnis
- Legt die TAR-Dateien im Plugin-Verzeichnis ab

### Manuell erstellen

```bash
# Im Plugin-Verzeichnis
cd ~/Dokumente/com.example.myplugin

# files.tar erstellen
cd files && tar -cf ../files.tar * && cd ..

# templates.tar erstellen
cd templates && tar -cf ../templates.tar * && cd ..

# acptemplates.tar erstellen (falls vorhanden)
cd acptemplates && tar -cf ../acptemplates.tar * && cd ..
```

**💡 Tipp:** Verwende `update-tars.sh` für automatische Erstellung!

---

## Schritt 3: Package erstellen

### Mit create-release.sh (empfohlen)

```bash
# Im Plugin-Verzeichnis
cd ~/Dokumente/com.example.myplugin

# Package erstellen
./scripts/create-release.sh 1.0.0
```

**Was macht das Script?**

1. ✅ **Analysiert package.xml** - Findet automatisch alle benötigten Dateien
2. ✅ **Zeigt Package-Struktur** - Du siehst was gepackt wird
3. ✅ **Aktualisiert Version** - Setzt Version in `package.xml`
4. ✅ **Erstellt Backup** - Sichert das letzte Package
5. ✅ **Packt alles zusammen** - Erstellt `{plugin}-{version}.tar.gz`

**Beispiel-Output:**
```
=== Package-Struktur ===

📦 Package-Struktur:

  ✅ package.xml (4,0K)
  ✅ page.xml (4,0K)
  ✅ files.tar (12K)
  ✅ templates.tar (8,0K)
  📁 language/ (3 Dateien)

✅ Gefundene Dateien: 5

📦 Erstelle Package...
  ✅ Kopiert: package.xml
  ✅ Kopiert: page.xml
  ✅ Kopiert: files.tar
  ✅ Kopiert: templates.tar
  ✅ Kopiert: language/ (Verzeichnis)

✅ Package erstellt: com.example.myplugin-1.0.0.tar.gz
```

### Automatisches Parsing

Das Toolkit analysiert deine `package.xml` automatisch:

- **Findet alle `<instruction>` Tags**
- **Bestimmt Standard-Dateinamen** für jeden PIP-Typ
- **Sucht Dateien case-insensitive**
- **Zeigt Warnungen** wenn Dateien fehlen

**Du musst keine Dateien manuell auflisten!**

---

## Schritt 4: Release erstellen (optional)

### GitHub Release

```bash
# Mit GitHub Repository
./scripts/create-release.sh 1.0.0 . owner/repo-name
```

**Was passiert?**
- Package wird erstellt
- GitHub Release wird automatisch erstellt
- Package wird als Asset hochgeladen

### Manuelles Release

1. **Package hochladen:**
   - Gehe zu deinem GitHub Repository
   - Klicke auf "Releases" → "Draft a new release"
   - Wähle Tag (z.B. `v1.0.0`)
   - Lade `com.example.myplugin-1.0.0.tar.gz` hoch

2. **Oder im ACP installieren:**
   - ACP → Pakete → Paket installieren
   - Wähle die `.tar.gz` Datei
   - Klicke auf "Installieren"

---

## Kompletter Workflow-Beispiel

```bash
# 1. Plugin entwickeln
cd ~/Dokumente/com.example.myplugin
# ... Code schreiben ...

# 2. TAR-Archive erstellen
./scripts/update-tars.sh

# 3. Version erhöhen und Package erstellen
./scripts/plugin-version.sh patch
# Oder manuell:
./scripts/create-release.sh 1.0.1

# 4. GitHub Release (optional)
./scripts/create-release.sh 1.0.1 . owner/repo-name
```

---

## Best Practices

### ✅ DO's

1. **Immer TAR-Archive vor Package erstellen**
   ```bash
   ./scripts/update-tars.sh
   ```

2. **Package-Struktur prüfen**
   - Das Toolkit zeigt dir vor dem Packen, was gepackt wird
   - Prüfe die Ausgabe auf Warnungen

3. **Versionen konsistent halten**
   - Verwende `plugin-version.sh` für automatische Versionsverwaltung
   - Oder aktualisiere manuell in `package.xml`

4. **Backups nutzen**
   - Das Toolkit erstellt automatisch Backups in `.package-backups/`
   - Immer die letzten 2 Versionen behalten

### ❌ DON'Ts

1. **Nicht vergessen TAR-Archive zu erstellen**
   - Ohne TAR-Archive funktioniert das Package nicht

2. **Nicht unnötige Dateien packen**
   - `.git/`, `.DS_Store`, `*.bak` werden automatisch ignoriert

3. **Nicht Versionen überspringen**
   - Verwende Semantic Versioning (siehe [VERSIONING.md](VERSIONING.md))

---

## Troubleshooting

### "Datei nicht gefunden" Warnungen

**Problem:** Das Toolkit findet eine benötigte Datei nicht

**Lösung:**
1. Prüfe ob die Datei existiert (case-insensitive)
2. Prüfe ob der Dateiname in `package.xml` korrekt ist
3. Erstelle fehlende TAR-Archive mit `update-tars.sh`

### "Keine Dateien zum Packen gefunden"

**Problem:** Keine Instructions in `package.xml` gefunden

**Lösung:**
1. Prüfe ob `<instructions type="install">` vorhanden ist
2. Prüfe ob `<instruction>` Tags vorhanden sind
3. Siehe [PIP-TYPES.md](PIP-TYPES.md) für unterstützte PIP-Typen

### Package zu groß

**Problem:** Package enthält unnötige Dateien

**Lösung:**
1. Prüfe `.gitignore` - Dateien werden automatisch ignoriert
2. Entferne temporäre Dateien vor dem Packen
3. Verwende `update-tars.sh` statt manueller TAR-Erstellung

---

## Erweiterte Features

### Automatische Versionsverwaltung

```bash
# Version erhöhen und Package erstellen
./scripts/plugin-version.sh patch
```

**Was passiert?**
- Version wird in `package.xml` erhöht
- Datum wird aktualisiert
- Package wird automatisch erstellt
- Backup wird erstellt

### GitHub Integration

```bash
# Package + GitHub Release
./scripts/create-release.sh 1.0.1 . owner/repo-name
```

**Voraussetzungen:**
- GitHub CLI installiert (`gh`)
- Authentifiziert (`gh auth login`)

---

## Weitere Informationen

- **[PIP-TYPES.md](PIP-TYPES.md)** - Alle unterstützten PIP-Typen
- **[VERSIONING.md](VERSIONING.md)** - Versionsverwaltung
- **[PLUGIN-NAMING.md](PLUGIN-NAMING.md)** - Plugin-Namenskonventionen
- **[DEVELOPER-TOOLS.md](DEVELOPER-TOOLS.md)** - Entwickler-Werkzeuge
- [WoltLab Package Components](https://docs.woltlab.com/6.0/package-components/)

---

**Letzte Aktualisierung:** 2025-01-08

