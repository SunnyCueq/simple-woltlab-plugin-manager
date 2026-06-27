#!/usr/bin/env bash

#################################################################
# WoltLab-Referenzen automatisch synchronisieren (nicht-interaktiv)
#
# Synchronisiert woltlab-docs, woltlab-github und woltlab-d-ts mit
# dem jeweiligen origin/<VERSION>-Branch (Standard: 6.2).
#
# Manuell:
#   ./tools/sync-woltlab-references.sh
#   ./tools/sync-woltlab-references.sh 6.2
#
# Cron (wöchentlich, Sonntag 04:00):
#   0 4 * * 0 /home/benny/Dokumente/woltlab/plugin-manager/tools/sync-woltlab-references.sh >> /home/benny/.cache/woltlab-refs-sync.log 2>&1
#
# Systemd-Timer (optional):
#   systemctl --user enable --now woltlab-refs-sync.timer
#################################################################

set -e

readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly VERSION="${1:-6.2}"

exec "$TOOLS_DIR/update-woltlab-version.sh" --refs-only "$VERSION"
