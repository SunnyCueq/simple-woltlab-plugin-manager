#!/usr/bin/env bash
#
# WoltLab Plugin Manager – Developer Tools
#
#   ./tools.sh                  → interaktives Menü
#   ./tools.sh help             → alle Befehle
#   ./tools.sh build            → Paket bauen (Patch-Version)
#   ./tools.sh build:same       → bauen ohne Versionserhoehung (Entwicklung)
#   ./tools.sh update-paket     → Update-Paket (= Patch, gleich wie build)
#   ./tools.sh build:minor      → Minor-Version
#   ./tools.sh typescript       → TS → JS (basis-plugin)
#
# Wichtige Befehle:
#   build / build:patch / build:minor / build:major
#   typescript | ts
#   unpack
#   validate
#   setup
#   push | gitpush
#   docs
#

set -e

readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$TOOLS_DIR")"
readonly SETUP_DONE_FILE="$TOOLS_DIR/.woltlab-setup-done"

# shellcheck source=common.sh
source "$TOOLS_DIR/common.sh"

# ── Optik (CSRetro-ähnlich) ─────────────────────────────────────────────────

tools_divider() {
	echo -e "\033[0;90m ────────────────────────────────────────────\033[0m"
}

tools_header() {
	clear 2>/dev/null || true
	echo ""
	echo -e "${BOLD}${MAGENTA} ╔══════════════════════════════════════════════╗${RESET}"
	echo -e "${BOLD}${MAGENTA} ║  WoltLab Plugin Manager – Tools              ║${RESET}"
	echo -e "${BOLD}${MAGENTA} ╚══════════════════════════════════════════════╝${RESET}"
	echo ""
}

# Plugin-Stamm für build.sh (Ordnername unter MAIN_DIR, z. B. basis-plugin)
plugin_slug_for_build() {
	local p="$1"
	if [ "$(basename "$p")" = "temp_edit" ]; then
		basename "$(dirname "$p")"
	else
		basename "$p"
	fi
}

# Kurzliste der Plugins (eine Zeile pro Eintrag, max. 5)
print_plugins_summary() {
	local plugins=()
	mapfile -t plugins < <(find_plugin_directories "$MAIN_DIR" 2>/dev/null || true)
	local n="${#plugins[@]}"
	if [ "$n" -eq 0 ]; then
		echo -e " ${YELLOW}○${RESET} Kein Plugin mit package.xml gefunden."
		return
	fi
	local i=0
	for p in "${plugins[@]}"; do
		[ "$i" -ge 5 ] && {
			echo -e " \033[0;90m… und weitere\033[0m"
			break
		}
		local ver name rel root xml_dir
		root="$p"
		[ "$(basename "$p")" = "temp_edit" ] && root="$(dirname "$p")"
		xml_dir="$root"
		[ -f "$root/temp_edit/package.xml" ] && xml_dir="$root/temp_edit"
		ver=$(get_plugin_version "$xml_dir" 2>/dev/null || echo "?")
		name=$(get_plugin_name "$xml_dir" 2>/dev/null || echo "?")
		rel="${root#$MAIN_DIR/}"
		echo -e " ${GREEN}●${RESET} ${BOLD}${name}${RESET} ${YELLOW}v${ver}${RESET} ${BLUE}[${rel}]${RESET}"
		i=$((i + 1))
	done
}

ensure_executable() {
	local script_path="$1"
	[ -f "$script_path" ] || return 1
	[ -x "$script_path" ] || chmod +x "$script_path" 2>/dev/null || true
}

run_tool() {
	local script_path="$1"
	shift
	ensure_executable "$script_path" || {
		print_error "Nicht ausführbar: $script_path"
		return 1
	}
	"$script_path" "$@"
}

# ── CLI: Hilfe ────────────────────────────────────────────────────────────────

cmd_help() {
	echo ""
	echo -e " ${BOLD}WoltLab Plugin Manager${RESET} (${MAIN_DIR})"
	echo ""
	echo -e " ${BOLD}Build & Assets${RESET}"
	echo "  build | build:patch [args…]   → tools/build.sh patch"
	echo "  build:same | build:dev [args…] → Build ohne Versionserhoehung (Entwicklung)"
	echo "  build:update | update-paket | update  → Update-Paket (Patch; Store-Update)"
	echo "  build:minor | build:major"
	echo "  build:dry-run                 → Paketinhalt ohne Build"
	echo "  typescript | ts [args…]       → tools/typescript.sh"
	echo "  unpack [args…]              → tools/unpack.sh"
	echo ""
	echo -e " ${BOLD}Qualität & Setup${RESET}"
	echo "  validate [pfad]               → tools/validate-plugin.sh"
	echo "  setup                         → tools/setup-minimal.sh"
	echo ""
	echo -e " ${BOLD}Git & Repo${RESET}"
	echo "  push | gitpush [args…]        → tools/gitpush.sh"
	echo "  repo                          → origin anzeigen / setzen"
	echo ""
	echo -e " ${BOLD}Sonstiges${RESET}"
	echo "  docs                          → Hilfe / README (help.sh)"
	echo "  wcf-version                   → Core/Docs/GitHub-Version (update-woltlab-version.sh)"
	echo "  sync-woltlab-refs [6.2]       → Nur Git-Spiegel (docs, github, d.ts) aktualisieren"
	echo "  lang                          → Menü-Sprache de/en (.env)"
	echo "  manager-push                  → Maintainer: Manager-Repo (falls vorhanden)"
	echo "  menu                          → Interaktives Menü"
	echo ""
	echo -e " ${DIM}Ohne Argumente: interaktives Menü. Siehe README im Repo.${RESET}"
	echo ""
}

# ── CLI: Repo ─────────────────────────────────────────────────────────────────

cmd_repo() {
	echo -e "${CYAN}Aktuelles Repository (Push):${RESET}"
	echo -e "  ${YELLOW}$(get_git_repo_display)${RESET}"
	echo ""
	read -r -p "origin ändern? (j/N): " ch
	ch=${ch:-n}
	if [[ "$ch" =~ ^[jJyY] ]]; then
		read -r -p "Neue URL: " url
		url=$(echo "$url" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
		if [ -n "$url" ]; then
			env_set "GIT_REPO_URL" "$url"
			print_success "GIT_REPO_URL gespeichert."
			if [ -d "$MAIN_DIR/.git" ]; then
				if git -C "$MAIN_DIR" remote get-url origin &>/dev/null; then
					git -C "$MAIN_DIR" remote set-url origin "$url"
				else
					git -C "$MAIN_DIR" remote add origin "$url"
				fi
				print_success "git origin aktualisiert."
			fi
		fi
	fi
}

cmd_lang() {
	echo -e "Aktuell: ${CYAN}${WOLTLAB_LANG:-en}${RESET}"
	read -r -p "Sprache (de/en): " l
	l="${l:-${WOLTLAB_LANG:-en}}"
	if [[ "${l,,}" == de ]]; then
		env_set "WOLTLAB_LANG" "de"
		export WOLTLAB_LANG=de
		print_success "Sprache: DE"
	elif [[ "${l,,}" == en ]]; then
		env_set "WOLTLAB_LANG" "en"
		export WOLTLAB_LANG=en
		print_success "Sprache: EN"
	else
		print_warning "Nur de oder en."
	fi
}

# ── Interaktiv: Wrapper (ohne „Zurück“-Overkill) ─────────────────────────────

run_build_interactive() {
	print_header 2>/dev/null || tools_header
	print_section "Build" "Tools" "Build"
	local plugins=()
	mapfile -t plugins < <(find_plugin_directories "$MAIN_DIR" 2>/dev/null || true)
	if [ "${#plugins[@]}" -gt 0 ]; then
		echo -e "${YELLOW}Plugins:${NC}"
		local i=1
		for plugin_path in "${plugins[@]}"; do
			local root="$plugin_path" xml_dir
			[ "$(basename "$plugin_path")" = "temp_edit" ] && root="$(dirname "$plugin_path")"
			xml_dir="$root"
			[ -f "$root/temp_edit/package.xml" ] && xml_dir="$root/temp_edit"
			echo -e "   ${CYAN}${i})${NC} $(get_plugin_name "$xml_dir") ${YELLOW}($(get_plugin_version "$xml_dir"))${NC}"
			i=$((i + 1))
		done
		echo ""
	fi
	echo -e "${YELLOW}Ziel:${NC} [Enter]=auto  |  basis-plugin  |  all"
	read -r build_target
	build_target=${build_target:-auto}
	echo -e "${YELLOW}Version:${NC} patch | minor | major | same  [patch]"
	read -r version_type
	version_type=${version_type:-patch}
	read -r -p "Fortfahren? (j/N): " ok
	[[ "${ok:-n}" =~ ^[jJyY] ]] || return 0
	if [ -z "$build_target" ] || [ "$build_target" = "auto" ]; then
		run_tool "$TOOLS_DIR/build.sh" "$version_type"
	elif [ "$build_target" = "all" ]; then
		local p slug
		for p in "${plugins[@]}"; do
			slug=$(plugin_slug_for_build "$p")
			print_info "Build: ${slug} (${version_type})"
			run_tool "$TOOLS_DIR/build.sh" "$slug" "$version_type" || print_warning "Build fehlgeschlagen: ${slug}"
		done
	else
		run_tool "$TOOLS_DIR/build.sh" "$build_target" "$version_type"
	fi
}

run_push_interactive() {
	print_header 2>/dev/null || tools_header
	print_section "Git Push" "Tools" "Push"
	read -r -p "Ziel [auto]: " push_target
	push_target=${push_target:-auto}
	read -r -p "Commit-Nachricht (leer = auto): " commit_msg
	read -r -p "Fortfahren? (j/N): " ok
	[[ "${ok:-n}" =~ ^[jJyY] ]] || return 0
	if [ -n "$commit_msg" ]; then
		run_tool "$TOOLS_DIR/gitpush.sh" "$push_target" "$commit_msg"
	else
		run_tool "$TOOLS_DIR/gitpush.sh" "$push_target"
	fi
}

run_validate_interactive() {
	print_header 2>/dev/null || tools_header
	print_section "Validierung" "Tools" "Validate"
	read -r -p "Plugin-Verzeichnis (leer = aktuell): " validate_target
	read -r -p "Fortfahren? (j/N): " ok
	[[ "${ok:-n}" =~ ^[jJyY] ]] || return 0
	if [ -n "$validate_target" ]; then
		if [[ "$validate_target" =~ ^/ ]]; then
			run_tool "$TOOLS_DIR/validate-plugin.sh" "$validate_target"
		else
			run_tool "$TOOLS_DIR/validate-plugin.sh" "$MAIN_DIR/$validate_target"
		fi
	else
		run_tool "$TOOLS_DIR/validate-plugin.sh"
	fi
}

# ── Menü ────────────────────────────────────────────────────────────────────

show_interactive_menu() {
	tools_header
	show_system_overview 2>/dev/null || true
	echo -e " ${BOLD}Plugins${RESET}"
	print_plugins_summary
	echo ""
	tools_divider
	echo -e " ${BOLD}ENTWICKLUNG${RESET}"
	echo -e " ${CYAN}1${RESET} Build / Update-Paket ${DIM}→ patch/minor/major/same (build.sh)${RESET}"
	echo -e " ${CYAN}2${RESET} TypeScript         ${DIM}→ tools/typescript.sh${RESET}"
	echo -e " ${CYAN}3${RESET} Unpack             ${DIM}→ Paket nach temp_edit/${RESET}"
	tools_divider
	echo -e " ${BOLD}QUALITÄT & DOKU${RESET}"
	echo -e " ${CYAN}4${RESET} Plugin validieren  ${DIM}→ Store-Kriterien${RESET}"
	echo -e " ${CYAN}5${RESET} Hilfe / Doku       ${DIM}→ help.sh${RESET}"
	tools_divider
	echo -e " ${BOLD}REPO & UMGEBUNG${RESET}"
	echo -e " ${CYAN}6${RESET} Git Push           ${DIM}→ gitpush.sh${RESET}"
	echo -e " ${CYAN}7${RESET} Setup              ${DIM}→ Core, Docs, Pfade${RESET}"
	echo -e " ${CYAN}8${RESET} Repo (origin)      ${DIM}→ URL anzeigen/setzen${RESET}"
	echo -e " ${CYAN}9${RESET} WoltLab-Version    ${DIM}→ Core/Docs sync${RESET}"
	echo -e " ${CYAN}L${RESET} Sprache DE/EN"
	if [ -f "$TOOLS_DIR/manager-push.sh" ]; then
		echo -e " ${CYAN}M${RESET} Manager Push       ${DIM}(Maintainer)${RESET}"
	fi
	tools_divider
	echo -e " ${CYAN}0${RESET} Beenden"
	echo ""
	printf " ${BOLD}→${RESET} "
}

run_menu_loop() {
	while true; do
		show_interactive_menu
		read -r choice
		echo ""
		case "$choice" in
			1) run_build_interactive ;;
			2)
				print_header 2>/dev/null || tools_header
				print_section "TypeScript" "Tools" "TS"
				echo "  1 Kompilieren   2 Watch   0 Zurück"
				read -r ts_c
				case "$ts_c" in
					2) run_tool "$TOOLS_DIR/typescript.sh" watch ;;
					0) continue ;;
					*) run_tool "$TOOLS_DIR/typescript.sh" ;;
				esac
				;;
			3)
				print_header 2>/dev/null || tools_header
				print_section "Unpack" "Tools" "Unpack"
				read -r -p "Plugin [auto]: " up_pl
				read -r -p "Paket .tar.gz (optional): " up_pkg
				read -r -p "Fortfahren? (j/N): " ok
				[[ "${ok:-n}" =~ ^[jJyY] ]] || continue
				if [ -n "$up_pkg" ]; then
					run_tool "$TOOLS_DIR/unpack.sh" "$up_pl" "$up_pkg"
				elif [ -n "$up_pl" ]; then
					run_tool "$TOOLS_DIR/unpack.sh" "$up_pl"
				else
					run_tool "$TOOLS_DIR/unpack.sh"
				fi
				;;
			4) run_validate_interactive ;;
			5)
				print_header 2>/dev/null || tools_header
				run_tool "$TOOLS_DIR/help.sh"
				;;
			6) run_push_interactive ;;
			7)
				print_header 2>/dev/null || tools_header
				run_tool "$TOOLS_DIR/setup-minimal.sh"
				;;
			8) cmd_repo ;;
			9)
				print_header 2>/dev/null || tools_header
				if [ -x "$TOOLS_DIR/update-woltlab-version.sh" ]; then
					"$TOOLS_DIR/update-woltlab-version.sh"
				else
					chmod +x "$TOOLS_DIR/update-woltlab-version.sh" 2>/dev/null
					"$TOOLS_DIR/update-woltlab-version.sh"
				fi
				;;
			l | L) cmd_lang ;;
			m | M)
				[ -f "$TOOLS_DIR/manager-push.sh" ] || {
					print_error "manager-push.sh fehlt"
					sleep 1
					continue
				}
				read -r -p "Manager-Repo pushen? (j/N): " ok
				[[ "${ok:-n}" =~ ^[jJyY] ]] || continue
				run_tool "$TOOLS_DIR/manager-push.sh"
				;;
			0)
				echo -e "${GREEN}Tschüss.${RESET}"
				exit 0
				;;
			*)
				print_error "Ungültige Eingabe."
				sleep 1
				;;
		esac
		echo ""
		read -r -p "[Enter] Menü …" _
	done
}

# ── CLI Dispatcher ────────────────────────────────────────────────────────────

if [ "$#" -gt 0 ]; then
	case "$1" in
	help | --help | -h | h)
		cmd_help
		exit 0
		;;
	build | build:patch | build:update | update-paket | update)
		shift
		run_tool "$TOOLS_DIR/build.sh" patch "$@"
		exit $?
		;;
	build:same | build:dev)
		shift
		run_tool "$TOOLS_DIR/build.sh" same "$@"
		exit $?
		;;
	build:minor)
		shift
		run_tool "$TOOLS_DIR/build.sh" minor "$@"
		exit $?
		;;
	build:major)
		shift
		run_tool "$TOOLS_DIR/build.sh" major "$@"
		exit $?
		;;
	build:dry-run | dry-run | --dry-run)
		shift
		run_tool "$TOOLS_DIR/build.sh" --dry-run "$@"
		exit $?
		;;
	typescript | ts)
		shift
		run_tool "$TOOLS_DIR/typescript.sh" "$@"
		exit $?
		;;
	unpack)
		shift
		run_tool "$TOOLS_DIR/unpack.sh" "$@"
		exit $?
		;;
	validate | check)
		shift
		run_tool "$TOOLS_DIR/validate-plugin.sh" "$@"
		exit $?
		;;
	setup)
		shift
		run_tool "$TOOLS_DIR/setup-minimal.sh" "$@"
		exit $?
		;;
	push | gitpush)
		shift
		run_tool "$TOOLS_DIR/gitpush.sh" "$@"
		exit $?
		;;
	docs | help-docs)
		shift
		run_tool "$TOOLS_DIR/help.sh" "$@"
		exit $?
		;;
	wcf-version | update-version | sync-wcf)
		shift
		run_tool "$TOOLS_DIR/update-woltlab-version.sh" "$@"
		exit $?
		;;
	sync-woltlab-refs | sync-refs)
		shift
		run_tool "$TOOLS_DIR/sync-woltlab-references.sh" "$@"
		exit $?
		;;
	copywriting | copy | texts)
		shift
		if [ $# -eq 0 ]; then
			run_tool "$TOOLS_DIR/copywriting/run.sh" --project "$MAIN_DIR/basis-plugin"
		else
			run_tool "$TOOLS_DIR/copywriting/run.sh" "$@"
		fi
		exit $?
		;;
	copywriting:apply | copywriting-apply)
		shift
		if [ $# -eq 0 ]; then
			run_tool "$TOOLS_DIR/copywriting/run.sh" apply --project "$MAIN_DIR/basis-plugin"
		else
			run_tool "$TOOLS_DIR/copywriting/run.sh" apply "$@"
		fi
		exit $?
		;;
	repo)
		cmd_repo
		exit 0
		;;
	lang)
		cmd_lang
		exit 0
		;;
	manager-push)
		shift
		run_tool "$TOOLS_DIR/manager-push.sh" "$@"
		exit $?
		;;
	menu | ui)
		run_menu_loop
		exit 0
		;;
	*)
		print_error "Unbekannt: $1  (./tools.sh help)"
		exit 1
		;;
	esac
fi

# Keine Argumente → Menü
run_menu_loop
