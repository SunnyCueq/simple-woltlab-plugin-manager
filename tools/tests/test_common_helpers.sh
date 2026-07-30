#!/usr/bin/env bash
# Minimal regression tests for tools/common.sh helpers (no bats required).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TOOLS_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
# shellcheck source=../common.sh
source "$TOOLS_DIR/common.sh"

fail=0
assert_eq() {
    local name="$1" got="$2" want="$3"
    if [[ "$got" == "$want" ]]; then
        echo "PASS $name"
    else
        echo "FAIL $name: got='$got' want='$want'"
        fail=1
    fi
}
assert_rc() {
    local name="$1" want_rc="$2"
    shift 2
    set +e
    "$@"
    local rc=$?
    set -e
    if [[ "$rc" -eq "$want_rc" ]]; then
        echo "PASS $name (rc=$rc)"
    else
        echo "FAIL $name: rc=$rc want=$want_rc"
        fail=1
    fi
}

assert_eq "release_label plain" "$(swpm_plugin_release_label /tmp/my-plugin)" "my-plugin"
assert_eq "release_label temp_edit" "$(swpm_plugin_release_label /tmp/my-plugin/temp_edit)" "my-plugin"
assert_eq "release_dir" "$(swpm_release_dir /tmp/main /tmp/my-plugin)" "/tmp/main/releases/my-plugin"

# Host allowlist: reject non-loopback / injection payloads without opening sockets
assert_rc "port reject evil host" 1 check_port_reachable '127.0.0.1;id' 80
assert_rc "port reject remote host" 1 check_port_reachable 'evil.example' 80
assert_rc "port reject bad port" 1 check_port_reachable 127.0.0.1 '80;id'
assert_rc "port reject zero" 1 check_port_reachable 127.0.0.1 0

if (( fail )); then
    echo "FAILED"
    exit 1
fi
echo "OK common helpers"
exit 0
