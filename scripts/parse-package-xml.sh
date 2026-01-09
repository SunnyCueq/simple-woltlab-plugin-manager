#!/bin/bash

# Simple WoltLab Plugin Manager - Package.xml Parser
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
#
# Parst package.xml und extrahiert alle Instructions
# Verwendung: parse_package_xml "package.xml"

# Lade PIP-Defaults
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/pip-defaults.sh"

# Parst package.xml und gibt alle Instructions zurück
# Verwendung: parse_package_instructions "package.xml"
parse_package_instructions() {
    local package_xml="$1"
    
    if [ ! -f "$package_xml" ]; then
        echo "❌ Fehler: package.xml nicht gefunden: $package_xml" >&2
        return 1
    fi
    
    # Extrahiere alle <instruction type="..."> Tags
    # Unterstützt sowohl <instruction type="file" /> als auch <instruction type="file">files.tar</instruction>
    grep -oP '<instruction\s+type="\K[^"]+' "$package_xml" 2>/dev/null | sort -u
}

# Gibt alle Dateien zurück, die für das Package benötigt werden
# Verwendung: get_required_files "package.xml" "PLUGIN_DIR"
get_required_files() {
    local package_xml="$1"
    local plugin_dir="${2:-$(pwd)}"
    
    local instructions
    instructions=$(parse_package_instructions "$package_xml")
    
    if [ -z "$instructions" ]; then
        echo "⚠️  Warnung: Keine Instructions in package.xml gefunden" >&2
        return 1
    fi
    
    local files=()
    local found_any=false
    
    # package.xml ist immer erforderlich
    files+=("package.xml")
    
    # Parse jede Instruction
    while IFS= read -r instruction_type; do
        if [ -z "$instruction_type" ]; then
            continue
        fi
        
        # Hole Standard-Dateiname für diesen PIP-Typ
        local default_file
        default_file=$(get_pip_default_file "$instruction_type")
        
        if [ -z "$default_file" ]; then
            # Kein Standard-Dateiname - versuche {type}.xml
            if [ -f "$plugin_dir/${instruction_type}.xml" ]; then
                files+=("${instruction_type}.xml")
                found_any=true
            else
                echo "⚠️  Warnung: Kein Standard-Dateiname für PIP-Typ '$instruction_type' gefunden" >&2
            fi
            continue
        fi
        
        # Prüfe ob Datei/Verzeichnis existiert (case-insensitive)
        local found_file=""
        
        if is_tar_pip "$instruction_type"; then
            # Suche TAR-Datei (case-insensitive)
            found_file=$(find "$plugin_dir" -maxdepth 1 -iname "$default_file" -type f | head -1)
            if [ -n "$found_file" ]; then
                files+=("$(basename "$found_file")")
                found_any=true
            else
                echo "⚠️  Warnung: $default_file nicht gefunden für PIP-Typ '$instruction_type'" >&2
            fi
        elif is_directory_pip "$instruction_type"; then
            # Prüfe ob Verzeichnis existiert (case-insensitive)
            found_file=$(find "$plugin_dir" -maxdepth 1 -iname "$default_file" -type d | head -1)
            if [ -n "$found_file" ]; then
                files+=("$(basename "$found_file")")
                found_any=true
            else
                echo "⚠️  Warnung: Verzeichnis '$default_file' nicht gefunden für PIP-Typ '$instruction_type'" >&2
            fi
        else
            # XML-Datei oder andere Dateien
            found_file=$(find "$plugin_dir" -maxdepth 1 -iname "$default_file" -type f | head -1)
            if [ -n "$found_file" ]; then
                files+=("$(basename "$found_file")")
                found_any=true
            else
                # Für XML-PIPs: Prüfe ob die Datei im package.xml explizit angegeben ist
                local explicit_file
                explicit_file=$(grep -oP "<instruction\s+type=\"$instruction_type\"[^>]*>\K[^<]+" "$package_xml" 2>/dev/null | head -1 | xargs)
                if [ -n "$explicit_file" ] && [ -f "$plugin_dir/$explicit_file" ]; then
                    files+=("$explicit_file")
                    found_any=true
                else
                    echo "⚠️  Warnung: $default_file nicht gefunden für PIP-Typ '$instruction_type'" >&2
                fi
            fi
        fi
    done <<< "$instructions"
    
    if [ "$found_any" = false ] && [ ${#files[@]} -eq 1 ]; then
        # Nur package.xml gefunden, keine anderen Dateien
        echo "⚠️  Warnung: Nur package.xml gefunden, keine anderen Dateien für Instructions" >&2
    fi
    
    # Gebe alle gefundenen Dateien zurück (unique)
    printf '%s\n' "${files[@]}" | sort -u
}

# Zeigt die Package-Struktur an
# Verwendung: show_package_structure "package.xml" "PLUGIN_DIR"
show_package_structure() {
    local package_xml="$1"
    local plugin_dir="${2:-$(pwd)}"
    
    echo "📦 Package-Struktur:"
    echo ""
    
    local files
    files=$(get_required_files "$package_xml" "$plugin_dir")
    
    if [ -z "$files" ]; then
        echo "  ❌ Keine Dateien gefunden"
        return 1
    fi
    
    local all_found=true
    
    while IFS= read -r file; do
        if [ -z "$file" ]; then
            continue
        fi
        
        local file_path="$plugin_dir/$file"
        
        if [ -f "$file_path" ]; then
            local size
            size=$(du -h "$file_path" 2>/dev/null | cut -f1)
            echo "  ✅ $file ($size)"
        elif [ -d "$file_path" ]; then
            local file_count
            file_count=$(find "$file_path" -type f 2>/dev/null | wc -l)
            echo "  📁 $file/ ($file_count Dateien)"
        else
            echo "  ⚠️  $file (nicht gefunden)"
            all_found=false
        fi
    done <<< "$files"
    
    echo ""
    
    if [ "$all_found" = false ]; then
        echo "⚠️  Einige Dateien wurden nicht gefunden!"
        return 1
    fi
    
    return 0
}

