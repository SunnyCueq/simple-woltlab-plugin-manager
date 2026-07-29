#!/usr/bin/env bash
# SWPM product-line orchestrator: list | order | build | validate | init | add-addon
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$SCRIPT_DIR")"

# shellcheck source=swpm-family-resolve.sh
source "$SCRIPT_DIR/swpm-family-resolve.sh"

if [ -f "$SCRIPT_DIR/common.sh" ]; then
    # shellcheck source=common.sh
    source "$SCRIPT_DIR/common.sh"
else
    print_success() { echo "OK: $1"; }
    print_error() { echo "ERR: $1" >&2; }
    print_warning() { echo "WARN: $1" >&2; }
    print_info() { echo "INFO: $1"; }
fi

MANIFEST_ARG=""
CONTINUE=0
STRICT=0
SCAFFOLD=0
FORCE=0
PARENT=""
BASE_ID="com.vendor.myapp"
BASE_ID_SET=0
BASE_DIR="myapp"
ADDONS_CSV=""
WCF_MIN="6.1.0"
LINE_ID=""
CMD=""

usage() {
    cat <<'EOF'
Usage:
  swpm-family.sh [--manifest PATH] list|order|check
  swpm-family.sh [--manifest PATH] [--continue] build [patch|minor|major|same]
  swpm-family.sh [--manifest PATH] [--continue] [--strict] validate
  swpm-family.sh init [--scaffold] [--force] [--parent DIR] [--line-id ID]
                     [--base-id ID] [--base-dir DIR] [--addons a,b] [--wcf-min VER]
  swpm-family.sh add-addon <slug> [--base-id ID] [--force] [--parent DIR]

Manifest: --manifest, else WOLTLAB_FAMILY_MANIFEST, else MAIN_DIR/swpm-family.json
EOF
}

while [ $# -gt 0 ]; do
    case "$1" in
        --manifest) MANIFEST_ARG="${2:-}"; shift 2 ;;
        --continue) CONTINUE=1; shift ;;
        --strict) STRICT=1; shift ;;
        --scaffold) SCAFFOLD=1; shift ;;
        --force) FORCE=1; shift ;;
        --parent) PARENT="${2:-}"; shift 2 ;;
        --base-id) BASE_ID="${2:-}"; BASE_ID_SET=1; shift 2 ;;
        --base-dir) BASE_DIR="${2:-}"; shift 2 ;;
        --addons) ADDONS_CSV="${2:-}"; shift 2 ;;
        --wcf-min) WCF_MIN="${2:-}"; shift 2 ;;
        --line-id) LINE_ID="${2:-}"; shift 2 ;;
        -h|--help) usage; exit 0 ;;
        list|order|check|build|validate|init|add-addon)
            CMD="$1"
            shift
            break
            ;;
        *)
            print_error "Unbekanntes Argument: $1"
            usage
            exit 1
            ;;
    esac
done

# Remaining args after command — also accept flags after subcommand
REST=("$@")
_tmp=()
while [ ${#REST[@]} -gt 0 ]; do
    case "${REST[0]}" in
        --manifest)
            MANIFEST_ARG="${REST[1]:-}"
            REST=("${REST[@]:2}")
            ;;
        --continue) CONTINUE=1; REST=("${REST[@]:1}") ;;
        --strict) STRICT=1; REST=("${REST[@]:1}") ;;
        --scaffold) SCAFFOLD=1; REST=("${REST[@]:1}") ;;
        --force) FORCE=1; REST=("${REST[@]:1}") ;;
        --parent)
            PARENT="${REST[1]:-}"
            REST=("${REST[@]:2}")
            ;;
        --base-id)
            BASE_ID="${REST[1]:-}"
            BASE_ID_SET=1
            REST=("${REST[@]:2}")
            ;;
        --base-dir)
            BASE_DIR="${REST[1]:-}"
            REST=("${REST[@]:2}")
            ;;
        --addons)
            ADDONS_CSV="${REST[1]:-}"
            REST=("${REST[@]:2}")
            ;;
        --wcf-min)
            WCF_MIN="${REST[1]:-}"
            REST=("${REST[@]:2}")
            ;;
        --line-id)
            LINE_ID="${REST[1]:-}"
            REST=("${REST[@]:2}")
            ;;
        *)
            _tmp+=("${REST[0]}")
            REST=("${REST[@]:1}")
            ;;
    esac
done
REST=("${_tmp[@]}")

warn_env_overrides() {
    if [ -n "${WOLTLAB_PACKAGE_ID:-}" ] || [ -n "${WOLTLAB_APP_ABBREV:-}" ]; then
        print_warning "family:* ignoriert WOLTLAB_PACKAGE_ID / WOLTLAB_APP_ABBREV — Metadaten kommen aus package.xml pro Paket. Bei Produktlinien diese .env-Keys leer lassen."
    fi
}

# First topo-root package id from manifest (for add-addon default base)
swpm_family_default_base_id() {
    local m="$1"
    local tools_dir first
    tools_dir="$(swpm_family_tools_dir)"
    # --json: kein order|head-1 (SIGPIPE/pipefail). Nur bei ok:true; WARNs/ERRs auf stderr.
    first=$(
        python3 "$tools_dir/check-family-deps.py" --manifest "$m" --mode order --json \
            | python3 -c "
import sys, json
d = json.load(sys.stdin)
for w in d.get('warnings') or []:
    print(f'WARN: {w}', file=sys.stderr)
if not d.get('ok'):
    for e in d.get('errors') or []:
        print(f'ERR: {e}', file=sys.stderr)
    sys.exit(1)
pkgs = d.get('packages') or []
if not pkgs:
    sys.exit(1)
print(pkgs[0]['id'])
"
    ) || true
    if [ -n "$first" ]; then
        echo "$first"
        return 0
    fi
    return 1
}

# Path relative to manifest directory (for paths[] entries)
swpm_rel_to_manifest() {
    local manifest_file="$1"
    local target="$2"
    python3 -c "import os.path; print(os.path.relpath('''$target''', '''$(dirname "$manifest_file")'''))"
}

cmd_list_order_check() {
    local mode="$1"
    local m
    m="$(swpm_family_find_manifest "$MANIFEST_ARG")" || exit 1
    swpm_family_run_deps "$mode" "$m"
}

write_package_xml() {
    local dest="$1"
    local pkg_id="$2"
    local pkg_name="$3"
    local extra_req="${4:-}"  # optional XML lines for extra requiredpackage
    local stub_root
    stub_root="$(dirname "$(dirname "$dest")")"

    mkdir -p "$(dirname "$dest")"
    # Minimal lib/ so PIP/file instruction has a syncable source (SoC: stub = graph + packable shell)
    mkdir -p "$stub_root/temp_edit/lib"
    : >"$stub_root/temp_edit/lib/.gitkeep"

    cat >"$dest" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<package name="${pkg_id}" xmlns="http://www.woltlab.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.woltlab.com http://www.woltlab.com/XSD/6.0/package.xsd">
	<packageinformation>
		<packagename>${pkg_name}</packagename>
		<version>0.1.0</version>
		<date>$(date +%Y-%m-%d)</date>
	</packageinformation>
	<authorinformation>
		<author>SWPM Scaffold</author>
	</authorinformation>
	<requiredpackages>
		<requiredpackage minversion="${WCF_MIN}">com.woltlab.wcf</requiredpackage>
${extra_req}	</requiredpackages>
	<instructions type="install">
		<instruction type="file">files.tar</instruction>
	</instructions>
</package>
EOF
}

cmd_init() {
    local parent="${PARENT:-$MAIN_DIR}"
    parent="$(cd "$parent" && pwd)"
    local line_id="${LINE_ID:-${BASE_ID}.line}"
    local manifest_out="$parent/swpm-family.json"
    if [ -n "$MANIFEST_ARG" ]; then
        manifest_out="$MANIFEST_ARG"
        if [[ "$manifest_out" != /* ]]; then
            manifest_out="$parent/$manifest_out"
        fi
    fi

    local paths_json="[]"
    local path_entries=()

    if [ "$SCAFFOLD" -eq 1 ]; then
        local base_root="$parent/$BASE_DIR"
        local base_xml="$base_root/temp_edit/package.xml"
        if [ -e "$base_root" ] && [ "$FORCE" -ne 1 ]; then
            print_error "Existiert bereits: $base_root (nutze --force)"
            exit 1
        fi
        write_package_xml "$base_xml" "$BASE_ID" "Scaffold Base" ""
        path_entries+=("$(swpm_rel_to_manifest "$manifest_out" "$base_root")")
        print_success "Basis-Stub: $base_root"

        local addon
        IFS=',' read -ra addon_list <<<"${ADDONS_CSV:-}"
        for addon in "${addon_list[@]}"; do
            addon="$(echo "$addon" | tr -d '[:space:]')"
            [ -z "$addon" ] && continue
            local addon_root="$parent/$addon"
            local addon_id="${BASE_ID}.${addon##*-}"
            # slug myapp-specials → prefer full: com.vendor.myapp.specials if BASE_ID=com.vendor.myapp
            if [[ "$addon" == *"-"* ]]; then
                local suffix="${addon#*-}"
                addon_id="${BASE_ID}.${suffix}"
            else
                addon_id="${BASE_ID}.${addon}"
            fi
            if [ -e "$addon_root" ] && [ "$FORCE" -ne 1 ]; then
                print_error "Existiert bereits: $addon_root (nutze --force)"
                exit 1
            fi
            local extra
            extra="		<requiredpackage minversion=\"0.1.0\">${BASE_ID}</requiredpackage>"$'\n'
            write_package_xml "$addon_root/temp_edit/package.xml" "$addon_id" "Scaffold Add-on ${addon}" "$extra"
            path_entries+=("$(swpm_rel_to_manifest "$manifest_out" "$addon_root")")
            print_success "Add-on-Stub: $addon_root ($addon_id)"
        done
    else
        # Manifest-only: use --base-dir and --addons as path hints if given
        path_entries+=("$BASE_DIR")
        local addon
        IFS=',' read -ra addon_list <<<"${ADDONS_CSV:-}"
        for addon in "${addon_list[@]}"; do
            addon="$(echo "$addon" | tr -d '[:space:]')"
            [ -n "$addon" ] && path_entries+=("$addon")
        done
        print_warning "Nur Manifest — Ordner unter paths müssen bereits existieren (sonst scheitert family:check)."
    fi

    if [ -f "$manifest_out" ] && [ "$FORCE" -ne 1 ]; then
        print_error "Manifest existiert: $manifest_out (nutze --force)"
        exit 1
    fi

    # Build JSON paths array
    local json_paths=""
    local p first=1
    for p in "${path_entries[@]}"; do
        if [ "$first" -eq 1 ]; then
            json_paths="\"$p\""
            first=0
        else
            json_paths="$json_paths, \"$p\""
        fi
    done

    mkdir -p "$(dirname "$manifest_out")"
    cat >"$manifest_out" <<EOF
{
  "schemaVersion": 1,
  "id": "${line_id}",
  "versionStrategy": "independent",
  "paths": [ ${json_paths} ],
  "packages": []
}
EOF
    print_success "Manifest: $manifest_out"
    if [ "$SCAFFOLD" -eq 1 ]; then
        print_info "Als Nächstes: ./tools/swpm-family.sh --manifest \"$manifest_out\" check"
        print_info "Hinweis: Scaffold-Stubs haben noch keine Plugin-Quellen — family:validate/build erst nach echtem Layout (files/lib/…)."
    else
        print_info "paths zeigen auf bestehende Paket-Roots — bei Bedarf --scaffold nutzen."
    fi
}

cmd_add_addon() {
    local slug="${REST[0]:-}"
    if [ -z "$slug" ]; then
        print_error "Usage: swpm-family.sh add-addon <slug>"
        exit 1
    fi
    local parent="${PARENT:-$MAIN_DIR}"
    parent="$(cd "$parent" && pwd)"
    local m
    m="$(swpm_family_find_manifest "$MANIFEST_ARG")" || {
        print_error "Kein Manifest — zuerst family:init"
        exit 1
    }
    if [ "$BASE_ID_SET" -ne 1 ]; then
        local derived=""
        derived=$(swpm_family_default_base_id "$m" || true)
        if [ -n "$derived" ]; then
            BASE_ID="$derived"
            print_info "Basis-ID aus Familie (Topo-Root): $BASE_ID"
        else
            print_warning "Konnte Basis-ID nicht aus Manifest ableiten — Fallback: $BASE_ID (besser --base-id setzen)"
        fi
    fi
    local addon_root="$parent/$slug"
    local suffix="${slug#*-}"
    [[ "$slug" != *"-"* ]] && suffix="$slug"
    local addon_id="${BASE_ID}.${suffix}"
    if [ -e "$addon_root" ] && [ "$FORCE" -ne 1 ]; then
        print_error "Existiert bereits: $addon_root"
        exit 1
    fi
    local extra
    extra="		<requiredpackage minversion=\"0.1.0\">${BASE_ID}</requiredpackage>"$'\n'
    write_package_xml "$addon_root/temp_edit/package.xml" "$addon_id" "Scaffold Add-on ${slug}" "$extra"
    print_success "Add-on-Stub: $addon_root ($addon_id → requires $BASE_ID)"

    # Append path relative to manifest dir
    local rel
    rel=$(swpm_rel_to_manifest "$m" "$addon_root")
    python3 - "$m" "$rel" <<'PY'
import json, sys
path = sys.argv[1]
rel = sys.argv[2]
data = json.loads(open(path, encoding="utf-8").read())
paths = list(data.get("paths") or [])
if rel not in paths:
    paths.append(rel)
    data["paths"] = paths
    open(path, "w", encoding="utf-8").write(json.dumps(data, indent=2, ensure_ascii=False) + "\n")
    print(f"Manifest paths += {rel}")
else:
    print(f"Manifest enthält {rel} bereits")
PY
}

cmd_build() {
    warn_env_overrides
    export SWPM_FAMILY_RUN=1
    local version_type="${REST[0]:-patch}"
    if [[ ! "$version_type" =~ ^(patch|minor|major|same)$ ]]; then
        print_error "Version-Typ: patch|minor|major|same (ist: $version_type)"
        exit 1
    fi
    local m
    m="$(swpm_family_find_manifest "$MANIFEST_ARG")" || exit 1
    print_info "Family Dep-Check vor Build..."
    if ! swpm_family_run_deps check "$m"; then
        print_error "Family-Check fehlgeschlagen — kein Build"
        exit 2
    fi

    # Root-Layout (package.xml im Paket-Root, kein temp_edit/): Staging mit Symlink,
    # damit build.sh weiter temp_edit/ erwarten kann — ohne Rekursion im Quellbaum.
    swpm_family_build_path() {
        local src="$1"
        local abs stage slug
        abs="$(cd "$src" && pwd)"
        if [ -d "$abs/temp_edit" ]; then
            echo "$abs"
            return 0
        fi
        if [ -f "$abs/package.xml" ]; then
            slug="$(basename "$abs")"
            stage="$MAIN_DIR/_family-stage/$slug"
            mkdir -p "$stage"
            ln -sfn "$abs" "$stage/temp_edit"
            echo "$stage"
            return 0
        fi
        echo "$abs"
    }

    local fail=0
    local id path ver build_path tar_out
    while IFS=$'\t' read -r id path ver; do
        [ -z "$id" ] && continue
        build_path="$(swpm_family_build_path "$path")"
        print_info "Build: $id ($path) [$version_type]"
        if [ "$build_path" != "$(cd "$path" && pwd)" ]; then
            print_info "Root-Layout → Staging: $build_path"
        fi
        # Unset overrides for this invocation
        if env -u WOLTLAB_PACKAGE_ID -u WOLTLAB_APP_ABBREV \
            bash "$SCRIPT_DIR/build.sh" "$build_path" "$version_type"; then
            tar_out="$(swpm_find_latest_package "$MAIN_DIR" "$build_path" "${id}_v*.tar.gz" || true)"
            if [ -n "$tar_out" ] && [ -f "$tar_out" ]; then
                print_info "Paket: $tar_out"
            fi
            print_success "OK: $id"
        else
            print_error "Build fehlgeschlagen: $id"
            fail=1
            [ "$CONTINUE" -eq 1 ] || exit 2
        fi
    done < <(swpm_family_run_deps order "$m")

    [ "$fail" -eq 0 ] || exit 2
    print_success "Family-Build abgeschlossen"
}

cmd_validate() {
    warn_env_overrides
    export SWPM_FAMILY_RUN=1
    local m
    m="$(swpm_family_find_manifest "$MANIFEST_ARG")" || exit 1
    print_info "Family Dep-Check..."
    if ! swpm_family_run_deps check "$m"; then
        print_error "Family-Check fehlgeschlagen"
        exit 2
    fi

    local fail=0
    local id path ver
    while IFS=$'\t' read -r id path ver; do
        [ -z "$id" ] && continue
        print_info "Validate: $id"
        local args=()
        [ "$STRICT" -eq 1 ] && args+=(--strict)
        args+=("$path")
        if env -u WOLTLAB_PACKAGE_ID -u WOLTLAB_APP_ABBREV \
            bash "$SCRIPT_DIR/validate-plugin.sh" "${args[@]}"; then
            print_success "OK: $id"
        else
            print_error "Validate fehlgeschlagen: $id"
            fail=1
            [ "$CONTINUE" -eq 1 ] || exit 2
        fi
    done < <(swpm_family_run_deps order "$m")

    [ "$fail" -eq 0 ] || exit 2
    print_success "Family-Validate abgeschlossen"
}

case "${CMD:-}" in
    list) cmd_list_order_check list ;;
    order) cmd_list_order_check order ;;
    check) cmd_list_order_check check ;;
    build) cmd_build ;;
    validate) cmd_validate ;;
    init) cmd_init ;;
    add-addon) cmd_add_addon ;;
    *)
        usage
        exit 1
        ;;
esac
