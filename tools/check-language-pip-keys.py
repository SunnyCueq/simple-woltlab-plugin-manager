#!/usr/bin/env python3
"""Prüft, ob implizite PIP-Sprachkeys in den Sprachdateien definiert sind.

Hintergrund (Praxis-Befund 2026-07-16):
- userGroupOption.xml, option.xml und acpMenu.xml erzeugen Sprachkeys implizit
  aus den name-Attributen (wcf.acp.group.option.<name>,
  wcf.acp.group.option.category.<name>, wcf.acp.option.<name>,
  wcf.acp.option.category.<name>, ACP-Menüpunkt-Namen).
- Diese Keys tauchen nirgends im Plugin-Code auf — check-language-keys.py
  (Code-Nutzung) kann sie deshalb nicht finden.
- Fehlt ein Key, zeigt das ACP den rohen Key an (z. B.
  "wcf.acp.group.option.category.admin.myaddon" als Tab-Titel in den
  Gruppenrechten) — sichtbarer Defekt, fällt im Store-Review auf.

Geprüft werden nur Kategorien/Optionen, die das Plugin selbst deklariert.
Hinweis: Wer eine bereits im Core existierende Options-Kategorie erneut
deklariert, bekommt einen False Positive — Kategorien gehören nur in die XML,
wenn das Plugin sie anlegt.

Ausgabe: <pip-datei>:<key>:fehlt-in:<sprachdateien>
Exit 0 = sauber, 1 = Funde.

Usage:
  check-language-pip-keys.py PLUGIN_DIR
  check-language-pip-keys.py --count PLUGIN_DIR
"""
from __future__ import annotations

import argparse
import sys
from pathlib import Path
from xml.etree import ElementTree as ET


def local_tag(tag: str) -> str:
    return tag.split("}")[-1] if "}" in tag else tag


def defined_language_keys(root: Path) -> dict[Path, set[str]]:
    """Alle <item name="…">-Keys je Basis-Sprachdatei (language/*.xml)."""
    result: dict[Path, set[str]] = {}
    lang_dir = root / "language"
    if not lang_dir.is_dir():
        return result
    for xml in sorted(lang_dir.glob("*.xml")):
        try:
            tree = ET.parse(xml)
        except ET.ParseError:
            continue
        keys = {
            el.get("name")
            for el in tree.getroot().iter()
            if local_tag(el.tag) == "item" and el.get("name")
        }
        result[xml] = keys
    return result


def under_delete(el: ET.Element, parent_map: dict[ET.Element, ET.Element]) -> bool:
    """True, wenn das Element unter einem <delete>-Vorfahren liegt (keine ACP-Phrasen nötig)."""
    parent = parent_map.get(el)
    while parent is not None:
        if local_tag(parent.tag) == "delete":
            return True
        parent = parent_map.get(parent)
    return False


def parent_map(root: ET.Element) -> dict[ET.Element, ET.Element]:
    """ElementTree hat keine parent-API — Parent-Map einmalig bauen."""
    mapping: dict[ET.Element, ET.Element] = {}
    for parent in root.iter():
        for child in parent:
            mapping[child] = parent
    return mapping


def required_keys(root: Path) -> list[tuple[Path, str, str]]:
    """(pip-datei, key, art) aller implizit erzeugten Sprachkeys."""
    required: list[tuple[Path, str, str]] = []

    def collect(path: Path, category_prefix: str, option_prefix: str, what: str) -> None:
        if not path.is_file():
            return
        try:
            tree = ET.parse(path)
        except ET.ParseError:
            return
        xml_root = tree.getroot()
        parents = parent_map(xml_root)
        for el in xml_root.iter():
            if under_delete(el, parents):
                continue
            tag = local_tag(el.tag)
            name = el.get("name") or ""
            if not name:
                continue
            if tag == "category":
                required.append((path, f"{category_prefix}{name}", f"{what}-Kategorie"))
            elif tag == "option":
                required.append((path, f"{option_prefix}{name}", what))

    collect(
        root / "userGroupOption.xml",
        "wcf.acp.group.option.category.", "wcf.acp.group.option.", "Gruppenrecht",
    )
    collect(
        root / "option.xml",
        "wcf.acp.option.category.", "wcf.acp.option.", "Option",
    )

    acp_menu = root / "acpMenu.xml"
    if acp_menu.is_file():
        try:
            for el in ET.parse(acp_menu).getroot().iter():
                if local_tag(el.tag) == "acpmenuitem" and el.get("name"):
                    required.append((acp_menu, el.get("name"), "ACP-Menüpunkt"))
        except ET.ParseError:
            pass

    return required


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Implizite PIP-Sprachkeys gegen language/*.xml prüfen"
    )
    parser.add_argument("plugin_dir", type=Path)
    parser.add_argument("--count", action="store_true")
    args = parser.parse_args()

    root = args.plugin_dir.resolve()
    if not root.is_dir():
        print(f"Not a directory: {root}", file=sys.stderr)
        return 2

    lang_keys = defined_language_keys(root)
    if not lang_keys:
        return 0

    total = 0
    for pip_path, key, what in required_keys(root):
        missing_in = sorted(
            xml.name for xml, keys in lang_keys.items() if key not in keys
        )
        if not missing_in:
            continue
        total += 1
        if not args.count:
            rel = pip_path.relative_to(root) if pip_path.is_relative_to(root) else pip_path
            print(
                f"{rel}:{key}:{what} fehlt in {', '.join(missing_in)} — "
                "ACP zeigt sonst den rohen Sprachkey an"
            )

    if args.count:
        print(total)
    return 1 if total else 0


if __name__ == "__main__":
    sys.exit(main())
