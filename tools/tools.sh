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

check_swpm_requirements || exit 1

# ── Optik (ui.sh) ─────────────────────────────────────────────────────────────

tools_header() {
	[ -t 0 ] && clear 2>/dev/null || true
	if declare -f ui_header &>/dev/null; then
		ui_header "Simple WoltLab Plugin Manager"
	else
		echo ""
		echo -e "${BOLD}${CYAN}╔══════════════════════════════════════════════╗${RESET}"
		echo -e "${BOLD}${CYAN}║  Simple WoltLab Plugin Manager               ║${RESET}"
		echo -e "${BOLD}${CYAN}╚══════════════════════════════════════════════╝${RESET}"
		echo ""
	fi
	local plat
	plat=$(platform_label 2>/dev/null || echo "?")
	echo -e "  ${DIM}Plattform:${RESET} ${plat}  ${DIM}│${RESET}  ${DIM}CLI:${RESET} ./tools.sh help"
	echo ""
}

tools_divider() {
	if declare -f ui_divider &>/dev/null; then
		ui_divider 52
	else
		echo -e "${DIM}────────────────────────────────────────────${RESET}"
	fi
}

plugin_xml_dir() {
	local root="$1"
	if [ -f "$root/temp_edit/package.xml" ]; then
		echo "$root/temp_edit"
	elif [ -f "$root/package.xml" ]; then
		echo "$root"
	else
		return 1
	fi
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
	local n=0
	for p in "${plugins[@]}"; do
		[ -n "$p" ] || continue
		n=$((n + 1))
	done
	if [ "$n" -eq 0 ]; then
		echo -e "  ${YELLOW}${WARN:-○}${RESET} Kein Plugin mit package.xml gefunden."
		echo -e "  ${DIM}→ Plugin-Ordner mit package.xml oder temp_edit/package.xml anlegen${RESET}"
		echo -e "  ${DIM}→ oder: Menü 3 Unpack / ./tools.sh unpack${RESET}"
		return
	fi
	local i=0
	for p in "${plugins[@]}"; do
		[ -n "$p" ] || continue
		[ "$i" -ge 5 ] && {
			echo -e "  ${DIM}… und weitere${RESET}"
			break
		}
		local ver name rel root xml_dir
		root="$p"
		[ "$(basename "$p")" = "temp_edit" ] && root="$(dirname "$p")"
		xml_dir=$(plugin_xml_dir "$root" 2>/dev/null) || continue
		ver=$(get_plugin_version "$xml_dir" 2>/dev/null || echo "?")
		name=$(get_plugin_name "$xml_dir" 2>/dev/null || echo "$(basename "$root")")
		rel="${root#$MAIN_DIR/}"
		printf "  ${GREEN}${OK:-●}${RESET} %-28s ${YELLOW}v%s${RESET} ${BLUE}[%s]${RESET}\n" "$name" "$ver" "$rel"
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
	if declare -f ui_header &>/dev/null; then
		ui_header "SWPM — CLI"
	else
		echo -e " ${BOLD}WoltLab Plugin Manager${RESET}"
	fi
	echo -e "  ${DIM}${MAIN_DIR}${RESET}"
	echo ""
	echo -e "  ${BOLD}Build & Assets${RESET}"
	echo "    build | build:patch [args…]     → tools/build.sh patch"
	echo "    build:same | build:dev [args…]  → Build ohne Versionserhöhung"
	echo "    build:update | update-paket     → Update-Paket (Patch)"
	echo "    build:minor | build:major | build:dry-run"
	echo "    typescript | ts [args…]         → tools/typescript.sh"
	echo "    unpack [args…]                  → tools/unpack.sh"
	echo "    lint:python [--fix]           → ruff auf tools/*.py (optional)"
	echo "    phpstan [plugin]                → PHPStan wenn phpstan.neon vorhanden"
	echo ""
	echo -e "  ${BOLD}Qualität & Setup${RESET}"
	echo "    validate [pfad]                 → tools/validate-plugin.sh"
	echo "    family:list | family:order | family:check"
	echo "    family:build [patch|…]          → Produktlinie (Manifest)"
	echo "    family:validate [--strict]"
	echo "    family:init [--scaffold]        → Manifest (+ optionale Stubs)"
	echo "    family:add-addon <slug>"
	echo "    setup                           → tools/setup-minimal.sh"
	echo ""
	echo -e "  ${BOLD}Git & Repo${RESET}"
	echo "    push | gitpush [args…]          → tools/gitpush.sh"
	echo "    repo                            → origin anzeigen / setzen"
	echo ""
	echo -e "  ${BOLD}Sonstiges${RESET}"
	echo "    docs                            → Hilfe / README (help.sh)"
	echo "    wcf-version                     → Core/Docs/GitHub-Version"
	echo "    sync-woltlab-refs [6.2]         → Git-Spiegel aktualisieren"
	echo "    lang                            → Menü-Sprache de/en"
	echo "    manager-push                    → Maintainer (falls vorhanden)"
	echo "    menu                            → Interaktives Menü"
	echo ""
	echo -e "  ${DIM}Ohne Argumente: interaktives Menü · Doku: tools/docs/README.md · Produktlinie: tools/docs/PRODUCT-LINE.de.md${RESET}"
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

run_family_interactive() {
	print_header 2>/dev/null || tools_header
	print_section "Produktlinie" "Tools" "Family"
	echo "  1 list   2 order/check   3 build   4 validate   5 init   6 init --scaffold   0 Zurück"
	read -r -p "Wahl: " fc
	case "${fc:-0}" in
		1) run_tool "$TOOLS_DIR/swpm-family.sh" list ;;
		2) run_tool "$TOOLS_DIR/swpm-family.sh" check ;;
		3)
			read -r -p "Version-Typ [patch]: " vt
			vt="${vt:-patch}"
			run_tool "$TOOLS_DIR/swpm-family.sh" build "$vt"
			;;
		4)
			read -r -p "Strict? (j/N): " st
			if [[ "${st:-n}" =~ ^[jJyY] ]]; then
				run_tool "$TOOLS_DIR/swpm-family.sh" --strict validate
			else
				run_tool "$TOOLS_DIR/swpm-family.sh" validate
			fi
			;;
		5) run_tool "$TOOLS_DIR/swpm-family.sh" init ;;
		6)
			read -r -p "base-id [com.vendor.myapp]: " bid
			read -r -p "base-dir [myapp]: " bdir
			read -r -p "addons (komma, z.B. myapp-specials): " adds
			bid="${bid:-com.vendor.myapp}"
			bdir="${bdir:-myapp}"
			args=(--scaffold --base-id "$bid" --base-dir "$bdir")
			[ -n "$adds" ] && args+=(--addons "$adds")
			run_tool "$TOOLS_DIR/swpm-family.sh" "${args[@]}" init
			;;
		*) return 0 ;;
	esac
}

# ── Menü ────────────────────────────────────────────────────────────────────

show_interactive_menu() {
	tools_header
	show_system_overview 2>/dev/null || true
	echo -e "  ${BOLD}Plugins${RESET}"
	print_plugins_summary
	echo ""
	tools_divider
	echo -e "  ${BOLD}ENTWICKLUNG${RESET}"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "1" "Build / Update-Paket" "patch · minor · major · same"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "2" "TypeScript" "typescript.sh"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "3" "Unpack" "→ temp_edit/"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "F" "Produktlinie" "family:* · Manifest"
	tools_divider
	echo -e "  ${BOLD}QUALITÄT & DOKU${RESET}"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "4" "Plugin validieren" "Store-Kriterien"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "5" "Hilfe / Doku" "help.sh"
	tools_divider
	echo -e "  ${BOLD}REPO & UMGEBUNG${RESET}"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "6" "Git Push" "gitpush.sh"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "7" "Setup" "Core, Docs, Pfade"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "8" "Repo (origin)" "URL anzeigen/setzen"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "9" "WoltLab-Version" "Core/Docs sync"
	printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "L" "Sprache DE/EN" ".env"
	if [ -f "$TOOLS_DIR/manager-push.sh" ]; then
		printf "  ${CYAN}%-3s${RESET} %-22s ${DIM}%s${RESET}\n" "M" "Manager Push" "(Maintainer)"
	fi
	tools_divider
	printf "  ${CYAN}%-3s${RESET} Beenden\n" "0"
	echo ""
	printf "  ${BOLD}${ARROW:-→}${RESET} "
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
			f | F) run_family_interactive ;;
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
	lint:python | ruff)
		shift
		run_tool "$TOOLS_DIR/lint-manager-python.sh" "$@"
		exit $?
		;;
	phpstan)
		shift
		_plugin="${1:-.}"
		[ $# -gt 0 ] && shift
		run_tool "$TOOLS_DIR/run-phpstan.sh" "$_plugin" "$@"
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
	family | family:list)
		shift
		run_tool "$TOOLS_DIR/swpm-family.sh" list "$@"
		exit $?
		;;
	family:order)
		shift
		run_tool "$TOOLS_DIR/swpm-family.sh" order "$@"
		exit $?
		;;
	family:check)
		shift
		run_tool "$TOOLS_DIR/swpm-family.sh" check "$@"
		exit $?
		;;
	family:build)
		shift
		run_tool "$TOOLS_DIR/swpm-family.sh" build "$@"
		exit $?
		;;
	family:validate)
		shift
		run_tool "$TOOLS_DIR/swpm-family.sh" validate "$@"
		exit $?
		;;
	family:init)
		shift
		run_tool "$TOOLS_DIR/swpm-family.sh" init "$@"
		exit $?
		;;
	family:add-addon)
		shift
		run_tool "$TOOLS_DIR/swpm-family.sh" add-addon "$@"
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
