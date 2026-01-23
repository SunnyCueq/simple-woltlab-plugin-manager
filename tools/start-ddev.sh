#!/bin/bash

#################################################################
# DDEV Quick Start - WoltLab Suite 6.1
# Pfad: tools/start-ddev.sh
# 
# Startet DDEV von überall aus
#################################################################

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DDEV_DIR="$TOOLS_DIR/woltlab-dev"
START_SCRIPT="$DDEV_DIR/start.sh"

# Prüfe ob DDEV-Verzeichnis existiert
if [ ! -d "$DDEV_DIR" ]; then
    echo "Fehler: DDEV-Verzeichnis nicht gefunden: $DDEV_DIR" >&2
    exit 1
fi

# Prüfe ob start.sh existiert
if [ ! -f "$START_SCRIPT" ]; then
    echo "Fehler: start.sh nicht gefunden: $START_SCRIPT" >&2
    exit 1
fi

# Wechsle ins DDEV-Verzeichnis
cd "$DDEV_DIR" || {
    echo "Fehler: Konnte nicht ins DDEV-Verzeichnis wechseln: $DDEV_DIR" >&2
    exit 1
}

# Führe das Start-Script aus
exec ./start.sh "$@"
