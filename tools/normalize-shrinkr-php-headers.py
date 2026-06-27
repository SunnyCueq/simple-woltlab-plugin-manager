#!/usr/bin/env python3
"""
Insert the standard proprietary file docblock after <?php in Shr1nkr temp_edit PHP files
when the marker text is not yet present.
"""
from __future__ import annotations

import os
import sys

MARKER = "Proprietary License; No redistribution or manual modification allowed."

STANDARD_BLOCK = """/**
 * @author    Sunny C.
 * @copyright 2024-2026 Sunny C.
 * @license   Proprietary License; No redistribution or manual modification allowed.
 * @link      https://sunnyc.de
 */
"""


def process_file(path: str) -> bool:
    with open(path, encoding="utf-8") as f:
        raw = f.read()
    if MARKER in raw:
        return False
    if not raw.startswith("<?php"):
        return False
    lines = raw.splitlines(keepends=True)
    if not lines:
        return False
    first = lines[0].strip()
    if first != "<?php":
        return False
    body = "".join(lines[1:])
    new_raw = lines[0].rstrip("\r\n") + "\n\n" + STANDARD_BLOCK + "\n" + body
    with open(path, "w", encoding="utf-8", newline="\n") as f:
        f.write(new_raw)
    return True


def main() -> int:
    root = os.path.join(
        os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
        "basis-plugin",
        "temp_edit",
    )
    if not os.path.isdir(root):
        print(f"Not found: {root}", file=sys.stderr)
        return 1
    changed = 0
    total = 0
    for dirpath, _, filenames in os.walk(root):
        for name in filenames:
            if not name.endswith(".php"):
                continue
            total += 1
            path = os.path.join(dirpath, name)
            if process_file(path):
                changed += 1
                print(f"+ {path}")
    print(f"Done. {changed} updated, {total} PHP files scanned.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
