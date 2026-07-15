#!/usr/bin/env python3
"""Validate WoltLab style/style.xml source tree (style PIP packages).

Exit 0 = clean, 1 = findings. Prints one finding per line:
  path:line: message

Does not compile SCSS — WoltLab builds CSS from variables.xml at install/runtime.
"""

from __future__ import annotations

import sys
import xml.etree.ElementTree as ET
from pathlib import Path


def local(tag: str) -> str:
    return tag.split("}")[-1] if "}" in tag else tag


def text(elem: ET.Element | None) -> str:
    if elem is None or elem.text is None:
        return ""
    return elem.text.strip()


def find_child(parent: ET.Element, name: str) -> ET.Element | None:
    for child in parent:
        if local(child.tag) == name:
            return child
    return None


def find_desc(root: ET.Element, name: str) -> ET.Element | None:
    for elem in root.iter():
        if local(elem.tag) == name:
            return elem
    return None


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: check-style-package.py <plugin-or-temp_edit-dir>", file=sys.stderr)
        return 2

    root = Path(sys.argv[1]).resolve()
    style_xml = root / "style" / "style.xml"
    if not style_xml.is_file():
        # Not a style package source — nothing to check
        return 0

    findings: list[str] = []
    try:
        tree = ET.parse(style_xml)
        xml_root = tree.getroot()
    except ET.ParseError as exc:
        print(f"{style_xml.relative_to(root)}:1: style.xml parse error: {exc}")
        return 1

    general = find_desc(xml_root, "general")
    if general is None:
        findings.append(f"{style_xml.relative_to(root)}:1: missing <general>")
    else:
        for req in ("stylename", "packageName"):
            if not text(find_child(general, req)):
                findings.append(
                    f"{style_xml.relative_to(root)}:1: missing or empty <{req}>"
                )
        for opt in ("image", "image2x", "coverPhoto"):
            name = text(find_child(general, opt))
            if not name:
                continue
            path = style_xml.parent / name
            if not path.is_file():
                findings.append(
                    f"{style_xml.relative_to(root)}:1: <{opt}> file missing: style/{name}"
                )

    files = find_desc(xml_root, "files")
    if files is not None:
        for child in files:
            tag = local(child.tag)
            name = text(child)
            if not name:
                continue
            if tag in ("variables", "variablesDarkMode"):
                path = style_xml.parent / name
                if not path.is_file():
                    findings.append(
                        f"{style_xml.relative_to(root)}:1: <{tag}> file missing: style/{name}"
                    )
            elif tag in ("templates", "images"):
                folder = name
                for suf in (".tar.gz", ".tgz", ".tar"):
                    if folder.endswith(suf):
                        folder = folder[: -len(suf)]
                        break
                candidates = [
                    style_xml.parent / folder,
                    root / folder,
                ]
                if not any(c.is_dir() for c in candidates):
                    findings.append(
                        f"{style_xml.relative_to(root)}:1: <{tag}> folder missing: style/{folder}/"
                    )

    for line in findings:
        print(line)

    # Informational only: SCSS in style/ is not compiled by SWPM
    for scss in sorted((root / "style").rglob("*.scss")):
        print(
            f"WARN:{scss.relative_to(root)}:1: .scss present — WoltLab styles use "
            "variables.xml; SWPM does not run scssphp (suite compiles at runtime)"
        )

    return 1 if findings else 0


if __name__ == "__main__":
    sys.exit(main())
