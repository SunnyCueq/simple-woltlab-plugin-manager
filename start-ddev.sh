#!/bin/bash

#################################################################
# DDEV Quick Start - WoltLab Suite 6.1
# Pfad: /home/benny/Dokumente/affiliate-plugin/start-ddev.sh
# 
# Startet DDEV von überall aus
#################################################################

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/woltlab-dev"

# Führe das Start-Script aus
exec ./start.sh "$@"
