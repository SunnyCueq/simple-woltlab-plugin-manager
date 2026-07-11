#!/usr/bin/env python3
"""Warn when frontend .tpl files sit in the plugin root instead of templates/.

WoltLab / wspackager canonical layout:
  templates/*.tpl  → templates.tar
  acptemplates/*.tpl → acptemplates.tar
  option.xml, page.xml, … stay in the package root (PIP XMLs)

Root-level *.tpl is a legacy SWPM fallback (still packed by build.sh).
Default: warn and exit 0. With --strict: exit 2.

Usage:
  check-template-layout.py [--strict] [--json] PLUGIN_DIR
"""
from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path


def find_root_tpls(root: Path) -> list[Path]:
    """Return *.tpl directly under root (not under templates/ or acptemplates/)."""
    return sorted(p for p in root.glob("*.tpl") if p.is_file())


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Check frontend templates live under templates/ (WoltLab layout)"
    )
    parser.add_argument(
        "--strict",
        action="store_true",
        help="Exit 2 when root-level *.tpl files are present",
    )
    parser.add_argument(
        "--json",
        action="store_true",
        help="Machine-readable JSON on stdout",
    )
    parser.add_argument(
        "plugin_dir",
        nargs="?",
        default=".",
        help="Plugin root or temp_edit (default: .)",
    )
    args = parser.parse_args()

    root = Path(args.plugin_dir).resolve()
    if not root.is_dir():
        print(f"Plugin-Verzeichnis nicht gefunden: {root}", file=sys.stderr)
        return 1

    root_tpls = find_root_tpls(root)
    has_templates_dir = (root / "templates").is_dir() and any(
        (root / "templates").glob("*.tpl")
    )

    messages = [
        f"{p.name}: nach templates/ verschieben (WoltLab-Norm)" for p in root_tpls
    ]

    exit_code = 2 if root_tpls and args.strict else 0

    if args.json:
        json.dump(
            {
                "ok": exit_code == 0,
                "tool": {
                    "name": "simple-woltlab-plugin-manager",
                    "component": "check-template-layout.py",
                },
                "source": str(root),
                "canonical": "templates/",
                "has_templates_dir": has_templates_dir,
                "root_tpls": [p.name for p in root_tpls],
                "warnings": messages,
            },
            sys.stdout,
            indent=2,
            ensure_ascii=False,
        )
        sys.stdout.write("\n")
        return exit_code

    if not root_tpls:
        if has_templates_dir:
            print(f"OK: Frontend-Templates unter templates/ ({root})")
        else:
            print(f"OK: keine Root-*.tpl ({root})")
        return 0

    print(f"=== Template-Layout — {root} ===")
    print("Kanonisch: templates/*.tpl → templates.tar")
    print("Legacy-Fallback: Root-*.tpl (build packt weiter, bitte migrieren)")
    print()
    for msg in messages:
        print(f"  WARN: {msg}")
    print()
    print("PIP-XMLs (option.xml, page.xml, …) bleiben im Root.")
    print("acptemplates/ unverändert.")
    if args.strict:
        print("--strict: Root-*.tpl sind ein Fehler.")
        return 2
    return 0


if __name__ == "__main__":
    sys.exit(main())
