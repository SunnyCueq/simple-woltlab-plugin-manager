#!/usr/bin/env bash
# Pack style.tar from style/style.xml (style PIP).
# Usage: pack-style-tar.sh <temp_edit_dir> <output_style.tar>

set -euo pipefail

readonly SOURCE_DIR="${1:?temp_edit dir}"
readonly OUT_TAR="${2:?output style.tar}"
readonly STYLE_DIR="$SOURCE_DIR/style"
readonly STYLE_XML="$STYLE_DIR/style.xml"

if [ ! -f "$STYLE_XML" ]; then
    echo "pack-style-tar: $STYLE_XML fehlt" >&2
    exit 1
fi

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT

cp "$STYLE_XML" "$WORK/style.xml"

pack_sub_archive() {
    local tag="$1"
    local archive_name="$2"
    local folder_name="${archive_name%.tar}"
    local src=""

    if grep -q "<${tag}>${archive_name}</${tag}>" "$STYLE_XML" 2>/dev/null; then
        if [ -d "$STYLE_DIR/$folder_name" ]; then
            src="$STYLE_DIR/$folder_name"
        elif [ -d "$SOURCE_DIR/$folder_name" ]; then
            src="$SOURCE_DIR/$folder_name"
        fi
    fi

    if [ -n "$src" ]; then
        tar -cf "$WORK/$archive_name" -C "$src" .
        echo "  + $archive_name aus $src"
    fi
}

pack_sub_archive "templates" "templates.tar"
pack_sub_archive "images" "images.tar"

tar -cf "$OUT_TAR" -C "$WORK" .
echo "pack-style-tar: $OUT_TAR erstellt"
