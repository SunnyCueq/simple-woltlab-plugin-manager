#!/usr/bin/env python3
"""Fail when frontend templateListeners wire CSS/JS behind options that default to off.

This is the CherryMagic-class bug: package installs files, templateListener exists,
but `{if MODULE}` with defaultvalue 0 means nothing appears in the HTML after install.
SWPM previously only checked that PIP sources/AMD exports exist — not the gate defaults.
"""
from __future__ import annotations

import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

OPTION_DEFAULT = re.compile(
    r"<option\s+name=\"([^\"]+)\">.*?<defaultvalue>([^<]*)</defaultvalue>",
    re.S,
)
# CHERRYMAGIC_ACTIVE / FOO_BAR from option foo_bar
CONST_REF = re.compile(
    r"(?:'([A-Z][A-Z0-9_]*)'\|defined\s*&&\s*\1)|"
    r"\b([A-Z][A-Z0-9_]{2,})\b"
)
CSS_HREF = re.compile(
    r"href=\"[^\"]*(?:\{[^}]*getPath\(\)[^}]*\})?([a-zA-Z0-9_./-]+\.css)",
)
# simpler: style/foo.css or acp/style/foo.css in templatecode
CSS_PATH = re.compile(r"(?:style|acp/style)/[a-zA-Z0-9_./-]+\.css")
REQUIRE_MOD = re.compile(r'require\(\s*\[\s*"([A-Za-z][A-Za-z0-9_/]+)"\s*\]')


def option_defaults(root: Path) -> dict[str, str]:
    path = root / "option.xml"
    if not path.is_file():
        return {}
    text = path.read_text(encoding="utf-8", errors="replace")
    out: dict[str, str] = {}
    for m in OPTION_DEFAULT.finditer(text):
        out[m.group(1)] = m.group(2).strip()
    return out


def const_to_option(const: str) -> str:
    return const.lower()


def listener_codes(root: Path) -> list[tuple[str, str]]:
    path = root / "templateListener.xml"
    if not path.is_file():
        return []
    tree = ET.parse(path)
    ns = {"w": "http://www.woltlab.com"}
    rows: list[tuple[str, str]] = []
    for node in tree.findall(".//w:templatelistener", ns) or tree.findall(".//templatelistener"):
        env = (node.findtext("environment") or node.findtext("{http://www.woltlab.com}environment") or "").strip()
        if env and env != "user":
            continue
        name = node.get("name") or "unknown"
        code_el = node.find("templatecode") or node.find("{http://www.woltlab.com}templatecode")
        if code_el is None or not (code_el.text or "").strip():
            continue
        rows.append((name, code_el.text or ""))
    return rows


def asset_exists(root: Path, rel: str) -> bool:
    candidates = [
        root / rel,
        root / "files" / rel,
        root / "files_wcf" / rel,
    ]
    return any(p.is_file() for p in candidates)


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".").resolve()
    tl = root / "templateListener.xml"
    if not tl.is_file():
        return 0

    defaults = option_defaults(root)
    issues: list[str] = []

    for name, code in listener_codes(root):
        loads_assets = bool(CSS_PATH.search(code) or REQUIRE_MOD.search(code))
        if not loads_assets:
            continue

        # Collect uppercase constants used in {if ...}
        if_blocks = re.findall(r"\{if\s+([^}]+)\}", code)
        gating_consts: set[str] = set()
        for expr in if_blocks:
            for m in CONST_REF.finditer(expr):
                c = m.group(1) or m.group(2)
                if c in {"LAST_UPDATE_TIME", "TIME_NOW", "WCF_VERSION"}:
                    continue
                if c.endswith("_VERSION"):
                    continue
                gating_consts.add(c)

        if gating_consts:
            mapped = [(c, const_to_option(c)) for c in sorted(gating_consts) if const_to_option(c) in defaults]
            if mapped:
                truthy = [c for c, opt in mapped if defaults[opt] not in ("0", "", "false", "False")]
                if not truthy:
                    opts = ", ".join(f"{opt}={defaults[opt]}" for _, opt in mapped)
                    issues.append(
                        f"templateListener '{name}': frontend CSS/JS gated by option(s) "
                        f"defaulting to off ({opts}) — install will look broken"
                    )

        for css in CSS_PATH.findall(code):
            if not asset_exists(root, css):
                issues.append(f"templateListener '{name}': missing stylesheet source {css}")

        for mod in REQUIRE_MOD.findall(code):
            js = f"js/{mod}.js"
            if not asset_exists(root, js):
                issues.append(f"templateListener '{name}': missing AMD module file {js}")

    for line in issues:
        print(line)
    return 1 if issues else 0


if __name__ == "__main__":
    sys.exit(main())
