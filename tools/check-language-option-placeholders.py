#!/usr/bin/env python3
"""
Fail if option help language items contain template placeholders that crash ACP.

Option descriptions are rendered via Language::tplGet / {lang} in optionFieldList.tpl.
Literal mail/engine placeholders like "{$url}" or "{slug}" are evaluated as template
code — not shown as documentation.

Legitimate dynamic vars (e.g. {$username} with PHP assign) and WoltLab tags ({link})
are allowed.

Usage:
  check-language-option-placeholders.py PLUGIN_DIR
  check-language-option-placeholders.py --count PLUGIN_DIR
"""
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path
from xml.etree import ElementTree as ET

# Variables often documented as mail/engine placeholders — never assigned on OptionForm.
DOC_DOLLAR_VARS = frozenset(
    {
        "url",
        "acpUrl",
        "extendUrl",
        "expiresAt",
        "expiresAtFormatted",
        "adminUser",
        "adminPassword",
        "canExtend",
        "slug",
        "base",
        "login",
        "ttlDays",
        "message",
        "action",
        "suggestedApiUrl",
    }
)

RE_DOLLAR_VAR = re.compile(r"\{\$([a-zA-Z_][a-zA-Z0-9_]*)")
RE_BARE_PLACEHOLDERS = re.compile(
    r"\{(?:slug|base|login)\}(?!\w)"
)


def local_tag(tag: str) -> str:
    return tag.split("}")[-1] if "}" in tag else tag


def is_option_help_item(item_name: str) -> bool:
    if not item_name.endswith(".description") and not item_name.endswith(
        ".description.informal"
    ):
        return False
    return item_name.startswith("wcf.acp.option.")


def scan_language_file(path: Path) -> list[str]:
    try:
        tree = ET.parse(path)
    except ET.ParseError as exc:
        return [f"{path.name}: parse-error: {exc}"]

    issues: list[str] = []
    root = tree.getroot()
    for item in root.iter():
        if local_tag(item.tag) != "item":
            continue
        name = item.get("name") or ""
        if not is_option_help_item(name):
            continue
        text = "".join(item.itertext())
        for match in RE_DOLLAR_VAR.finditer(text):
            var = match.group(1)
            if var in DOC_DOLLAR_VARS:
                issues.append(
                    f"{path.name}: {name}: {{${var}}} is evaluated by tplGet — "
                    "use plain words or &#123;…&#125;"
                )
        if RE_BARE_PLACEHOLDERS.search(text):
            issues.append(
                f"{path.name}: {name}: bare {{slug|base|login}} — "
                "use entities or plain words"
            )
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
        description="Check option language help for unsafe template placeholders"
    )
    parser.add_argument("plugin_dir", type=Path)
    parser.add_argument("--count", action="store_true")
    args = parser.parse_args()

    root = args.plugin_dir.resolve()
    if not root.is_dir():
        print(f"Not a directory: {root}", file=sys.stderr)
        return 2

    all_issues: list[str] = []
    for path in iter_language_xml(root):
        all_issues.extend(scan_language_file(path))

    if args.count:
        print(len(all_issues))
        return 1 if all_issues else 0

    for issue in all_issues:
        print(issue, file=sys.stderr)
    if all_issues:
        print(
            f"{len(all_issues)} option-help placeholder issue(s)",
            file=sys.stderr,
        )
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
