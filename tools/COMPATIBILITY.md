# Plattform-Kompatibilität

## Unterstützte Plattformen

✅ **Linux** (alle Distributionen)
- Debian/Ubuntu (apt)
- Arch Linux (pacman)
- Fedora/RHEL (yum/dnf)
- Alpine Linux (apk)
- Andere Distributionen

✅ **macOS** (mit Homebrew)
- macOS 10.15+ (Catalina oder neuer)
- Homebrew erforderlich für Package-Installation

✅ **Windows WSL2**
- Ubuntu/Debian-basierte Distributionen
- Vollständig kompatibel

## Bekannte Kompatibilitätsprobleme

### 1. grep -P (Perl-Regex)
**Problem:** macOS verwendet BSD grep, das `-P` nicht unterstützt.

**Lösung:** Scripts verwenden jetzt `grep -E` (Extended Regex) wo möglich, oder `perl` als Fallback.

**Status:** ⚠️ Teilweise behoben - einige Stellen verwenden noch `grep -oP`

### 2. sed -i
**Problem:** macOS benötigt `sed -i ''` statt `sed -i`.

**Lösung:** `sed_inplace()` Funktion in `common.sh` erstellt.

**Status:** ⚠️ Noch nicht überall implementiert

### 3. Package Manager
**Problem:** Verschiedene Distributionen verwenden verschiedene Package Manager.

**Lösung:** Automatische Erkennung mit Fallback:
- pacman (Arch)
- apt/apt-get (Debian/Ubuntu)
- yum/dnf (Fedora/RHEL)
- brew (macOS)
- pkg (FreeBSD)

**Status:** ✅ Implementiert

### 4. Shebang
**Problem:** `/bin/bash` könnte auf macOS eine alte Version sein.

**Lösung:** `#!/usr/bin/env bash` verwendet (findet aktuelle bash-Version).

**Status:** ⚠️ Noch nicht überall aktualisiert

## Empfohlene Verbesserungen

1. **Alle grep -P ersetzen:**
   - Durch `grep -E` wo möglich
   - Oder durch `perl -ne` als Fallback

2. **Alle sed -i ersetzen:**
   - Durch `sed_inplace()` Funktion

3. **Alle Shebangs aktualisieren:**
   - Von `#!/bin/bash` zu `#!/usr/bin/env bash`

4. **macOS-spezifische Tests:**
   - date-Format prüfen
   - Pfad-Trenner prüfen
   - BSD vs GNU Tools

## Aktueller Status

- ✅ Plattform-Erkennung implementiert
- ✅ Package Manager-Erkennung implementiert
- ⚠️ grep -P noch teilweise vorhanden (51 Stellen)
- ⚠️ sed -i noch nicht überall plattformkompatibel
- ⚠️ Shebang noch nicht überall aktualisiert
