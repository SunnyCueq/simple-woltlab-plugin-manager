#!/usr/bin/env python3
"""
WoltLab template XSS heuristics for plugin validation.

WoltLab auto-escapes plain {$var} output (StringUtil::encodeHTML). Modifiers like
|encodeHTML or |escape do NOT exist and cause compile fatals.

Flags:
- Plain {$var} in <script> without {unsafe:…|encodeJS}
- {unsafe:…} without justification (not checked here — manual review)

Ignores: {* comments *}, {lang}…{/lang}, compiled {unsafe:…} blocks
"""
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

RE_COMMENT = re.compile(r"\{[\*][\s\S]*?[\*]\}")
RE_LANG_BLOCK = re.compile(r"\{lang[^}]*\}[\s\S]*?\{/lang\}")
RE_UNSAFE = re.compile(r"\{unsafe:[^}]+\}")
RE_SCRIPT = re.compile(r"<script[\s\S]*?</script>", re.IGNORECASE)
RE_INVALID_ENCODEHTML = re.compile(r"\|encodeHTML\b")
RE_INVALID_ESCAPE = re.compile(r"\|escape\b")
RE_SCRIPT_PLAIN = re.compile(r"(['\"])\{\$([a-zA-Z_][a-zA-Z0-9_]*)\}\1")
RE_PLAIN_VAR_IN_SCRIPT = re.compile(r"\{\$([a-zA-Z_][a-zA-Z0-9_]*)\}(?!\|)")


def strip_ignored_regions(text: str) -> str:
    text = RE_COMMENT.sub("", text)
    text = RE_LANG_BLOCK.sub("", text)
    text = RE_UNSAFE.sub("", text)
    return text


def line_number(text: str, index: int) -> int:
    return text.count("\n", 0, index) + 1


def scan_file(path: Path) -> list[tuple[int, str, str]]:
    try:
        content = path.read_text(encoding="utf-8")
    except OSError as exc:
        return [(0, "read-error", str(exc))]

    issues: list[tuple[int, str, str]] = []

    for m in RE_INVALID_ENCODEHTML.finditer(content):
        issues.append((line_number(content, m.start()), "invalid-modifier", "|encodeHTML"))
    for m in RE_INVALID_ESCAPE.finditer(content):
        issues.append((line_number(content, m.start()), "invalid-modifier", "|escape"))

    for block in RE_SCRIPT.finditer(content):
        script = block.group(0)
        if "{unsafe:" in script:
            continue
        for m in RE_SCRIPT_PLAIN.finditer(script):
            pos = block.start() + m.start()
            issues.append((line_number(content, pos), "script", m.group(0)))
        for m in RE_PLAIN_VAR_IN_SCRIPT.finditer(script):
            pos = block.start() + m.start()
            issues.append((line_number(content, pos), "script-inline", m.group(0)))

    seen: set[tuple[int, str, str]] = set()
    out: list[tuple[int, str, str]] = []
    for item in sorted(issues, key=lambda x: (x[0], x[1])):
        if item not in seen:
            seen.add(item)
            out.append(item)
    return out


def iter_templates(root: Path):
    for path in sorted(root.rglob("*.tpl")):
        if "node_modules" in path.parts:
            continue
        yield path


def main() -> int:
    parser = argparse.ArgumentParser(description="Check WoltLab .tpl files for XSS/compile risks")
    parser.add_argument("plugin_dir", type=Path)
    parser.add_argument("--count", action="store_true")
    args = parser.parse_args()

    root = args.plugin_dir.resolve()
    if not root.is_dir():
        print(f"Not a directory: {root}", file=sys.stderr)
        return 2

    total = 0
    for tpl in iter_templates(root):
        for line_no, kind, snippet in scan_file(tpl):
            total += 1
            if not args.count:
                rel = tpl.relative_to(root) if tpl.is_relative_to(root) else tpl
                print(f"{rel}:{line_no}:{kind}:{snippet}")

    if args.count:
        print(total)
    return 1 if total else 0


if __name__ == "__main__":
    sys.exit(main())
