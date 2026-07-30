#!/usr/bin/env python3
"""Fail on empty directories under pack roots (they land in files.tar / files_wcf.tar).

``tar -cf … -C files .`` includes empty dirs (e.g. leftover ``files/acp/`` with no
assets). Install then creates an empty ``acp/`` under the app — noise and a common
scaffold leftover when ACP PHP lives under ``files/lib/acp/`` instead.

Usage:
  check-empty-pack-dirs.py PLUGIN_DIR
Exit 0 = clean, 2 = empty dirs found, 1 = usage/IO.
"""
from __future__ import annotations

import sys
from pathlib import Path


def pack_roots(plugin: Path) -> list[Path]:
    """Directories whose contents are tar'd into the package (relative trees)."""
    roots: list[Path] = []
    files = plugin / "files"
    if files.is_dir():
        roots.append(files)
    files_wcf = plugin / "files_wcf"
    if files_wcf.is_dir():
        roots.append(files_wcf)
    # Legacy root layout (no files/): lib/, acp/, style/ packed into files.tar
    if not files.is_dir():
        for name in ("lib", "acp", "style"):
            p = plugin / name
            if p.is_dir():
                roots.append(p)
    for name in ("templates", "acptemplates"):
        p = plugin / name
        if p.is_dir():
            roots.append(p)
    return roots


def empty_dirs(root: Path) -> list[Path]:
    """Directories with no entries (find -type d -empty)."""
    found: list[Path] = []
    for dirpath, dirnames, filenames in os_walk_sorted(root):
        if not dirnames and not filenames:
            found.append(dirpath)
    return found


def os_walk_sorted(root: Path):
    """os.walk alternative that yields Path and sorts for stable output."""
    import os

    for dirpath, dirnames, filenames in os.walk(root):
        dirnames.sort()
        filenames.sort()
        yield Path(dirpath), dirnames, filenames


def rel(plugin: Path, path: Path) -> str:
    try:
        return str(path.relative_to(plugin)).replace("\\", "/")
    except ValueError:
        return str(path)


def main() -> int:
    if len(sys.argv) != 2:
        print(f"Usage: {sys.argv[0]} PLUGIN_DIR", file=sys.stderr)
        return 1
    plugin = Path(sys.argv[1]).resolve()
    if not plugin.is_dir():
        print(f"Kein Verzeichnis: {plugin}", file=sys.stderr)
        return 1

    issues: list[str] = []
    for root in pack_roots(plugin):
        for empty in empty_dirs(root):
            # Skip the pack root itself if the whole tree is empty — still report
            issues.append(rel(plugin, empty))

    if not issues:
        print("OK: keine leeren Pack-Ordner")
        return 0

    print("check-empty-pack-dirs: FEHLER — leere Ordner würden mit in PIP-Archive")
    print("  (tar packt auch Verzeichnisse ohne Dateien, z. B. Rest files/acp/).")
    print("  Fix: leere Ordner löschen, dann neu bauen.")
    for path in sorted(issues):
        print(f"  - {path}/")
    return 2


if __name__ == "__main__":
    sys.exit(main())
