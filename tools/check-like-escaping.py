#!/usr/bin/env python3
"""
Check PHP files for LIKE queries that should use WCF::getDB()->escapeLikeValue().

WoltLab 6.2.5+ standardized on escapeLikeValue() instead of addcslashes($x, '%_').

Usage:
  check-like-escaping.py PLUGIN_DIR
  check-like-escaping.py --count PLUGIN_DIR
"""
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

RE_ADDCSLASHES_LIKE = re.compile(
    r"addcslashes\s*\([^)]*['\"][_%]['\"]",
    re.IGNORECASE,
)
RE_LIKE_CONCAT = re.compile(
    r"['\"]%['\"]\s*\.\s*\$[a-zA-Z_]",
)
RE_LIKE_KEYWORD = re.compile(r"\bLIKE\b", re.IGNORECASE)
RE_ESCAPE_LIKE = re.compile(r"escapeLikeValue\s*\(")


def scan_file(path: Path) -> list[tuple[int, str, str]]:
    try:
        lines = path.read_text(encoding="utf-8").splitlines()
    except OSError as exc:
        return [(0, "read-error", str(exc))]

    issues: list[tuple[int, str, str]] = []
    file_has_escape_like = any(RE_ESCAPE_LIKE.search(line) for line in lines)

    for i, line in enumerate(lines, start=1):
        stripped = line.strip()
        if stripped.startswith("//") or stripped.startswith("*") or stripped.startswith("#"):
            continue

        if RE_ADDCSLASHES_LIKE.search(line):
            issues.append((i, "addcslashes", stripped[:120]))
            continue

        if RE_LIKE_KEYWORD.search(line) and RE_LIKE_CONCAT.search(line):
            if "escapeLikeValue" not in line:
                # Look at nearby lines for escapeLikeValue in same statement
                window = "\n".join(lines[max(0, i - 4) : min(len(lines), i + 2)])
                if "escapeLikeValue" not in window:
                    issues.append((i, "like-concat", stripped[:120]))

    return issues


def iter_php(root: Path):
    for path in sorted(root.rglob("*.php")):
        if "node_modules" in path.parts or "vendor" in path.parts:
            continue
        yield path


def main() -> int:
    parser = argparse.ArgumentParser(description="Check LIKE escaping in PHP")
    parser.add_argument("plugin_dir", type=Path)
    parser.add_argument("--count", action="store_true")
    args = parser.parse_args()

    root = args.plugin_dir.resolve()
    if not root.is_dir():
        print(f"Not a directory: {root}", file=sys.stderr)
        return 2

    total = 0
    for php in iter_php(root):
        issues = scan_file(php)
        if not issues:
            continue
        total += len(issues)
        if not args.count:
            rel = php.relative_to(root) if php.is_relative_to(root) else php
            for line_no, kind, snippet in issues:
                print(f"{rel}:{line_no}:{kind}:{snippet}")

    if args.count:
        print(total)
    return 1 if total else 0


if __name__ == "__main__":
    sys.exit(main())
