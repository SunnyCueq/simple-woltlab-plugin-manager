#!/usr/bin/env bash
# Copywriting-Review – nutzt System-Python (nicht Cursor-AppImage).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TOOLS_DIR="$(dirname "$SCRIPT_DIR")"
VENV="$SCRIPT_DIR/.venv"
PY="${PY:-/usr/bin/python3.14}"

if [[ ! -x "$PY" ]]; then
	PY="/usr/bin/python3"
fi

ensure_venv() {
	if [[ -f "$VENV/bin/python3" ]] && file "$VENV/bin/python3" | grep -q 'ELF'; then
		return 0
	fi
	echo "Erstelle venv mit $PY …"
	rm -rf "$VENV"
	env -i PATH=/usr/bin:/bin HOME="${HOME:-/tmp}" "$PY" -m venv "$VENV"
	env -i PATH="$VENV/bin:/usr/bin:/bin" HOME="${HOME:-/tmp}" \
		"$VENV/bin/python3" -m pip install -q -r "$SCRIPT_DIR/requirements.txt"
}

ensure_venv

# Optional: tools/.env für OPENAI_API_KEY
if [[ -f "$TOOLS_DIR/.env" ]]; then
	set -a
	# shellcheck disable=SC1090
	source "$TOOLS_DIR/.env"
	set +a
fi

if [[ "${1:-}" == "apply" || "${1:-}" == "apply-safe" ]]; then
	CMD="$1"
	shift
	exec env -i PATH="$VENV/bin:/usr/bin:/bin" HOME="${HOME:-/tmp}" \
		"$VENV/bin/python3" "$SCRIPT_DIR/apply.py" "$@"
fi

exec env -i \
	PATH="$VENV/bin:/usr/bin:/bin" \
	HOME="${HOME:-/tmp}" \
	OPENAI_API_KEY="${OPENAI_API_KEY:-}" \
	OPENAI_BASE_URL="${OPENAI_BASE_URL:-}" \
	OPENAI_MODEL="${OPENAI_MODEL:-}" \
	COPYWRITING_API_KEY="${COPYWRITING_API_KEY:-}" \
	COPYWRITING_MODEL="${COPYWRITING_MODEL:-}" \
	"$VENV/bin/python3" "$SCRIPT_DIR/review.py" "$@"
