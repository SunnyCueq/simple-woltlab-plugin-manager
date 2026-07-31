#!/usr/bin/env bash

#################################################################
# WoltLab-Referenzen automatisch synchronisieren (nicht-interaktiv)
#
# Synchronisiert:
#   woltlab-docs, woltlab-github, woltlab-d-ts,
#   woltlab-exporter, woltlab-conversation, woltlab-legal-notice
# mit origin/<VERSION> (Standard: 6.2). Optional Core-ZIP via update-Skript.
#
# Manuell:
#   ./tools/sync-woltlab-references.sh
#   ./tools/sync-woltlab-references.sh 6.2
#   ./tools/update-woltlab-version.sh 6.2.6   # inkl. Core-Download
#
# Cron (wöchentlich, Sonntag 04:00):
#   0 4 * * 0 /pfad/zum/plugin-manager/tools/sync-woltlab-references.sh >> ~/.cache/woltlab-refs-sync.log 2>&1
#################################################################

set -e

readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly VERSION="${1:-6.2}"

exec "$TOOLS_DIR/update-woltlab-version.sh" --refs-only "$VERSION"
