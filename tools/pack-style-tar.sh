#!/usr/bin/env bash
# Pack style.tar / style.tgz / style.tar.gz from style/style.xml (style PIP).
#
# WoltLab accepts .tar, .tar.gz, and .tgz. Pure style packages (e.g. ACP export)
# typically ship variables.xml (+ optional dark mode), previews, images.tar,
# templates.tar — SCSS is compiled by WoltLab from variables at install/runtime,
# not by SWPM.
#
# Usage: pack-style-tar.sh <temp_edit_dir> <output_archive>
#   output_archive: style.tar | style.tgz | style.tar.gz (extension decides format)

set -euo pipefail

readonly SOURCE_DIR="${1:?temp_edit dir}"
readonly OUT_ARCHIVE="${2:?output style archive}"
readonly STYLE_DIR="$SOURCE_DIR/style"
readonly STYLE_XML="$STYLE_DIR/style.xml"

if [ ! -f "$STYLE_XML" ]; then
    echo "pack-style-tar: $STYLE_XML fehlt" >&2
    exit 1
fi

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT

cp "$STYLE_XML" "$WORK/style.xml"

# Extract first text content of a local XML tag (namespace-agnostic enough for style.xml).
xml_tag_text() {
    local tag="$1"
    # Prefer CDATA / text inside <tag>...</tag>
    sed -n "s/.*<${tag}[^>]*>[[:space:]]*<!\\[CDATA\\[\\([^]]*\\)\\]\\]>[[:space:]]*<\\/${tag}>.*/\\1/p; t
            s/.*<${tag}[^>]*>\\([^<]*\\)<\\/${tag}>.*/\\1/p" "$STYLE_XML" | head -1 | sed 's/^[[:space:]]*//;s/[[:space:]]*$//'
}

copy_if_present() {
    local name="$1"
    [ -z "$name" ] && return 0
    if [ -f "$STYLE_DIR/$name" ]; then
        cp "$STYLE_DIR/$name" "$WORK/$name"
        echo "  + $name"
    elif [ -f "$SOURCE_DIR/$name" ]; then
        cp "$SOURCE_DIR/$name" "$WORK/$name"
        echo "  + $name (from package root)"
    else
        echo "pack-style-tar: fehlt: $name (referenziert in style.xml)" >&2
        exit 1
    fi
}

# Flat files commonly referenced from <general> / <files>
for tag in image image2x coverPhoto; do
    val=$(xml_tag_text "$tag" || true)
    [ -n "$val" ] && copy_if_present "$val"
done

for tag in variables variablesDarkMode; do
    val=$(xml_tag_text "$tag" || true)
    [ -n "$val" ] && copy_if_present "$val"
done

pack_sub_archive() {
    local tag="$1"
    local archive_name
    archive_name=$(xml_tag_text "$tag" || true)
    [ -z "$archive_name" ] && return 0

    local folder_name="${archive_name}"
    folder_name="${folder_name%.tar.gz}"
    folder_name="${folder_name%.tgz}"
    folder_name="${folder_name%.tar}"

    local src=""
    if [ -d "$STYLE_DIR/$folder_name" ]; then
        src="$STYLE_DIR/$folder_name"
    elif [ -d "$SOURCE_DIR/$folder_name" ]; then
        src="$SOURCE_DIR/$folder_name"
    fi

    if [ -z "$src" ]; then
        echo "pack-style-tar: Ordner für <$tag>$archive_name fehlt (erwartet style/$folder_name/)" >&2
        exit 1
    fi

    tar -cf "$WORK/$archive_name" -C "$src" .
    echo "  + $archive_name aus $src"
}

pack_sub_archive "templates"
pack_sub_archive "images"

# Prefer filename from package.xml instruction (.tgz vs .tar)
case "$OUT_ARCHIVE" in
    *.tgz|*.tar.gz)
        tar -czf "$OUT_ARCHIVE" -C "$WORK" .
        ;;
    *)
        tar -cf "$OUT_ARCHIVE" -C "$WORK" .
        ;;
esac

echo "pack-style-tar: $OUT_ARCHIVE erstellt"
