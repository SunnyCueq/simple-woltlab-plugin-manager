#!/usr/bin/env python3
"""Warn when a 1.0.0 (pre-public) package still carries historical update baggage.

Intended for product lines that reset SemVer to 1.0.0 before the first store release.
Severity is warn by default (build continues); use --strict to exit 1.
"""

from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path


VERSION_RE = re.compile(r"<version>\s*([^<]+?)\s*</version>", re.I)
UPDATE_BLOCK_RE = re.compile(
    r'<instructions\s+type=["\']update["\'][^>]*>',
    re.I,
)
FROMVERSION_RE = re.compile(r'fromversion=["\']([^"\']+)["\']', re.I)


def package_roots(root: Path) -> list[Path]:
    candidates: list[Path] = []
    for name in ("package.xml", "temp_edit/package.xml"):
        p = root / name
        if p.is_file():
            candidates.append(p.parent if name == "package.xml" else p.parent.parent)
            break
    # Also accept root itself if package.xml is only under temp_edit (already handled)
    if not candidates and (root / "temp_edit" / "package.xml").is_file():
        candidates.append(root)
    return candidates or ([root] if (root / "package.xml").is_file() else [])


def find_package_xml(root: Path) -> Path | None:
    for rel in ("package.xml", "temp_edit/package.xml"):
        p = root / rel
        if p.is_file():
            return p
    return None


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("plugin_root", type=Path, help="Plugin / package root")
    parser.add_argument(
        "--strict",
        action="store_true",
        help="Exit 1 when hygiene issues are found (default: warn only)",
    )
    args = parser.parse_args()
    root = args.plugin_root.resolve()
    pkg = find_package_xml(root)
    if pkg is None:
        print(f"SKIP: no package.xml under {root}")
        return 0

    text = pkg.read_text(encoding="utf-8")
    m = VERSION_RE.search(text)
    if not m:
        print(f"WARN: {pkg}: missing <version>")
        return 1 if args.strict else 0

    version = m.group(1).strip()
    if version != "1.0.0":
        print(f"OK: version {version} — first-release hygiene check skipped")
        return 0

    issues: list[str] = []
    update_blocks = UPDATE_BLOCK_RE.findall(text)
    if update_blocks:
        fvs = FROMVERSION_RE.findall(text)
        issues.append(
            f"version 1.0.0 still has {len(update_blocks)} update instruction block(s)"
            + (f" (fromversion={', '.join(fvs)})" if fvs else "")
        )

    # Historical migration scripts that should not ship in a first release
    for pattern in ("**/post_update_*.php", "**/acp/database/update_*.php"):
        for hit in sorted(root.glob(pattern)):
            # Ignore vendor / examples if any
            if "examples" in hit.parts or "node_modules" in hit.parts:
                continue
            issues.append(f"obsolete migration script present: {hit.relative_to(root)}")

    if not issues:
        print(f"OK: {pkg.relative_to(root) if root in pkg.parents else pkg.name} is clean for 1.0.0 first release")
        return 0

    print(f"WARN: first-release hygiene issues in {root.name}:")
    for issue in issues:
        print(f"  - {issue}")
    return 1 if args.strict else 0


if __name__ == "__main__":
    sys.exit(main())
