#!/usr/bin/env python3
"""Fail if the installable package ships PIP archives that package.xml does not
request, or ships known WoltLab-demo leftover templates in templates.tar.

Root cause this catches: reusing a build slot (e.g. basis-plugin/) leaves an old
templates.tar on disk; build.sh used to ``cp *.tar`` into the final archive even
when the current package has no ``<instruction type="template">``.

Usage:
  check-package-pip-archives.py PACKAGE_DIR
  check-package-pip-archives.py --archive path/to/pkg_v1.0.0.tar.gz

PACKAGE_DIR = unpacked package root (package.xml + *.tar). Exit 0 OK, 2 fail.
"""
from __future__ import annotations

import argparse
import re
import sys
import tarfile
import tempfile
import xml.etree.ElementTree as ET
from pathlib import Path

# Default archive names when the instruction body is empty (WoltLab convention).
EMPTY_BODY_DEFAULTS = {
    "file": "files.tar",
    "template": "templates.tar",
    "acpTemplate": "acptemplates.tar",
    "style": "style.tar",
}

# Known scaffolding from WoltLab / demo packages — must not appear in other products.
DEMO_TEMPLATE_RE = re.compile(
    r"(^|/)(__wscDemo|wscDemoExtend|wscDemo|demoRequest\.tpl)",
    re.IGNORECASE,
)


def local_tag(elem: ET.Element) -> str:
    return elem.tag.split("}")[-1] if "}" in elem.tag else elem.tag


def package_id(pkg_xml: Path) -> str:
    root = ET.parse(pkg_xml).getroot()
    return (root.attrib.get("name") or "").strip()


def expected_archives(pkg_xml: Path) -> set[str]:
    """Archive filenames referenced by install/update file/template/acpTemplate/style PIPs."""
    root = ET.parse(pkg_xml).getroot()
    out: set[str] = set()
    for node in root.iter():
        if local_tag(node) != "instruction":
            continue
        pip = (node.attrib.get("type") or "").strip()
        if pip not in EMPTY_BODY_DEFAULTS:
            continue
        body = (node.text or "").strip()
        app = (node.attrib.get("application") or "").strip()
        if body:
            out.add(body)
            continue
        if pip == "file" and app == "wcf":
            out.add("files_wcf.tar")
        else:
            out.add(EMPTY_BODY_DEFAULTS[pip])
    return out


def list_root_archives(dir_path: Path) -> set[str]:
    """PIP archives at package root (.tar / .tgz / style.tar.gz) — not the outer product .tar.gz."""
    names: set[str] = set()
    for p in dir_path.iterdir():
        if not p.is_file():
            continue
        name = p.name
        if name.endswith(".tar.gz") and name != "style.tar.gz":
            continue
        if name.endswith(".tar") or name.endswith(".tgz") or name == "style.tar.gz":
            names.add(name)
    return names


def templates_in_archive(templates_tar: Path) -> list[str]:
    if not templates_tar.is_file():
        return []
    with tarfile.open(templates_tar, "r:*") as tf:
        return [m.name for m in tf.getmembers() if m.isfile()]


def check_dir(package_dir: Path) -> list[str]:
    errors: list[str] = []
    pkg_xml = package_dir / "package.xml"
    if not pkg_xml.is_file():
        return [f"package.xml fehlt in {package_dir}"]

    pid = package_id(pkg_xml)
    expected = expected_archives(pkg_xml)
    present = list_root_archives(package_dir)

    for name in sorted(present - expected):
        errors.append(
            f"Überflüssiges Archiv im Paket-Root (nicht in package.xml): {name} "
            f"— typisch: Rest aus anderem Plugin im gleichen Build-Slot"
        )
    for name in sorted(expected - present):
        errors.append(f"In package.xml referenziert, fehlt aber im Paket: {name}")

    tpl_tar = package_dir / "templates.tar"
    if tpl_tar.is_file():
        if "templates.tar" not in expected:
            errors.append(
                'templates.tar liegt im Paket, aber package.xml hat keine '
                '<instruction type="template">'
            )
        elif ".demo" not in pid.lower():
            for member in templates_in_archive(tpl_tar):
                if DEMO_TEMPLATE_RE.search(member):
                    errors.append(
                        f"Demo-/Scaffold-Template in templates.tar: {member} "
                        f"(Paket {pid} — fremder Rest, Store: keine überflüssigen Dateien)"
                    )
    return errors


def check_outer_archive(archive: Path) -> list[str]:
    with tempfile.TemporaryDirectory(prefix="swpm-pip-arch-") as tmp:
        dest = Path(tmp)
        with tarfile.open(archive, "r:*") as tf:
            tf.extractall(dest)
        if (dest / "package.xml").is_file():
            return check_dir(dest)
        subs = [p for p in dest.iterdir() if p.is_dir()]
        if len(subs) == 1 and (subs[0] / "package.xml").is_file():
            return check_dir(subs[0])
        return [f"package.xml nicht gefunden in {archive}"]


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("path", type=Path, help="Paket-Root oder fertiges .tar.gz")
    ap.add_argument(
        "--archive",
        action="store_true",
        help="path ist ein fertiges .tar.gz/.tar (sonst Verzeichnis)",
    )
    args = ap.parse_args()
    path: Path = args.path
    if not path.exists():
        print(f"Pfad nicht gefunden: {path}", file=sys.stderr)
        return 1

    as_archive = args.archive or path.name.endswith((".tar.gz", ".tgz"))
    errors = check_outer_archive(path) if as_archive else check_dir(path)

    if errors:
        print("check-package-pip-archives: FEHLER")
        for e in errors:
            print(f"  - {e}")
        return 2
    print("check-package-pip-archives: OK")
    return 0


if __name__ == "__main__":
    sys.exit(main())
