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
import json
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
    case_mismatch: bool = False


def path_exists_case_sensitive(root: Path, rel: str) -> tuple[bool, bool]:
    """Return (exists, case_ok). On case-insensitive FS, case_ok may be False while exists True."""
    rel_path = Path(rel)
    current = root
    for i, part in enumerate(rel_path.parts):
        if not current.is_dir():
            return False, True
        names = [p.name for p in current.iterdir()]
        match = [n for n in names if n == part]
        if not match:
            ci = [n for n in names if n.lower() == part.lower()]
            if ci:
                return True, False
            return False, True
        current = current / part
    if current.is_file():
        return True, True
    if current.is_dir():
        return True, True
    return current.exists(), True


def resolve_style_assets(root: Path, style_instruction_value: str) -> list[tuple[str, bool, str]]:
    """Resolve style PIP sources.

    package.xml value is the *archive* name (style.tar / style.tgz), not style.xml.
    Sources live under style/style.xml (+ variables, images/, templates/).
    """
    style_xml = root / "style" / "style.xml"
    if not style_xml.is_file():
        # Legacy: instruction points at style.xml path
        legacy = root / (style_instruction_value or "style.xml")
        if legacy.is_file() and legacy.suffix == ".xml":
            style_xml = legacy
        else:
            return [("style/style.xml", False, "style/style.xml fehlt (style PIP)")]

    out: list[tuple[str, bool, str]] = [(str(style_xml.relative_to(root)), True, "")]
    try:
        tree = ET.parse(style_xml)
        files_node = None
        for elem in tree.getroot().iter():
            if elem.tag.split("}")[-1] == "files":
                files_node = elem
                break
        if files_node is None:
            return out
        for child in files_node:
            tag = child.tag.split("}")[-1]
            name = (child.text or "").strip()
            if not name:
                continue
            if tag in ("variables", "variablesDarkMode"):
                path = style_xml.parent / name
                label = f"style/{name}"
                out.append((label, path.is_file(), str(path)))
                continue
            if tag not in ("templates", "images"):
                continue
            folder = name
            for suf in (".tar.gz", ".tgz", ".tar"):
                if folder.endswith(suf):
                    folder = folder[: -len(suf)]
                    break
            candidates = [
                style_xml.parent / folder,
                root / folder,
                root / "style" / folder,
            ]
            found_dir = next((c for c in candidates if c.is_dir()), None)
            label = f"style/{name} ← {folder}/"
            out.append((label, found_dir is not None, str(found_dir or candidates[0])))
    except ET.ParseError as exc:
        out.append(("style.xml parse", False, str(exc)))
    return out


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


def resolve_target(
    root: Path,
    inst: Instruction,
    *,
    pip_overrides: dict[str, str],
    strict_case: bool,
) -> TargetResult:
    pip = inst.pip_type
    syncable = pip not in PACKAGE_ONLY_TYPES

    def check_file(rel: str) -> tuple[bool, bool]:
        if strict_case:
            return path_exists_case_sensitive(root, rel)
        p = root / rel
        return p.is_file(), True

    if pip == "file":
        label = "files.tar ← files/ or lib/,acp/,style/"
        if inst.application == "wcf":
            label = "files_wcf.tar ← js/, lib/bootstrap/ or files_wcf/"
        ok = has_file_sources(root, inst.application)
        return TargetResult(inst, label, ok, syncable)

    if pip == "template":
        label = "templates.tar ← templates/ (Legacy: Root-*.tpl)"
        return TargetResult(inst, label, has_template_sources(root), syncable)

    if pip == "acpTemplate":
        label = "acptemplates.tar ← acptemplates/"
        return TargetResult(inst, label, has_acp_template_sources(root), syncable)

    if pip == "language":
        label = "language/*.xml"
        return TargetResult(inst, label, has_language_sources(root), syncable)

    if pip == "style":
        archive = inst.value or "style.tar"
        assets = resolve_style_assets(root, archive)
        missing = [a for a in assets if not a[1]]
        label = f"style PIP ← {archive} (Quellen style/)" + (
            "" if not missing else f" (+ {len(missing)} fehlend)"
        )
        return TargetResult(inst, label, not missing, syncable)

    if pip == "database":
        if inst.value:
            found, case_ok = check_file(inst.value)
            return TargetResult(
                inst,
                inst.value,
                found,
                syncable,
                case_mismatch=found and not case_ok,
            )
        pattern = "acp/database/*.php"
        return TargetResult(
            inst, pattern, glob_exists(root, pattern), syncable
        )

    if pip in PACKAGE_ONLY_TYPES:
        if inst.value:
            found, case_ok = check_file(inst.value)
            # WCF scripts run as WCF_DIR + path after files_wcf.tar extract.
            # Sources usually live under files_wcf/<path> in the package tree.
            if not found and inst.application in (None, "", "wcf"):
                alt = f"files_wcf/{inst.value.lstrip('/')}"
                found_alt, case_ok_alt = check_file(alt)
                if found_alt:
                    return TargetResult(
                        inst,
                        f"{inst.value} ← {alt}",
                        True,
                        False,
                        case_mismatch=not case_ok_alt,
                    )
            return TargetResult(
                inst,
                inst.value,
                found,
                False,
                case_mismatch=found and not case_ok,
            )
        return TargetResult(inst, f"{pip} (Pfad in package.xml)", False, False)

    if pip in XML_PIP_TYPES or pip.endswith("Option") or pip.endswith("Listener"):
        filename = inst.value or pip_overrides.get(pip) or f"{pip}.xml"
        found, case_ok = check_file(filename)
        return TargetResult(
            inst,
            filename,
            found,
            syncable,
            case_mismatch=found and not case_ok,
        )

    if inst.value:
        found, case_ok = check_file(inst.value)
        return TargetResult(
            inst,
            inst.value,
            found,
            syncable,
            case_mismatch=found and not case_ok,
        )

    default = pip_overrides.get(pip) or f"{pip}.xml"
    found, case_ok = check_file(default)
    return TargetResult(
        inst,
        default,
        found,
        syncable if pip not in PACKAGE_ONLY_TYPES else False,
        case_mismatch=found and not case_ok,
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


def emit(payload: dict) -> None:
    json.dump(payload, sys.stdout, indent=2, ensure_ascii=False)
    sys.stdout.write("\n")


def main() -> int:
    parser = argparse.ArgumentParser(description="Check PIP sources (DevTools parity)")
    parser.add_argument(
        "--strict",
        action="store_true",
        help="Exit 2 when any expected source is missing",
    )
    parser.add_argument(
        "--strict-case",
        action="store_true",
        help="Fail when path exists with wrong letter case (Linux/server parity)",
    )
    parser.add_argument(
        "--json",
        action="store_true",
        help="Machine-readable JSON on stdout",
    )
    parser.add_argument(
        "--pip",
        action="append",
        default=[],
        metavar="type=path",
        help="3rd-party PIP default file, e.g. --pip banana=banana.xml",
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

    pip_overrides: dict[str, str] = {}
    for item in args.pip:
        if "=" not in item:
            print(f"Ungültiges --pip: {item} (erwartet type=path)", file=sys.stderr)
            return 1
        k, v = item.split("=", 1)
        pip_overrides[k.strip()] = v.strip()

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
    package_id = pkg_path.parent.name
    try:
        tree = ET.parse(pkg_path)
        root_el = tree.getroot()
        if root_el.get("name"):
            package_id = root_el.get("name", package_id)
    except ET.ParseError:
        pass

    missing: list[TargetResult] = []
    case_errors: list[TargetResult] = []
    sync_ok: list[TargetResult] = []
    package_only: list[TargetResult] = []
    results: list[TargetResult] = []
    rows: list[dict] = []

    for inst in instructions:
        result = resolve_target(
            root,
            inst,
            pip_overrides=pip_overrides,
            strict_case=args.strict_case,
        )
        results.append(result)
        rows.append(
            {
                "group": inst.group,
                "fromversion": inst.fromversion,
                "pip": inst.pip_type,
                "application": inst.application,
                "expected": result.expected,
                "found": result.found,
                "syncable": result.syncable,
                "case_mismatch": result.case_mismatch,
            }
        )
        if result.case_mismatch:
            case_errors.append(result)
        if not result.found:
            missing.append(result)
        elif result.syncable:
            sync_ok.append(result)
        else:
            package_only.append(result)

    exit_code = 0
    if missing or (case_errors and (args.strict_case or args.strict)):
        exit_code = 2

    if args.json:
        emit(
            {
                "ok": exit_code == 0,
                "tool": {
                    "name": "simple-woltlab-plugin-manager",
                    "component": "check-pip-sources.py",
                },
                "source": str(root),
                "package": {"identifier": package_id, "version": version},
                "summary": {
                    "sync_ok": len(sync_ok),
                    "package_only": len(package_only),
                    "missing": len(missing),
                    "case_mismatch": len(case_errors),
                },
                "instructions": rows,
                "missing": [
                    {"pip": m.instruction.pip_type, "expected": m.expected}
                    for m in missing
                ],
                "case_mismatch": [
                    {"pip": m.instruction.pip_type, "expected": m.expected}
                    for m in case_errors
                ],
            }
        )
        return exit_code

    print(f"=== PIP-Quellen (DevTools-Parität) — {pkg_path.name} v{version} ===")
    print(f"    Root: {root}")
    print()

    current_group = ""
    for inst, result in zip(instructions, results):
        if inst.group != current_group:
            current_group = inst.group
            fv = f" fromversion={inst.fromversion}" if inst.fromversion else ""
            print(f"--- {current_group}{fv} ---")
        app = f" app={inst.application}" if inst.application else ""
        if result.case_mismatch:
            status = "CASE"
        else:
            status = "OK" if result.found else "FEHLT"
        sync = "sync" if result.syncable else "nur Paket-Update"
        print(f"  [{status}] {inst.pip_type}{app}: {result.expected} ({sync})")

    print()
    print("--- Zusammenfassung ---")
    print(f"  Sync-fähig (DevTools): {len(sync_ok)}")
    print(f"  Nur Paket-Update:        {len(package_only)}")
    print(f"  Fehlende Quellen:        {len(missing)}")
    if case_errors:
        print(f"  Falsche Groß/Kleinschreibung: {len(case_errors)}")

    if package_only:
        print()
        print("Hinweis: script/sql-Änderungen erfordern ACP-Paket-Update, kein Projekt-Abgleich.")

    if case_errors:
        print()
        print("Groß-/Kleinschreibung (Pfade sind case-sensitive):")
        for m in case_errors:
            print(f"  - {m.instruction.pip_type}: {m.expected}")

    if missing:
        print()
        print("Fehlende Quellen:")
        for m in missing:
            fv = f" [{m.instruction.group}]" if m.instruction.group != "install" else ""
            print(f"  - {m.instruction.pip_type}: {m.expected}{fv}")
        return 2

    if case_errors and (args.strict_case or args.strict):
        return 2
    return 0


if __name__ == "__main__":
    sys.exit(main())
