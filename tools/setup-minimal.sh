#!/usr/bin/env bash

#################################################################
# Minimales Setup – WoltLab-Core, Docs, GitHub, lokaler Pfad
# Pfad: tools/setup-minimal.sh
# Fragt nacheinander ab, führt Downloads/Clones aus, schreibt tools/.env
#################################################################

set -e

readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$TOOLS_DIR")"
readonly ENV_FILE="$TOOLS_DIR/.env"
readonly SETUP_DONE_FILE="$TOOLS_DIR/.woltlab-setup-done"

if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    echo "common.sh nicht gefunden."
    exit 1
fi

# .env aus .env.example anlegen falls nicht vorhanden
if [ ! -f "$ENV_FILE" ]; then
    if [ -f "$TOOLS_DIR/.env.example" ]; then
        cp "$TOOLS_DIR/.env.example" "$ENV_FILE"
        print_info "Konfiguration angelegt: $ENV_FILE"
    else
        touch "$ENV_FILE"
    fi
fi

# env_set/env_get aus common.sh (nutzt ENV_FILE, das hier gesetzt ist)

# Workspace-Datei anpassen: Ordner „lokale WoltLab-Installation“ und Intelephense-Pfad setzen
update_workspace_local_path() {
    local local_path="$1"
    [ -z "$local_path" ] && return 0
    local workspace_file="$MAIN_DIR/woltlab-development.code-workspace"
    [ ! -f "$workspace_file" ] && return 0
    # Absoluten Pfad normalisieren (ohne trailing slash)
    local abs_path
    abs_path="$(cd "$local_path" 2>/dev/null && pwd)" || abs_path="$local_path"
    export WOLTLAB_LOCAL_PATH="$abs_path"
    export WOLTLAB_WORKSPACE_FILE="$workspace_file"
    python3 << 'PYEOF'
import json
import os
import sys
p = os.environ.get("WOLTLAB_LOCAL_PATH", "")
fpath = os.environ.get("WOLTLAB_WORKSPACE_FILE", "")
if not p or not fpath:
    sys.exit(0)
try:
    with open(fpath, "r", encoding="utf-8") as f:
        ws = json.load(f)
except (json.JSONDecodeError, OSError):
    sys.exit(1)
for folder in ws.get("folders", []):
    if folder.get("path") == "tools/woltlab-dev/public" or folder.get("name") == "⚙️ WoltLab Core (DDEV)":
        folder["name"] = "🌐 WoltLab (lokal)"
        folder["path"] = p
        break
ws.setdefault("settings", {})["intelephense.environment.includePaths"] = [p]
with open(fpath, "w", encoding="utf-8") as f:
    json.dump(ws, f, indent="\t", ensure_ascii=False)
    f.write("\n")
PYEOF
}

print_header
echo -e "${CYAN}Minimales Setup – Vorbereitung für Plugin-Entwicklung${NC}"
echo ""

# 1) WoltLab-Core (Setup-Dateien) herunterladen?
read -p "WoltLab-Core (Setup-Dateien) herunterladen? (j/n) [j]: " core_choice
core_choice=${core_choice:-j}
if [[ "$core_choice" =~ ^[jJyY] ]]; then
    if [ -x "$TOOLS_DIR/download-woltlab-core.sh" ]; then
        "$TOOLS_DIR/download-woltlab-core.sh" || print_warning "Core-Download fehlgeschlagen."
    else
        chmod +x "$TOOLS_DIR/download-woltlab-core.sh" 2>/dev/null && "$TOOLS_DIR/download-woltlab-core.sh" || print_warning "Core-Download fehlgeschlagen."
    fi
    env_set "WOLTLAB_CORE_DOWNLOADED" "1"
else
    env_set "WOLTLAB_CORE_DOWNLOADED" "0"
fi
echo ""

# 2) WoltLab-Docs klonen?
DOCS_URL=$(env_get "WOLTLAB_DOCS_URL")
[ -z "$DOCS_URL" ] && DOCS_URL="https://github.com/WoltLab/docs.woltlab.com"
read -p "WoltLab-Docs (Repo) klonen? (j/n) [n]: " docs_choice
docs_choice=${docs_choice:-n}
if [[ "$docs_choice" =~ ^[jJyY] ]]; then
    if command -v git &>/dev/null; then
        if [ -d "$MAIN_DIR/woltlab-docs" ]; then
            print_info "Vorhandenes Verzeichnis woltlab-docs wird ersetzt."
            rm -rf "$MAIN_DIR/woltlab-docs"
        fi
        print_info "Clone: $DOCS_URL"
        if git clone --depth 1 "$DOCS_URL" "$MAIN_DIR/woltlab-docs"; then
            print_success "WoltLab-Docs nach woltlab-docs/ geklont."
        else
            print_warning "Clone fehlgeschlagen."
        fi
    else
        print_warning "Git nicht gefunden. Bitte Git installieren und erneut ausführen."
    fi
fi
echo ""

# 3) WoltLab-GitHub (WCF) klonen?
GITHUB_URL=$(env_get "WOLTLAB_GITHUB_URL")
[ -z "$GITHUB_URL" ] && GITHUB_URL="https://github.com/WoltLab/WCF"
read -p "WoltLab-GitHub (WCF-Repo) klonen? (j/n) [n]: " gh_choice
gh_choice=${gh_choice:-n}
if [[ "$gh_choice" =~ ^[jJyY] ]]; then
    if command -v git &>/dev/null; then
        if [ -d "$MAIN_DIR/woltlab-github" ]; then
            print_info "Vorhandenes Verzeichnis woltlab-github wird ersetzt."
            rm -rf "$MAIN_DIR/woltlab-github"
        fi
        print_info "Clone: $GITHUB_URL"
        if git clone --depth 1 "$GITHUB_URL" "$MAIN_DIR/woltlab-github"; then
            print_success "WoltLab-GitHub nach woltlab-github/ geklont."
        else
            print_warning "Clone fehlgeschlagen."
        fi
    else
        print_warning "Git nicht gefunden."
    fi
fi
echo ""

# 4) WoltLab TypeScript-Typings (d.ts) klonen?
DTS_URL=$(env_get "WOLTLAB_DTS_URL")
[ -z "$DTS_URL" ] && DTS_URL="https://github.com/WoltLab/d.ts"
read -p "WoltLab TypeScript-Typings (d.ts) klonen? (j/n) [j]: " dts_choice
dts_choice=${dts_choice:-j}
if [[ "$dts_choice" =~ ^[jJyY] ]]; then
    if command -v git &>/dev/null; then
        if [ -d "$MAIN_DIR/woltlab-d-ts" ]; then
            print_info "Vorhandenes Verzeichnis woltlab-d-ts wird ersetzt."
            rm -rf "$MAIN_DIR/woltlab-d-ts"
        fi
        print_info "Clone: $DTS_URL"
        if git clone --depth 1 "$DTS_URL" "$MAIN_DIR/woltlab-d-ts"; then
            print_success "WoltLab d.ts nach woltlab-d-ts/ geklont."
            env_set "WOLTLAB_DTS_CLONED" "1"
            env_set "WOLTLAB_DTS_DIR" "woltlab-d-ts"
        else
            print_warning "Clone fehlgeschlagen."
        fi
    else
        print_warning "Git nicht gefunden."
    fi
fi
echo ""

# 5) Pfad zur lokalen WoltLab-Installation angeben?
read -p "Pfad zur lokalen WoltLab-Installation angeben? (j/n) [n]: " path_choice
path_choice=${path_choice:-n}
if [[ "$path_choice" =~ ^[jJyY] ]]; then
    read -p "Pfad (z. B. /var/www/woltlab oder absoluter Pfad zum installierten WoltLab): " local_path
    local_path=$(echo "$local_path" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
    if [ -n "$local_path" ]; then
        if [ -d "$local_path" ]; then
            if [ -f "$local_path/install.php" ] || [ -d "$local_path/wcf" ] || [ -f "$local_path/global.php" ]; then
                env_set "WOLTLAB_PUBLIC_DIR" "$local_path"
                print_success "Pfad in Konfiguration gespeichert: $ENV_FILE"
            else
                print_warning "Verzeichnis existiert, wirkt aber nicht wie eine WoltLab-Installation (install.php/wcf/global.php nicht gefunden). Trotzdem gespeichert."
                env_set "WOLTLAB_PUBLIC_DIR" "$local_path"
            fi
        else
            print_warning "Verzeichnis existiert nicht. Pfad trotzdem gespeichert (kann später angepasst werden)."
            env_set "WOLTLAB_PUBLIC_DIR" "$local_path"
        fi
        if command -v python3 &>/dev/null; then
            if update_workspace_local_path "$local_path"; then
                print_success "Workspace angepasst: lokale Installation im Workspace sichtbar (Workspace ggf. neu laden)."
            else
                print_warning "Workspace-Datei konnte nicht angepasst werden (z. B. ungültiges JSON). Pfad steht in $ENV_FILE."
            fi
        else
            print_warning "Python3 nicht gefunden – Workspace-Datei wurde nicht angepasst. Pfad steht in $ENV_FILE."
        fi
    fi
else
    env_set "WOLTLAB_PUBLIC_DIR" ""
fi
echo ""

# 6) Cursor MCP-Konfiguration (mcp.json) nach basis-plugin/.cursor/ kopieren?
MCP_TEMPLATE="$TOOLS_DIR/templates/mcp.json.example"
if [ -f "$MCP_TEMPLATE" ] && [ -d "$MAIN_DIR/basis-plugin" ]; then
    read -p "Cursor MCP-Konfiguration (mcp.json) nach basis-plugin/.cursor/ kopieren? (j/n) [n]: " mcp_choice
    mcp_choice=${mcp_choice:-n}
    if [[ "$mcp_choice" =~ ^[jJyY] ]]; then
        mkdir -p "$MAIN_DIR/basis-plugin/.cursor"
        if cp "$MCP_TEMPLATE" "$MAIN_DIR/basis-plugin/.cursor/mcp.json"; then
            print_success "MCP-Vorlage nach basis-plugin/.cursor/mcp.json kopiert."
        else
            print_warning "Kopieren fehlgeschlagen."
        fi
    fi
fi
echo ""

# 7) Git-Repository (origin) für diesen Workspace angeben?
read -p "Git-Repository (origin) für diesen Workspace angeben? (j/n) [n]: " repo_choice
repo_choice=${repo_choice:-n}
if [[ "$repo_choice" =~ ^[jJyY] ]]; then
    OVERWRITE_REPO="n"
    if [ -d "$MAIN_DIR/.git" ] && git -C "$MAIN_DIR" remote get-url origin >/dev/null 2>&1; then
        CURRENT_ORIGIN=$(git -C "$MAIN_DIR" remote get-url origin)
        print_info "Aktueller origin: $CURRENT_ORIGIN"
        read -p "Überschreiben? (j/n) [n]: " OVERWRITE_REPO
        OVERWRITE_REPO=${OVERWRITE_REPO:-n}
    fi
    if [[ "$OVERWRITE_REPO" =~ ^[jJyY] ]] || ! git -C "$MAIN_DIR" remote get-url origin >/dev/null 2>&1; then
        read -p "URL (z. B. https://github.com/user/repo oder git@github.com:user/repo.git): " repo_url
        repo_url=$(echo "$repo_url" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
        if [ -n "$repo_url" ]; then
            env_set "GIT_REPO_URL" "$repo_url"
            print_success "GIT_REPO_URL in Konfiguration gespeichert."
            if [ -d "$MAIN_DIR/.git" ]; then
                if git -C "$MAIN_DIR" remote get-url origin >/dev/null 2>&1; then
                    git -C "$MAIN_DIR" remote set-url origin "$repo_url"
                    print_success "Git origin aktualisiert."
                else
                    git -C "$MAIN_DIR" remote add origin "$repo_url"
                    print_success "Git origin hinzugefügt."
                fi
            fi
        fi
    fi
fi
echo ""

touch "$SETUP_DONE_FILE"
print_success "Setup abgeschlossen. Konfiguration: $ENV_FILE"
echo ""
