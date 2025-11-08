#!/bin/bash

# Simple WoltLab Plugin Manager - PIP Default Filenames
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
#
# Mapping von WoltLab PIP-Typen zu ihren Standard-Dateinamen
# Basierend auf WoltLab Suite 6.0 Dokumentation

# Gibt den Standard-Dateinamen für einen PIP-Typ zurück
# Verwendung: get_pip_default_file "file"
get_pip_default_file() {
    local pip_type="$1"
    
    case "$pip_type" in
        "file")
            echo "files.tar"
            ;;
        "template")
            echo "templates.tar"
            ;;
        "acpTemplate")
            echo "acptemplates.tar"
            ;;
        "page")
            echo "page.xml"
            ;;
        "language")
            echo "language"
            ;;
        "sql")
            echo "install.sql"
            ;;
        "script")
            # Scripts haben keinen Standard-Dateinamen
            echo ""
            ;;
        "style")
            echo "style.tar"
            ;;
        "acpmenu")
            echo "acpmenu.xml"
            ;;
        "menu")
            echo "menu.xml"
            ;;
        "eventListener")
            echo "eventListener.xml"
            ;;
        "templateListener")
            echo "templateListener.xml"
            ;;
        "box")
            echo "box.xml"
            ;;
        "userOption")
            echo "userOption.xml"
            ;;
        "userGroupOption")
            echo "userGroupOption.xml"
            ;;
        "cronjob")
            echo "cronjob.xml"
            ;;
        "objectType")
            echo "objectType.xml"
            ;;
        "objectTypeDefinition")
            echo "objectTypeDefinition.xml"
            ;;
        "packageUpdateServer")
            echo "packageUpdateServer.xml"
            ;;
        "packageUpdate")
            echo "packageUpdate.xml"
            ;;
        *)
            # Für unbekannte PIPs: Versuche {type}.xml
            if [ -f "${pip_type}.xml" ]; then
                echo "${pip_type}.xml"
            else
                echo ""
            fi
            ;;
    esac
}

# Prüft ob ein PIP-Typ eine TAR-Datei erwartet
is_tar_pip() {
    local pip_type="$1"
    
    case "$pip_type" in
        "file"|"template"|"acpTemplate"|"style")
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

# Prüft ob ein PIP-Typ eine XML-Datei erwartet
is_xml_pip() {
    local pip_type="$1"
    
    case "$pip_type" in
        "page"|"acpmenu"|"menu"|"eventListener"|"templateListener"|"box"|"userOption"|"userGroupOption"|"cronjob"|"objectType"|"objectTypeDefinition"|"packageUpdateServer"|"packageUpdate")
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

# Prüft ob ein PIP-Typ ein Verzeichnis erwartet
is_directory_pip() {
    local pip_type="$1"
    
    case "$pip_type" in
        "language")
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

