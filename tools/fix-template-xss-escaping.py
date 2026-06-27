#!/usr/bin/env python3
"""
WoltLab template helper — flags invalid |encodeHTML and fixes <script> vars only.

WoltLab: plain {$var} is auto HTML-escaped. Use {unsafe:$var|encodeJS} in scripts.
|encodeHTML is NOT a valid modifier.

Usage:
  fix-template-xss-escaping.py PLUGIN_DIR [--dry-run]
"""
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path


def fix_content(content: str) -> str:
    content = re.sub(r"\|encodeHTML\b", "", content)

    def fix_script(m: re.Match[str]) -> str:
        block = m.group(0)
        return re.sub(
            r"(['\"])\{\$([a-zA-Z_][a-zA-Z0-9_]*)\}\1",
            r"{\1unsafe:$\2|encodeJS\1}",
            block,
        )

    content = re.sub(r"<script[\s\S]*?</script>", fix_script, content, flags=re.IGNORECASE)
    return content


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("plugin_dir", type=Path)
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    root = args.plugin_dir.resolve()
    changed = 0
    for path in sorted(root.rglob("*.tpl")):
        if "node_modules" in path.parts:
            continue
        original = path.read_text(encoding="utf-8")
        updated = fix_content(original)
        if updated == original:
            continue
        changed += 1
        rel = path.relative_to(root) if path.is_relative_to(root) else path
        print(rel)
        if not args.dry_run:
            path.write_text(updated, encoding="utf-8")

    print(f"{'Would update' if args.dry_run else 'Updated'} {changed} template(s).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
