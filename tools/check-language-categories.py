#!/usr/bin/env python3
"""
Validate WoltLab language XML: each <item name="…"> must belong to its parent <category name="…">.

Mirrors LanguageEditor::validateItemName() — item name must equal category name or start with
category name + '.' (otherwise ACP package install fails with InvalidArgumentException).

Usage:
  check-language-categories.py PLUGIN_DIR
  check-language-categories.py --count PLUGIN_DIR
"""
from __future__ import annotations

import argparse
import sys
from pathlib import Path
from xml.etree import ElementTree as ET


def local_tag(tag: str) -> str:
    return tag.split("}")[-1] if "}" in tag else tag


def item_matches_category(item_name: str, category_name: str) -> bool:
    if not item_name:
        return False
    if item_name == category_name:
        return True
    prefix = category_name + "."
    return item_name.startswith(prefix)


def scan_language_file(path: Path) -> list[tuple[str, str]]:
    """Return list of (category_name, item_name) mismatches."""
    try:
        tree = ET.parse(path)
    except ET.ParseError as exc:
        return [("?", f"parse-error: {exc}")]

    root = tree.getroot()
    issues: list[tuple[str, str]] = []

    for category in root.iter():
        if local_tag(category.tag) != "category":
            continue
        category_name = category.get("name")
        if not category_name:
            continue
        for child in category:
            if local_tag(child.tag) != "item":
                continue
            item_name = child.get("name") or ""
            if not item_matches_category(item_name, category_name):
                issues.append((category_name, item_name))

    return issues


def iter_language_xml(root: Path):
    lang_dir = root / "language"
    if lang_dir.is_dir():
        yield from sorted(lang_dir.glob("*.xml"))
        return
    for path in sorted(root.rglob("language/*.xml")):
        if "node_modules" in path.parts or "vendor" in path.parts:
            continue
        yield path


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Check language item/category name alignment (WoltLab PIP)"
    )
    parser.add_argument("plugin_dir", type=Path)
    parser.add_argument("--count", action="store_true")
    args = parser.parse_args()

    root = args.plugin_dir.resolve()
    if not root.is_dir():
        print(f"Not a directory: {root}", file=sys.stderr)
        return 2

    total = 0
    for xml_path in iter_language_xml(root):
        issues = scan_language_file(xml_path)
        if not issues:
            continue
        total += len(issues)
        if not args.count:
            rel = xml_path.relative_to(root) if xml_path.is_relative_to(root) else xml_path
            for category_name, item_name in issues:
                print(f"{rel}:category-mismatch:{category_name}:{item_name}")

    if args.count:
        print(total)
    return 1 if total else 0


if __name__ == "__main__":
    sys.exit(main())
