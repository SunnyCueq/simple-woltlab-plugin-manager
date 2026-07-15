#!/usr/bin/env python3
"""Warn when package.xml lacks packagedescription (default and/or de).

Generic single-package check for SWPM builds. Exit 0 always unless --strict.
"""

from __future__ import annotations

import argparse
import sys
import xml.etree.ElementTree as ET
from pathlib import Path


def local_tag(tag: str) -> str:
    return tag.split("}")[-1] if "}" in tag else tag


def find_package_xml(root: Path) -> Path | None:
    for rel in ("package.xml", "temp_edit/package.xml"):
        p = root / rel
        if p.is_file():
            return p
    return None


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("plugin_root", type=Path)
    parser.add_argument("--strict", action="store_true")
    args = parser.parse_args()
    root = args.plugin_root.resolve()
    pkg = find_package_xml(root)
    if pkg is None:
        print(f"SKIP: no package.xml under {root}")
        return 0

    tree = ET.parse(pkg)
    descriptions: list[tuple[str | None, str]] = []
    for child in tree.getroot():
        if local_tag(child.tag) != "packageinformation":
            continue
        for sub in child:
            if local_tag(sub.tag) != "packagedescription":
                continue
            text = (sub.text or "").strip()
            if not text:
                continue
            lang = sub.attrib.get("language") or sub.attrib.get(
                "{http://www.w3.org/XML/1998/namespace}lang"
            )
            descriptions.append((lang, text))

    # wcf1_package.packageDescription is VARCHAR(255) in WSC.
    max_len = 255

    issues: list[str] = []
    if not descriptions:
        issues.append("keine <packagedescription>")
    else:
        langs = {lang for lang, _ in descriptions}
        if None not in langs and "" not in langs:
            issues.append("packagedescription ohne Default (ohne language-Attribut)")
        if not any(lang == "de" for lang, _ in descriptions):
            issues.append('packagedescription language="de" fehlt')
        for lang, text in descriptions:
            if len(text) > max_len:
                label = lang or "default"
                issues.append(
                    f"packagedescription ({label}) zu lang: {len(text)} > {max_len}"
                )

    if not issues:
        print("OK: packagedescription vorhanden")
        return 0

    print(f"WARN: packagedescription hygiene in {root.name}:")
    for issue in issues:
        print(f"  - {issue}")
    return 1 if args.strict else 0


if __name__ == "__main__":
    sys.exit(main())
