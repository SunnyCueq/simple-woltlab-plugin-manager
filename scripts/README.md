# Scripts Übersicht

Dieses Verzeichnis enthält alle Hilfsskripte für das WoltLab Plugin Manager Projekt.

## 📦 Plugin-Entwicklung

### `create-plugin.sh`
Erstellt ein neues WoltLab Plugin mit der richtigen Struktur.

### `validate-plugin.sh`
Validiert ein Plugin auf Fehler, fehlende Dateien und Best Practices.

### `plugin-version.sh`
Verwaltet Plugin-Versionen (get, bump, set).

## 🔨 Build & Release

### `update-tars.sh`
Aktualisiert TAR-Archive aus den Plugin-Dateien.

### `extract-plugin-files.sh`
Extrahiert Plugin-Dateien aus TAR-Archiven.

### `create-release.sh`
Erstellt ein vollständiges Release-Paket.

### `version.sh`
Projekt-Versionsverwaltung.

## 🧪 Testing

### `simple-bash-test.sh`
**Schneller Kompatibilitätstest** für Bash-Scripte.

```bash
./scripts/simple-bash-test.sh install.sh
```

Prüft:
- Bash-Syntax
- `local` außerhalb von Funktionen
- Statistiken

### `test-wsl-ubuntu.sh`
**Docker-basierter Test** für WSL2/Ubuntu 24 Kompatibilität.

```bash
./scripts/test-wsl-ubuntu.sh
```

Optionen:
1. Schnelltest (Syntax-Check)
2. Vollständiger Test (Interaktiv)
3. Automatischer Test (Non-Interactive)


## 🔧 Utilities

### `parse-package-xml.sh`
Parst `package.xml` und extrahiert Informationen.

### `pip-defaults.sh`
Standardwerte für PIPs (Plugin Installation Packages).

---

## Typische Workflows

### Neues Plugin erstellen
```bash
./scripts/create-plugin.sh
```

### Plugin validieren
```bash
./scripts/validate-plugin.sh /pfad/zum/plugin
```

### Release erstellen
```bash
cd /pfad/zum/plugin
./create-release.sh 1.0.0
```

### WSL2-Kompatibilität testen (auf Arch/CachyOS)
```bash
# Schnelltest
./scripts/simple-bash-test.sh install.sh

# Docker-Test (Ubuntu 24)
./scripts/test-wsl-ubuntu.sh
```

---

## Entwickler-Hinweise

### Testing auf verschiedenen Systemen

**CachyOS/Arch Linux:**
- Verwende Docker für Ubuntu-Tests
- `simple-bash-test.sh` für lokale Tests

**WSL2/Ubuntu:**
- Direkte Tests möglich
- Beachte Bash-Version Unterschiede

**macOS:**
- Beachte BSD vs GNU coreutils
- Installiere GNU tools via Homebrew

### Bash-Best-Practices

1. ✅ `local` nur in Funktionen
2. ✅ Verwende `set -euo pipefail`
3. ✅ Quote alle Variablen: `"$VAR"`
4. ✅ Verwende `[[ ]]` statt `[ ]`
5. ✅ Array-Syntax: `arr=()` statt `local arr=()`

---

## Weitere Dokumentation

- [WSL2 Testing Guide](../docs/TESTING-WSL.md)
- [Plugin Store Checklist](../docs/PLUGIN-STORE-CHECKLIST.md)
- [Workspace Setup](../docs/WORKSPACE-SETUP.md)
