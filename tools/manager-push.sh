#!/usr/bin/env bash
# Veraltet — nutze release-manager.sh (im Repo, SemVer + origin + CI).
exec "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/release-manager.sh" "$@"
