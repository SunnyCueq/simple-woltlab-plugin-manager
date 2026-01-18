#!/bin/bash

#################################################################
# WoltLab Snapshot Wiederherstellung
# Pfad: tools/restore-snapshot.sh
# 
# Stellt die komplette WoltLab-Installation aus dem Snapshot wieder her
#################################################################

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$TOOLS_DIR/woltlab-snapshot-tools"

# Führe das Restore-Script aus
exec ./restore.sh
