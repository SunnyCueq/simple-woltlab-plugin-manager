#!/usr/bin/env python3
"""
Validate structural integrity of language/*.xml files:

1. Invalid attributes on <item> — WoltLab's language.xsd allows ONLY `name`.
   A `variant="informal"` attribute does not exist in WoltLab; on import the
   attribute is ignored and duplicate names silently overwrite each other
   (last one wins). Formal/informal German belongs INSIDE the value:
   {if LANGUAGE_USE_INFORMAL_VARIANT}du{else}Sie{/if}
2. Duplicate item names within one file — non-deterministic install result.
3. Template scripting ({if ...}) inside the wcf.global category — the
   wcf.global category does not support template scripting.

Befund Shr1nkr 2026-07-02: 165 variant="informal"-Items + 176 doppelte Keys
in de.xml — XSD-invalide, beim Import gewann still der letzte Wert.

Usage: python3 check-language-integrity.py [plugin_dir]
Output: one issue per line: <file>:<line>:<message>  (empty = clean)
"""
from __future__ import annotations

import re
import sys
from collections import defaultdict
from pathlib import Path

ITEM_TAG = re.compile(r'<item\s([^>]*?)>')
NAME_ATTR = re.compile(r'name="([^"]+)"')
EXTRA_ATTR = re.compile(r'(\w+)="[^"]*"')
CATEGORY_TAG = re.compile(r'<category\s+name="([^"]+)"')


def check_file(path: Path) -> list[str]:
    issues: list[str] = []
    seen: dict[str, int] = {}
    by_key_count: defaultdict[str, int] = defaultdict(int)
    current_category = ""

    for lineno, line in enumerate(path.read_text(encoding="utf-8", errors="replace").splitlines(), start=1):
        cat = CATEGORY_TAG.search(line)
        if cat:
            current_category = cat.group(1)

        for m in ITEM_TAG.finditer(line):
            attrs = m.group(1)
            name_m = NAME_ATTR.search(attrs)
            name = name_m.group(1) if name_m else "?"

            for am in EXTRA_ATTR.finditer(attrs):
                if am.group(1) != "name":
                    issues.append(
                        f"{path}:{lineno}:Ungültiges Attribut '{am.group(1)}' auf <item name=\"{name}\"> "
                        f"— language.xsd erlaubt nur 'name'; Sie/du gehört als "
                        "{if LANGUAGE_USE_INFORMAL_VARIANT}…{else}…{/if} in den Wert"
                    )

            if name in seen:
                by_key_count[name] += 1
                issues.append(
                    f"{path}:{lineno}:Doppelter Key '{name}' (erste Definition Zeile {seen[name]}) "
                    "— beim Import gewinnt still der letzte Wert"
                )
            else:
                seen[name] = lineno

            if current_category == "wcf.global" and "{if " in line:
                issues.append(
                    f"{path}:{lineno}:Template-Scripting in wcf.global ('{name}') — dort nicht unterstützt"
                )

    return issues


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".")
    lang_dir = root / "language"
    if not lang_dir.is_dir():
        return 0
    issues: list[str] = []
    for xml in sorted(lang_dir.glob("*.xml")):
        issues.extend(check_file(xml))
    for issue in issues:
        print(issue)
    return 0


if __name__ == "__main__":
    sys.exit(main())
