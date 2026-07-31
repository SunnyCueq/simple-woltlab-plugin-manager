#!/usr/bin/env bash
#
# WoltLab Plugin Manager – Developer Tools
#
#   ./tools.sh                  → interaktives Menü
#   ./tools.sh help             → alle Befehle
#   ./tools.sh build            → Paket bauen (Patch-Version)
#   ./tools.sh build:same       → bauen ohne Versionserhöhung (Entwicklung)
#   ./tools.sh build:minor      → Minor-Version
#   ./tools.sh typescript       → TS → JS
#
# Menü-Ausgabe geht aufs Terminal, Auswahl-IDs auf stdout — dadurch lassen sich
# Auswahlfunktionen in $(…) verwenden, ohne dass das Menü im Ergebnis landet.
#
# Wichtige Befehle:
#   build / build:patch / build:minor / build:major
#   typescript | ts · unpack · validate · setup
#   push | gitpush · release · docs
#

set -euo pipefail

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$TOOLS_DIR")"
readonly TOOLS_DIR MAIN_DIR
readonly MENU_STATE_DIR="${XDG_STATE_HOME:-$HOME/.local/state}/swpm"
readonly MENU_LAST_FILE="$MENU_STATE_DIR/last-action"

# shellcheck source=common.sh
source "$TOOLS_DIR/common.sh"

check_swpm_requirements || exit 1

# ── Ein-/Ausgabe ─────────────────────────────────────────────────────────────
# Sichtbares geht auf MENU_OUT (Terminal, sonst stderr), Rückgabewerte auf stdout.

# Nur ein tatsächlich offenbares /dev/tty zählt — die Rechte allein genügen
# nicht, ohne Controlling Terminal schlägt das Öffnen fehl.
if { true >/dev/tty; } 2>/dev/null; then
	readonly MENU_OUT="/dev/tty"
	readonly MENU_IN="/dev/tty"
else
	readonly MENU_OUT="/dev/stderr"
	readonly MENU_IN=""
fi

have_fzf() { command -v fzf >/dev/null 2>&1; }
is_tty() { [ -t 1 ] || [ "$MENU_OUT" = "/dev/tty" ]; }

menu_ui() {
	# shellcheck disable=SC2059
	printf "$@" >"$MENU_OUT"
}

menu_ui_nl() {
	printf '\n' >"$MENU_OUT"
}

menu_read() {
	local prompt="$1" var=""
	printf '%b' "$prompt" >"$MENU_OUT"
	if [ -n "$MENU_IN" ]; then
		read -r var <"$MENU_IN" || true
	else
		read -r var || true
	fi
	printf '%s' "$var"
}

# ── Plugins ──────────────────────────────────────────────────────────────────

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

list_plugin_roots() {
	local plugins=() p root
	mapfile -t plugins < <(find_plugin_directories "$MAIN_DIR" 2>/dev/null || true)
	for p in "${plugins[@]}"; do
		[ -n "$p" ] || continue
		root="$p"
		[ "$(basename "$p")" = "temp_edit" ] && root="$(dirname "$p")"
		printf '%s\n' "$root"
	done
}

plugin_count() {
	local n=0 root
	while IFS= read -r root; do
		[ -n "$root" ] && n=$((n + 1))
	done < <(list_plugin_roots)
	echo "$n"
}

# Kompakte Zeile im Kopf: „basis-plugin v1.2.0 · myapp v0.3.1 · …“
plugins_inline() {
	local root xml_dir name ver i=0 out=""
	while IFS= read -r root; do
		[ -n "$root" ] || continue
		xml_dir=$(plugin_xml_dir "$root" 2>/dev/null) || continue
		[ "$i" -ge 3 ] && {
			out+=" · …"
			break
		}
		name=$(get_plugin_name "$xml_dir" 2>/dev/null || basename "$root")
		ver=$(get_plugin_version "$xml_dir" 2>/dev/null || echo "?")
		[ -n "$out" ] && out+=" · "
		out+="${name} v${ver}"
		i=$((i + 1))
	done < <(list_plugin_roots)
	printf '%s' "$out"
}

show_plugins() {
	menu_ui '  %bPlugins%b\n\n' "$BOLD" "$RESET"
	local root xml_dir ver name rel found=0
	while IFS= read -r root; do
		[ -n "$root" ] || continue
		xml_dir=$(plugin_xml_dir "$root" 2>/dev/null) || continue
		found=1
		ver=$(get_plugin_version "$xml_dir" 2>/dev/null || echo "?")
		name=$(get_plugin_name "$xml_dir" 2>/dev/null || basename "$root")
		rel="${root#"$MAIN_DIR"/}"
		menu_ui '  %b✓%b  %b%-28s%b  %bv%s%b  %b%s%b\n' \
			"$GREEN" "$RESET" "$BOLD" "$name" "$RESET" "$YELLOW" "$ver" "$RESET" "$DIM" "$rel" "$RESET"
	done < <(list_plugin_roots)
	if [ "$found" -eq 0 ]; then
		menu_ui '  %bKein Plugin mit package.xml gefunden.%b\n' "$YELLOW" "$RESET"
		menu_ui '  %b→ Ordner mit package.xml oder temp_edit/package.xml anlegen%b\n' "$DIM" "$RESET"
		menu_ui '  %b→ oder Menü „Entpacken“ / ./tools.sh unpack%b\n' "$DIM" "$RESET"
	fi
}

# ── Kopfbereich ──────────────────────────────────────────────────────────────

refs_core_version() {
	local f="$MAIN_DIR/woltlab-core/.swpm-core-version" v=""
	[ -f "$f" ] || return 1
	v="$(tr -d '[:space:]' <"$f")"
	[[ "$v" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]] || return 1
	printf '%s' "$v"
}

status_line() {
	local plat gitv nodev refs n
	plat=$(platform_label 2>/dev/null || echo "?")
	gitv=$(get_git_version 2>/dev/null || echo "?")
	[ "$gitv" = "not installed" ] && gitv="fehlt"
	if command -v node &>/dev/null; then
		nodev=$(node -v 2>/dev/null || echo "fehlt")
	else
		nodev="fehlt"
	fi
	refs="$(refs_core_version 2>/dev/null || true)"
	[ -z "$refs" ] && refs="—"
	n=$(plugin_count)

	menu_ui '  %b%s%b · Git %s · Node %s · Refs-Core %b%s%b · %b%d%b Plugin(s)\n' \
		"$DIM" "$plat" "$RESET" "$gitv" "$nodev" "$CYAN" "$refs" "$RESET" "$BOLD" "$n" "$RESET"
}

menu_header() {
	local inline
	if [ "$MENU_OUT" = "/dev/tty" ]; then
		clear 2>/dev/null || true
	fi
	menu_ui_nl
	menu_ui '  %bSWPM%b  %bSimple WoltLab Plugin Manager%b\n' "$BOLD$CYAN" "$RESET" "$DIM" "$RESET"
	menu_ui '  %bNummer tippen + Enter · q = Ende · CLI: ./tools.sh help%b\n' "$DIM" "$RESET"
	menu_ui_nl
	status_line
	inline="$(plugins_inline)"
	if [ -n "$inline" ]; then
		menu_ui '  %b%s%b\n' "$DIM" "$inline" "$RESET"
	fi
	menu_ui '  %b────────────────────────────────────────%b\n' "$DIM" "$RESET"
	menu_ui_nl
}

remember_action() {
	mkdir -p "$MENU_STATE_DIR" 2>/dev/null || true
	printf '%s\n' "$1" >"$MENU_LAST_FILE" 2>/dev/null || true
}

# ── Katalog: id|Gruppe|Label|Hinweis ─────────────────────────────────────────

menu_catalog() {
	cat <<'EOF'
build|Bauen|Paket bauen|Version patch/minor/major/same
ts|Bauen|TypeScript|einmal oder watch
unpack|Bauen|Entpacken|Paket → temp_edit/
family|Bauen|Produktlinie|list · build · check
validate|Prüfen|Plugin prüfen|lokale Qualitätschecks
docs|Prüfen|Hilfe|README & Handbuch
push|Repo|Git Push|Commit + Push (Plugins)
setup|Repo|Setup|Core, Docs, Pfade
repo|Repo|Git-Remote|origin anzeigen/setzen
wcfver|Repo|WoltLab-Refs|Core online prüfen / syncen
EOF
	if [ -f "$TOOLS_DIR/release-manager.sh" ]; then
		echo 'release|Repo|SWPM Release|Tag + GitHub-Release'
	fi
	cat <<'EOF'
lang|Mehr|Sprache|DE / EN
plugins|Mehr|Plugins|Liste mit Version
status|Mehr|System|ausführliche Übersicht
clihelp|Mehr|CLI-Hilfe|alle Terminal-Befehle
quit|—|Beenden|
EOF
}

lookup_label() {
	local want="$1" id group label hint
	while IFS='|' read -r id group label hint; do
		[ "$id" = "$want" ] || continue
		printf '%s' "$label"
		return 0
	done < <(menu_catalog)
	printf '%s' "$want"
}

# ── Auswahl ──────────────────────────────────────────────────────────────────

pick_from_menu() {
	local -a ids=()
	local i=0 id group label hint last_group="" last="" mark
	[ -f "$MENU_LAST_FILE" ] && last=$(head -n1 "$MENU_LAST_FILE" 2>/dev/null || true)

	while IFS='|' read -r id group label hint; do
		[ -n "$id" ] || continue
		i=$((i + 1))
		ids+=("$id")
		mark=" "
		[ "$id" = "$last" ] && mark="•"

		if [ "$id" = "quit" ]; then
			menu_ui_nl
			menu_ui '  %s%b%2d%b  %s\n' "$mark" "$CYAN" "$i" "$RESET" "$label"
			continue
		fi
		if [ "$group" != "$last_group" ] && [ "$group" != "—" ]; then
			[ -n "$last_group" ] && menu_ui_nl
			menu_ui '  %b%s%b\n' "$DIM" "$group" "$RESET"
			last_group="$group"
		fi
		if [ -n "$hint" ]; then
			menu_ui '  %s%b%2d%b  %-18s  %b%s%b\n' "$mark" "$CYAN" "$i" "$RESET" "$label" "$DIM" "$hint" "$RESET"
		else
			menu_ui '  %s%b%2d%b  %s\n' "$mark" "$CYAN" "$i" "$RESET" "$label"
		fi
	done < <(menu_catalog)
	menu_ui_nl

	local choice c_lc
	choice="$(menu_read "  ${BOLD}Nummer${RESET} ${YELLOW}›${RESET} ")"
	choice="${choice#"${choice%%[![:space:]]*}"}"
	choice="${choice%"${choice##*[![:space:]]}"}"

	case "${choice,,}" in
	"" | q | quit | exit | 0)
		printf 'quit\n'
		return 0
		;;
	esac

	if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#ids[@]}" ]; then
		printf '%s\n' "${ids[$((choice - 1))]}"
		return 0
	fi

	# Kurzname (build, ts, …) — keine Treffer auf Gruppenüberschriften
	c_lc="${choice,,}"
	for id in "${ids[@]}"; do
		if [ "$id" = "$c_lc" ]; then
			printf '%s\n' "$id"
			return 0
		fi
	done

	menu_ui '  %bUngültig.%b Bitte Nummer 1–%d oder q.\n' "$YELLOW" "$RESET" "${#ids[@]}"
	return 1
}

pick_from_fzf() {
	local line id
	line=$(
		menu_catalog | awk -F'|' -v last="$(head -n1 "$MENU_LAST_FILE" 2>/dev/null || true)" '
			$1=="quit" { next }
			{
				mark = ($1==last) ? "•" : " "
				printf "%s\t%s %s  %s\n", $1, mark, $3, $4
			}
			END { printf "quit\t  Beenden\n" }
		' | fzf --height=70% --layout=reverse --border=rounded \
			--prompt="  Befehl › " --header="↑↓ · tippen filtert · Enter · Esc=Ende" \
			--delimiter=$'\t' --with-nth=2.. --accept-nth=1 \
			</dev/tty \
			|| true
	)
	id="${line%%$'\t'*}"
	[ -z "$id" ] && id="quit"
	printf '%s\n' "$id"
}

pick_action() {
	if [ "${SWPM_PICKER:-}" = "fzf" ] && have_fzf && [ "$MENU_OUT" = "/dev/tty" ]; then
		pick_from_fzf
		return 0
	fi
	pick_from_menu
}

choose_one() {
	local prompt="$1"
	shift
	local -a opts=("$@")
	local choice i=1 o

	[ "${#opts[@]}" -eq 0 ] && return 1
	if [ "${#opts[@]}" -eq 1 ]; then
		printf '%s\n' "${opts[0]}"
		return 0
	fi

	menu_ui '  %b%s%b\n' "$BOLD" "$prompt" "$RESET"
	for o in "${opts[@]}"; do
		menu_ui '  %b%d%b) %s\n' "$CYAN" "$i" "$RESET" "$o"
		i=$((i + 1))
	done
	choice="$(menu_read "  ${YELLOW}›${RESET} ")"
	if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#opts[@]}" ]; then
		printf '%s\n' "${opts[$((choice - 1))]}"
		return 0
	fi
	for o in "${opts[@]}"; do
		if [ "${o,,}" = "${choice,,}" ] || [[ "${o,,}" == "${choice,,}"* ]]; then
			printf '%s\n' "$o"
			return 0
		fi
	done
	return 1
}

confirm() {
	local msg="${1:-Fortfahren?}" a
	a="$(menu_read "  ${YELLOW}${msg}${RESET} ${DIM}[j/N]${RESET} ")"
	[[ "${a:-n}" =~ ^[jJyY] ]]
}

ask_input() {
	local prompt="$1" def="${2:-}" val
	if [ -n "$def" ]; then
		val="$(menu_read "  ${prompt} ${DIM}[${def}]${RESET}: ")"
		printf '%s\n' "${val:-$def}"
	else
		val="$(menu_read "  ${prompt}: ")"
		printf '%s\n' "$val"
	fi
}

menu_pause() {
	menu_ui_nl
	if [ "$MENU_OUT" = "/dev/tty" ]; then
		menu_read "  ${DIM}[Enter] zurück …${RESET} " >/dev/null
	fi
}

# ── Runner ───────────────────────────────────────────────────────────────────

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

# ── Aktionen ─────────────────────────────────────────────────────────────────

action_build() {
	local roots=() labels=() r xml name ver pick target version_type
	mapfile -t roots < <(list_plugin_roots)
	labels=("auto" "all")
	for r in "${roots[@]}"; do
		xml=$(plugin_xml_dir "$r" 2>/dev/null) || continue
		name=$(get_plugin_name "$xml" 2>/dev/null || basename "$r")
		ver=$(get_plugin_version "$xml" 2>/dev/null || echo "?")
		labels+=("$(basename "$r") (${name} v${ver})")
	done

	pick=$(choose_one "Welches Plugin?" "${labels[@]}") || return 0
	case "$pick" in
	auto) target="auto" ;;
	all) target="all" ;;
	*)
		target="${pick%% (*}"
		target="${target// /}"
		;;
	esac

	version_type=$(choose_one "Version?" "patch" "minor" "major" "same") || return 0
	menu_ui_nl
	menu_ui '  %b→ build %s %s%b\n' "$DIM" "$target" "$version_type" "$RESET"
	confirm "Build starten?" || return 0

	if [ "$target" = "auto" ]; then
		run_tool "$TOOLS_DIR/build.sh" "$version_type"
	elif [ "$target" = "all" ]; then
		local slug
		for r in "${roots[@]}"; do
			slug=$(plugin_slug_for_build "$r")
			print_info "Build: ${slug} (${version_type})"
			run_tool "$TOOLS_DIR/build.sh" "$slug" "$version_type" \
				|| print_warning "Build fehlgeschlagen: ${slug}"
		done
	else
		run_tool "$TOOLS_DIR/build.sh" "$target" "$version_type"
	fi
}

action_ts() {
	local mode
	mode=$(choose_one "TypeScript?" "kompilieren" "watch") || return 0
	if [ "$mode" = "watch" ]; then
		run_tool "$TOOLS_DIR/typescript.sh" watch
	else
		run_tool "$TOOLS_DIR/typescript.sh"
	fi
}

action_unpack() {
	local pl pkg
	pl=$(ask_input "Plugin" "auto")
	pkg=$(ask_input "Paket .tar.gz (leer = neuestes)" "")
	confirm "Entpacken starten?" || return 0
	if [ -n "$pkg" ]; then
		run_tool "$TOOLS_DIR/unpack.sh" "$pl" "$pkg"
	elif [ -n "$pl" ] && [ "$pl" != "auto" ]; then
		run_tool "$TOOLS_DIR/unpack.sh" "$pl"
	else
		run_tool "$TOOLS_DIR/unpack.sh"
	fi
}

action_family() {
	local sub
	sub=$(choose_one "Produktlinie?" \
		"list" "check" "build" "validate" "init" "init --scaffold") || return 0
	case "$sub" in
	list) run_tool "$TOOLS_DIR/swpm-family.sh" list ;;
	check) run_tool "$TOOLS_DIR/swpm-family.sh" check ;;
	build)
		local vt
		vt=$(choose_one "Version?" "patch" "minor" "major" "same") || return 0
		run_tool "$TOOLS_DIR/swpm-family.sh" build "$vt"
		;;
	validate)
		if confirm "Strict-Modus?"; then
			run_tool "$TOOLS_DIR/swpm-family.sh" --strict validate
		else
			run_tool "$TOOLS_DIR/swpm-family.sh" validate
		fi
		;;
	init) run_tool "$TOOLS_DIR/swpm-family.sh" init ;;
	"init --scaffold")
		local bid bdir adds
		bid=$(ask_input "base-id" "com.vendor.myapp")
		bdir=$(ask_input "base-dir" "myapp")
		adds=$(ask_input "addons (komma)" "")
		local args=(--scaffold --base-id "$bid" --base-dir "$bdir")
		[ -n "$adds" ] && args+=(--addons "$adds")
		run_tool "$TOOLS_DIR/swpm-family.sh" "${args[@]}" init
		;;
	esac
}

action_validate() {
	local target
	target=$(ask_input "Plugin-Verzeichnis (leer = auto)" "")
	confirm "Validierung starten?" || return 0
	if [ -n "$target" ]; then
		if [[ "$target" =~ ^/ ]]; then
			run_tool "$TOOLS_DIR/validate-plugin.sh" "$target"
		else
			run_tool "$TOOLS_DIR/validate-plugin.sh" "$MAIN_DIR/$target"
		fi
	else
		run_tool "$TOOLS_DIR/validate-plugin.sh"
	fi
}

action_push() {
	local target msg
	target=$(ask_input "Ziel" "auto")
	msg=$(ask_input "Commit-Nachricht (leer = auto)" "")
	confirm "Push starten?" || return 0
	if [ -n "$msg" ]; then
		run_tool "$TOOLS_DIR/gitpush.sh" "$target" "$msg"
	else
		run_tool "$TOOLS_DIR/gitpush.sh" "$target"
	fi
}

action_repo() {
	menu_ui '  Aktuelles Remote: %b%s%b\n\n' "$YELLOW" "$(get_git_repo_display)" "$RESET"
	confirm "origin ändern?" || return 0
	local url
	url=$(ask_input "Neue URL" "")
	url=$(echo "$url" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
	[ -n "$url" ] || return 0
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
}

action_lang() {
	menu_ui '  Aktuell: %b%s%b\n' "$CYAN" "${WOLTLAB_LANG:-en}" "$RESET"
	local l
	l=$(choose_one "Sprache?" "de" "en") || return 0
	env_set "WOLTLAB_LANG" "$l"
	export WOLTLAB_LANG="$l"
	print_success "Sprache: ${l^^}"
}

action_release() {
	[ -f "$TOOLS_DIR/release-manager.sh" ] || {
		print_error "release-manager.sh fehlt"
		return 1
	}
	local ver
	ver=$(ask_input "Version (z. B. 1.2.7)" "")
	ver=$(echo "$ver" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
	[ -n "$ver" ] || {
		print_error "Version erforderlich"
		return 1
	}
	confirm "Release ${ver} starten?" || return 0
	run_tool "$TOOLS_DIR/release-manager.sh" "$ver"
}

dispatch_action() {
	local id="$1"
	case "$id" in
	build) action_build ;;
	ts) action_ts ;;
	unpack) action_unpack ;;
	family) action_family ;;
	validate) action_validate ;;
	docs) run_tool "$TOOLS_DIR/help.sh" ;;
	push) action_push ;;
	setup) run_tool "$TOOLS_DIR/setup-minimal.sh" ;;
	repo) action_repo ;;
	wcfver) run_tool "$TOOLS_DIR/update-woltlab-version.sh" ;;
	lang) action_lang ;;
	release) action_release ;;
	plugins) show_plugins ;;
	status) show_system_overview 2>/dev/null || true ;;
	clihelp) cmd_help ;;
	quit) return 2 ;;
	*)
		print_error "Unbekannte Auswahl: ${id}"
		return 1
		;;
	esac
}

run_menu_loop() {
	local id label rc

	while true; do
		menu_header
		id=""
		set +e
		id="$(pick_action)"
		rc=$?
		set -e
		id="$(printf '%s' "$id" | tr -d '\r' | head -n1 | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"

		if [ "$rc" -ne 0 ] || [ -z "$id" ]; then
			menu_pause
			continue
		fi
		if [ "$id" = "quit" ]; then
			menu_ui '  %bBis bald.%b\n' "$GREEN" "$RESET"
			exit 0
		fi

		label="$(lookup_label "$id")"
		menu_ui_nl
		menu_ui '  %b›%b %b%s%b\n\n' "$CYAN" "$RESET" "$BOLD" "$label" "$RESET"
		remember_action "$id"

		set +e
		dispatch_action "$id"
		rc=$?
		set -e
		[ "$rc" -eq 2 ] && {
			menu_ui '  %bBis bald.%b\n' "$GREEN" "$RESET"
			exit 0
		}
		menu_pause
	done
}

# ── CLI: Hilfe ───────────────────────────────────────────────────────────────

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
	echo "    lint:python [--fix]             → ruff auf tools/*.py (optional)"
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
	echo "    push | gitpush [args…]          → tools/gitpush.sh (Plugin-Repos)"
	echo "    release [version]               → tools/release-manager.sh (SWPM)"
	echo "    repo                            → origin anzeigen / setzen"
	echo ""
	echo -e "  ${BOLD}Sonstiges${RESET}"
	echo "    docs                            → Hilfe / README (help.sh)"
	echo "    wcf-version [--check|--yes]     → Core online prüfen / syncen"
	echo "    sync-woltlab-refs [6.2]         → nur Git-Spiegel aktualisieren"
	echo "    lang                            → Menü-Sprache de/en"
	echo "    menu                            → Interaktives Menü"
	echo ""
	echo -e "  ${DIM}Ohne Argumente: interaktives Menü · Doku: tools/docs/TOOLS-OVERVIEW.de.md · Produktlinie: tools/docs/PRODUCT-LINE.de.md${RESET}"
	echo -e "  ${DIM}Menü mit Fuzzy-Suche: SWPM_PICKER=fzf ./tools.sh${RESET}"
	echo ""
}

# ── CLI-Dispatcher ───────────────────────────────────────────────────────────

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
		action_repo
		exit 0
		;;
	lang)
		action_lang
		exit 0
		;;
	release | manager-push | manager:release)
		shift
		run_tool "$TOOLS_DIR/release-manager.sh" "$@"
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
