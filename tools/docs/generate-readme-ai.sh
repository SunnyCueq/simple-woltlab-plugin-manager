#!/usr/bin/env bash
# Generate readme-ai drafts for SWPM (review before merging into README.md).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
VENV="${ROOT}/.venv-readmeai"
BIN="${VENV}/bin/readmeai"
OUT="${ROOT}/docs/drafts"
API="${READMEAI_API:-openai}"

if [ ! -x "$BIN" ]; then
    echo "readme-ai not installed. Run:" >&2
    echo "  python3 -m venv .venv-readmeai && .venv-readmeai/bin/pip install -U readmeai" >&2
    exit 1
fi

if [ "$API" != "offline" ] && [ "$API" != "ollama" ]; then
    if [ -z "${OPENAI_API_KEY:-}" ] && [ -z "${ANTHROPIC_API_KEY:-}" ] && [ -z "${GOOGLE_API_KEY:-}" ]; then
        echo "No LLM API key set. Export OPENAI_API_KEY or use READMEAI_API=ollama|offline" >&2
        exit 1
    fi
fi

mkdir -p "$OUT"

SYS_EN='Project: Simple WoltLab Plugin Manager (SWPM). Cross-platform bash/python CLI for WoltLab plugin build, validate, release. No placeholder text. Accurate commands only. Exclude vendored woltlab-github from feature claims.'

SYS_DE='Projekt: Simple WoltLab Plugin Manager (SWPM). Plattformübergreifendes Bash/Python-CLI für WoltLab-Plugin Build, Validierung, Release. Gesamte README auf Deutsch. Keine Platzhalter. Nur echte Befehle.'

"$BIN" --api "$API" -r "$ROOT" -o "${OUT}/README.ai-en.md" \
    -td 2 -hs classic -e minimal \
    --system-message "$SYS_EN"

"$BIN" --api "$API" -r "$ROOT/tools" -o "${OUT}/README.ai-tools-en.md" \
    -td 2 -hs compact -e minimal \
    --system-message "$SYS_EN Tools subdirectory documentation."

"$BIN" --api "$API" -r "$ROOT" -o "${OUT}/README.ai-de.md" \
    -td 2 -hs classic -e minimal \
    --system-message "$SYS_DE"

echo "Drafts written to ${OUT}/ — review before merging."
