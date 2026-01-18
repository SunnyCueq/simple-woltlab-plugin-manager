#!/bin/bash

#################################################################
# WoltLab Development Tools - Quick Access
# Pfad: /home/benny/Dokumente/woltlab-development/tools.sh
# 
# Schnellzugriff auf das zentrale Tools-Menü
#################################################################

MAIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "$MAIN_DIR/tools/tools.sh" "$@"
