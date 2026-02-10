# Einheitliche Shell-Skript-Struktur

Alle Tools und Skripte in `tools/` folgen einem einheitlichen Aufbau. Referenzen: [Google Shell Style Guide](https://google.github.io/styleguide/shellguide.html), [Bash Hackers Wiki](https://wiki.bash-hackers.org/).

**Skripte in `tools/`:** `common.sh` (gemeinsame Funktionen, wird von den anderen gesourct), `tools.sh` (Hauptmenü), `build.sh`, `gitpush.sh`, `typescript.sh`, `unpack.sh`, `validate-plugin.sh`, `setup-minimal.sh`, `help.sh`, `download-woltlab-core.sh`. Optional kann `manager-push.sh` (Maintainer) vorhanden sein.

## Sektions-Schema (Template)

Jedes Skript gliedert sich in diese Blöcke (in dieser Reihenfolge):

1. **Shebang + Kurzbeschreibung** – Was macht das Skript, Pfad, ggf. Usage in 1–3 Zeilen.
2. **KONFIGURATION** – `set -e`, `readonly` Pfade/Variablen.
3. **QUELLEN** – `source common.sh`, Fallback wenn common.sh fehlt.
4. **HILFSFUNKTIONEN** – Nur skriptspezifische Funktionen.
5. **HAUPTLOGIK** – Parameter parsen, case/if, Aufrufe; bei tools.sh: Menü-Schleife.
6. **MAIN / EINSTIEG** – `main "$@"` oder erkennbarer Einstieg (z. B. while-Schleife).

## Kommentarblöcke

Verwende einheitliche Trennzeilen:

```bash
#=====================================
# KONFIGURATION
#=====================================

#=====================================
# QUELLEN
#=====================================

#=====================================
# HILFSFUNKTIONEN
#=====================================

#=====================================
# HAUPTLOGIK
#=====================================
```

## Beispiel (Auszug): help.sh

```bash
#!/bin/bash

#################################################################
# Help & Documentation Viewer
# Pfad: tools/help.sh
# Zeigt die README.md in einem lesbaren Format an.
#################################################################

set -e

#=====================================
# KONFIGURATION
#=====================================
readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly README_FILE="$TOOLS_DIR/README.md"

#=====================================
# QUELLEN
#=====================================
if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    RED='\033[0;31m'
    # ... Fallback-Farben
fi

#=====================================
# HILFSFUNKTIONEN
#=====================================
show_with_glow() { ... }
show_with_bat() { ... }
# ...

#=====================================
# HAUPTLOGIK
#=====================================
if [ ! -f "$README_FILE" ]; then
    print_error "README.md nicht gefunden: $README_FILE"
    exit 1
fi
if show_with_glow; then exit 0; fi
# ...
```

## Stil

- **set -e** in allen Skripten.
- **readonly** für Pfade und Konstanten, die sich nicht ändern.
- **trap** nur wo nötig (Cleanup, Lock).
