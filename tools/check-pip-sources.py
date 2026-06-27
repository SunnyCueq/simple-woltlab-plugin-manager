#!/usr/bin/env python3
"""
WoltLab DevTools parity: PIP source targets, sync classification, update paths.

Mirrors DevtoolsPip::getTargets() / isSupported() for plugin repo layouts
(temp_edit: lib/, acp/, templates/ …).

Usage:
  check-pip-sources.py [--strict] PLUGIN_DIR [package.xml]

Exit 0 = OK, 1 = usage error, 2 = missing sources (--strict) or warnings as errors
"""
from __future__ import annotations

import argparse
import glob
import re
import sys
import xml.etree.ElementTree as ET
from dataclasses import dataclass
from pathlib import Path

NS = {"p": "http://www.woltlab.com"}

# PIPs without IIdempotentPackageInstallationPlugin — DevTools cannot sync these.
PACKAGE_ONLY_TYPES = frozenset({"script", "sql"})

# XML PIPs use {type}.xml unless instruction body overrides.
XML_PIP_TYPES = frozenset(
    {
        "option",
        "page",
        "language",
        "userGroupOption",
        "templateListener",
        "eventListener",
        "objectTypeDefinition",
        "objectType",
        "box",
        "menuItem",
        "cronjob",
        "bbcode",
        "aclOption",
        "acpMenu",
        "acpSearchProvider",
        "pip",
        "userNotificationEvent",
        "userOption",
        "coreObject",
        "style",
        "smiley",
        "tag",
        "mediaProvider",
    }
)


@dataclass
class Instruction:
    group: str  # install | update
    pip_type: str
    value: str
    application: str | None
    fromversion: str | None


@dataclass
class TargetResult:
    instruction: Instruction
    expected: str
    found: bool
    syncable: bool


def local_tag(elem) -> str:
    return elem.tag.split("}")[-1] if "}" in elem.tag else elem.tag


def parse_package_xml(path: Path) -> tuple[str, list[Instruction]]:
    tree = ET.parse(path)
    root = tree.getroot()
    version = ""
    for child in root:
        if local_tag(child) == "packageinformation":
            for sub in child:
                if local_tag(sub) == "version" and sub.text:
                    version = sub.text.strip()
    instructions: list[Instruction] = []
    for block in root:
        tag = local_tag(block)
        if tag != "instructions":
            continue
        group = block.get("type", "install")
        fromversion = block.get("fromversion")
        for inst in block:
            if local_tag(inst) != "instruction":
                continue
            pip_type = inst.get("type", "").strip()
            if not pip_type:
                continue
            value = (inst.text or "").strip()
            application = inst.get("application")
            instructions.append(
                Instruction(group, pip_type, value, application, fromversion)
            )
    return version, instructions


def has_file_sources(root: Path, app: str | None) -> bool:
    if app == "wcf":
        return (root / "js").is_dir() or (root / "lib" / "bootstrap").is_dir() or (
            root / "files_wcf"
        ).is_dir()
    return (
        (root / "lib").is_dir()
        or (root / "acp").is_dir()
        or (root / "style").is_dir()
        or (root / "files").is_dir()
    )


def has_template_sources(root: Path) -> bool:
    if (root / "templates").is_dir():
        return any((root / "templates").rglob("*.tpl"))
    return any(root.glob("*.tpl"))


def has_acp_template_sources(root: Path) -> bool:
    return (root / "acptemplates").is_dir() and any(
        (root / "acptemplates").rglob("*.tpl")
    )


def has_language_sources(root: Path) -> bool:
    lang = root / "language"
    if not lang.is_dir():
        return (root / "language.xml").is_file()
    return any(lang.glob("*.xml"))


def glob_exists(root: Path, pattern: str) -> bool:
    return bool(glob.glob(str(root / pattern), recursive=True))


def resolve_target(root: Path, inst: Instruction) -> TargetResult:
    pip = inst.pip_type
    syncable = pip not in PACKAGE_ONLY_TYPES

    if pip == "file":
        label = "files.tar ← files/ or lib/,acp/,style/"
        if inst.application == "wcf":
            label = "files_wcf.tar ← js/, lib/bootstrap/ or files_wcf/"
        ok = has_file_sources(root, inst.application)
        return TargetResult(inst, label, ok, syncable)

    if pip == "template":
        label = "templates.tar ← templates/ or *.tpl"
        return TargetResult(inst, label, has_template_sources(root), syncable)

    if pip == "acpTemplate":
        label = "acptemplates.tar ← acptemplates/"
        return TargetResult(inst, label, has_acp_template_sources(root), syncable)

    if pip == "language":
        label = "language/*.xml"
        return TargetResult(inst, label, has_language_sources(root), syncable)

    if pip == "database":
        if inst.value:
            path = root / inst.value
            return TargetResult(inst, inst.value, path.is_file(), syncable)
        pattern = "acp/database/*.php"
        return TargetResult(
            inst, pattern, glob_exists(root, pattern), syncable
        )

    if pip in PACKAGE_ONLY_TYPES:
        if inst.value:
            path = root / inst.value
            return TargetResult(
                inst,
                inst.value,
                path.is_file(),
                False,
            )
        return TargetResult(inst, f"{pip} (Pfad in package.xml)", False, False)

    if pip in XML_PIP_TYPES or pip.endswith("Option") or pip.endswith("Listener"):
        filename = inst.value or f"{pip}.xml"
        path = root / filename
        return TargetResult(inst, filename, path.is_file(), syncable)

    if inst.value:
        path = root / inst.value
        return TargetResult(inst, inst.value, path.is_file(), syncable)

    default = f"{pip}.xml"
    path = root / default
    return TargetResult(
        inst, default, path.is_file(), syncable if pip not in PACKAGE_ONLY_TYPES else False
    )


def dedupe_instructions(instructions: list[Instruction]) -> list[Instruction]:
    seen: set[tuple] = set()
    out: list[Instruction] = []
    for i in instructions:
        key = (i.group, i.fromversion, i.pip_type, i.value, i.application)
        if key in seen:
            continue
        seen.add(key)
        out.append(i)
    return out


def main() -> int:
    parser = argparse.ArgumentParser(description="Check PIP sources (DevTools parity)")
    parser.add_argument(
        "--strict",
        action="store_true",
        help="Exit 2 when any expected source is missing",
    )
    parser.add_argument(
        "plugin_dir",
        nargs="?",
        default=None,
        help="Plugin root or temp_edit (default: basis-plugin/temp_edit)",
    )
    parser.add_argument(
        "package_xml",
        nargs="?",
        default=None,
        help="package.xml path (default: PLUGIN_DIR/package.xml)",
    )
    args = parser.parse_args()

    tools_dir = Path(__file__).resolve().parent
    if args.plugin_dir:
        root = Path(args.plugin_dir).resolve()
    else:
        root = tools_dir.parent / "basis-plugin" / "temp_edit"

    pkg_path = Path(args.package_xml).resolve() if args.package_xml else root / "package.xml"
    if not pkg_path.is_file():
        print(f"package.xml nicht gefunden: {pkg_path}", file=sys.stderr)
        return 1
    if not root.is_dir():
        print(f"Plugin-Verzeichnis nicht gefunden: {root}", file=sys.stderr)
        return 1

    version, instructions = parse_package_xml(pkg_path)
    instructions = dedupe_instructions(instructions)

    print(f"=== PIP-Quellen (DevTools-Parität) — {pkg_path.name} v{version} ===")
    print(f"    Root: {root}")
    print()

    missing: list[TargetResult] = []
    sync_ok: list[TargetResult] = []
    package_only: list[TargetResult] = []

    current_group = ""
    for inst in instructions:
        if inst.group != current_group:
            current_group = inst.group
            fv = f" fromversion={inst.fromversion}" if inst.fromversion else ""
            print(f"--- {current_group}{fv} ---")
        result = resolve_target(root, inst)
        app = f" app={inst.application}" if inst.application else ""
        status = "OK" if result.found else "FEHLT"
        sync = "sync" if result.syncable else "nur Paket-Update"
        print(f"  [{status}] {inst.pip_type}{app}: {result.expected} ({sync})")
        if not result.found:
            missing.append(result)
        elif result.syncable:
            sync_ok.append(result)
        else:
            package_only.append(result)

    print()
    print("--- Zusammenfassung ---")
    print(f"  Sync-fähig (DevTools): {len(sync_ok)}")
    print(f"  Nur Paket-Update:        {len(package_only)}")
    print(f"  Fehlende Quellen:        {len(missing)}")

    if package_only:
        print()
        print("Hinweis: script/sql-Änderungen erfordern ACP-Paket-Update, kein Projekt-Abgleich.")

    if missing:
        print()
        print("Fehlende Quellen:")
        for m in missing:
            fv = f" [{m.instruction.group}]" if m.instruction.group != "install" else ""
            print(f"  - {m.instruction.pip_type}: {m.expected}{fv}")
        if args.strict:
            return 2
        return 2

    return 0


if __name__ == "__main__":
    sys.exit(main())
