#!/usr/bin/env python3
"""
WoltLab AMD modules: ACP templates call Module.setup() — compiled JS must export setup.

Detects:
- js/**/*.js with exports.default = { setup } but no exports.setup = setup
- acptemplates/**/*.tpl requiring AMD modules via *.setup() without matching export in JS

Usage:
  check-js-amd-exports.py PLUGIN_DIR
  check-js-amd-exports.py --prefix MyApp PLUGIN_DIR   # optional: only modules under MyApp/
"""
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

DEFAULT_EXPORT_SETUP = re.compile(r"exports\.default\s*=\s*\{\s*setup\s*\}")
NAMED_EXPORT_SETUP = re.compile(r"exports\.setup\s*=\s*setup\b")
# Any AMD id: FirstSegment/rest… (WoltLab app JS namespace)
REQUIRE_MODULE = re.compile(r'require\(\["([A-Za-z][A-Za-z0-9_]*/[^"]+)"\]')


def woltlab_module_to_js_path(module_id: str) -> str:
    """MyApp/Acp/Link/AddFormInit -> js/MyApp/Acp/Link/AddFormInit.js"""
    return f"js/{module_id}.js"


def module_require_style(root: Path, module_id: str) -> str:
    """Return 'named', 'default', or 'none' depending on acptemplates/templateListener usage."""
    uses_named = False
    uses_default = False
    sources: list[str] = []
    acp = root / "acptemplates"
    if acp.is_dir():
        for path in acp.rglob("*.tpl"):
            sources.append(path.read_text(encoding="utf-8", errors="replace"))
    tl = root / "templateListener.xml"
    if tl.is_file():
        sources.append(tl.read_text(encoding="utf-8", errors="replace"))

    escaped = re.escape(module_id)
    for text in sources:
        if module_id not in text:
            continue
        if re.search(rf'require\(\["{escaped}"\][\s\S]*?\.default\.setup\(\)', text):
            uses_default = True
        if re.search(rf'require\(\["{escaped}"\][\s\S]*?\)\s*=>\s*\{{[\s\S]*?\bsetup\(\)', text):
            uses_named = True
        elif re.search(rf'require\(\["{escaped}"\],\s*function[\s\S]*?\bsetup\(\)', text):
            uses_named = True
        for block in re.findall(rf'require\(\["{escaped}"\][\s\S]*?\);', text):
            if ".default.setup()" in block:
                continue
            if re.search(r"\b\w+\.setup\(\)", block):
                uses_named = True
    if uses_named:
        return "named"
    if uses_default:
        return "default"
    return "none"


def scan_js_file(path: Path, require_style: str = "none") -> str | None:
    text = path.read_text(encoding="utf-8", errors="replace")
    if "setup" not in text:
        return None
    has_default = bool(DEFAULT_EXPORT_SETUP.search(text))
    has_named = bool(NAMED_EXPORT_SETUP.search(text))
    if require_style == "named" and not has_named:
        if has_default:
            return "ACP requires Module.setup() but JS only has exports.default={setup}"
        return "missing exports.setup=setup (ACP require uses Module.setup)"
    if require_style == "default" and not has_default and not has_named:
        return "ACP requires Module.default.setup() but JS has no default or named setup export"
    if require_style == "none" and has_default and not has_named:
        return "only exports.default={setup}, missing exports.setup=setup (prefer export { setup })"
    return None


def collect_modules(root: Path, prefix: str | None) -> set[str]:
    modules: set[str] = set()
    for base in [root / "acptemplates", root / "templateListener.xml"]:
        if base.is_file():
            texts = [base.read_text(encoding="utf-8", errors="replace")]
        elif base.is_dir():
            texts = [p.read_text(encoding="utf-8", errors="replace") for p in base.rglob("*.tpl")]
        else:
            continue
        for text in texts:
            for m in REQUIRE_MODULE.findall(text):
                if prefix and not m.startswith(prefix + "/"):
                    continue
                # Only modules that map to an existing js/ tree segment (or any if js missing)
                modules.add(m)
    return modules


def scan_templates(root: Path, prefix: str | None = None) -> list[str]:
    issues: list[str] = []
    for module_id in sorted(collect_modules(root, prefix)):
        js_rel = woltlab_module_to_js_path(module_id)
        js_path = root / js_rel
        style = module_require_style(root, module_id)
        if style == "none":
            continue
        if not js_path.is_file():
            issues.append(f"missing {js_rel} (required as {module_id})")
            continue
        js_issue = scan_js_file(js_path, style)
        if js_issue:
            issues.append(f"{js_rel}: {js_issue}")
    return issues


def main() -> int:
    parser = argparse.ArgumentParser(description="Check AMD named exports for ACP require().setup()")
    parser.add_argument(
        "--prefix",
        default=None,
        help="Optional AMD namespace prefix (e.g. MyApp); default: all require([\"Prefix/…\"]) modules",
    )
    parser.add_argument("plugin_dir", type=Path)
    args = parser.parse_args()

    root = args.plugin_dir.resolve()
    if not root.is_dir():
        print(f"Not a directory: {root}", file=sys.stderr)
        return 2

    issues = scan_templates(root, args.prefix)

    if issues:
        for issue in issues:
            print(issue)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
