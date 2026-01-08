# WSL2/Ubuntu Testing Guide

## Problem

Ein User berichtete über folgenden Fehler unter WSL2 - Ubuntu 24:

```bash
./install.sh: line 645: local: can only be used in a function
```

## Ursache

Das `local` Keyword in Bash kann nur innerhalb von Funktionen verwendet werden. Der Fehler trat auf, weil `local` im globalen Scope verwendet wurde.

## Lösung

Die fehlerhaften Zeilen wurden korrigiert:

**Vorher (Zeile 641-648):**
```bash
    # Prüfe ob Script-Dateien existieren
    local scripts_to_copy=(
        "extract-plugin-files.sh"
        "update-tars.sh"
        "create-release.sh"
    )

    local copy_success=true
```

**Nachher:**
```bash
    # Prüfe ob Script-Dateien existieren
    scripts_to_copy=(
        "extract-plugin-files.sh"
        "update-tars.sh"
        "create-release.sh"
    )

    copy_success=true
```

## Testen auf CachyOS/Arch Linux

Da du CachyOS verwendest und der Fehler nur unter WSL2/Ubuntu auftritt, kannst du mit Docker/Podman testen:

### Option 1: Automatischer Test (Empfohlen)

```bash
./scripts/test-wsl-ubuntu.sh
```

Wähle Option 3 für automatischen Test.

### Option 2: Interaktiver Test

```bash
./scripts/test-wsl-ubuntu.sh
```

Wähle Option 2 und teste das Script manuell in einem Ubuntu-Container.

### Option 3: Manueller Docker-Test

```bash
# Starte Ubuntu 24 Container
docker run -it --rm -v "$(pwd):/workspace:ro" -w /tmp ubuntu:24.04 bash

# Im Container:
apt update
apt install -y bash php git tar
cp /workspace/install.sh /tmp/
chmod +x /tmp/install.sh

# Teste
bash -n /tmp/install.sh  # Syntax-Check
./install.sh             # Vollständiger Test
```

## Voraussetzungen für Tests

### Docker installieren (CachyOS):

```bash
sudo pacman -S docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker $USER  # Optional: Docker ohne sudo
```

### Oder Podman (rootless):

```bash
sudo pacman -S podman
```

## Automatische Prüfung

Das Script `simple-bash-test.sh` prüft automatisch auf häufige Bash-Fehler:

```bash
./scripts/simple-bash-test.sh install.sh
```

## CI/CD Integration

Für zukünftige Commits kannst du diese Tests in GitHub Actions integrieren:

```yaml
name: Ubuntu Compatibility Test
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@v4
      - name: Syntax Check
        run: bash -n install.sh
      - name: Check local usage
        run: ./scripts/check-local-outside-functions.sh install.sh
```

## Häufige Bash-Fehler in WSL2/Ubuntu

1. **`local` außerhalb von Funktionen** (dieser Fall)
2. **Unterschiedliche Bash-Versionen**: Arch hat oft neuere Bash-Versionen
3. **Unterschiedliche `set -e` Verhalten**
4. **Pfad-Unterschiede** (`/mnt/c/` vs `/home/`)

## Testing Checklist

- [ ] Syntax-Check mit `bash -n install.sh`
- [ ] Test in Ubuntu 24 Container
- [ ] Test mit älteren Bash-Versionen (< 5.0)
- [ ] Test auf macOS (falls Zielplattform)
- [ ] ShellCheck Lint: `shellcheck install.sh`

## Weiterführende Links

- [Bash Manual - Shell Functions](https://www.gnu.org/software/bash/manual/html_node/Shell-Functions.html)
- [WSL2 Dokumentation](https://learn.microsoft.com/en-us/windows/wsl/)
- [Docker Testing Best Practices](https://docs.docker.com/develop/dev-best-practices/)
