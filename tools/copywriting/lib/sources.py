"""Load strings from various language file formats."""

from __future__ import annotations

import json
import re
import xml.etree.ElementTree as ET
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class LanguageEntry:
    key: str
    text: str
    variant: str | None = None
    category: str | None = None


def load_language_file(path: Path, fmt: str) -> list[LanguageEntry]:
    fmt = (fmt or "woltlab-xml").lower().replace("_", "-")
    if fmt in ("woltlab-xml", "woltlab", "xml"):
        return _load_woltlab_xml(path)
    if fmt in ("json", "json-flat"):
        return _load_json_flat(path)
    if fmt in ("java-properties", "properties"):
        return _load_properties(path)
    if fmt in ("markdown-blocks", "md"):
        return _load_markdown_blocks(path)
    raise ValueError(f"Unbekanntes Format: {fmt}")


def _load_woltlab_xml(path: Path) -> list[LanguageEntry]:
    tree = ET.parse(path)
    root = tree.getroot()
    entries: list[LanguageEntry] = []
    current_category: str | None = None

    for elem in root.iter():
        tag = elem.tag.split("}")[-1]
        if tag == "category":
            current_category = elem.get("name")
        elif tag == "item":
            name = elem.get("name")
            if not name:
                continue
            variant = elem.get("variant")
            text = "".join(elem.itertext()).strip()
            entries.append(
                LanguageEntry(
                    key=name,
                    text=text,
                    variant=variant,
                    category=current_category,
                )
            )
    return entries


def _load_json_flat(path: Path) -> list[LanguageEntry]:
    data = json.loads(path.read_text(encoding="utf-8"))
    entries: list[LanguageEntry] = []
    if isinstance(data, dict):

        def walk(prefix: str, obj: object) -> None:
            if isinstance(obj, dict):
                for k, v in obj.items():
                    walk(f"{prefix}.{k}" if prefix else k, v)
            elif isinstance(obj, str):
                entries.append(LanguageEntry(key=prefix, text=obj))

        walk("", data)
    return entries


def _load_properties(path: Path) -> list[LanguageEntry]:
    entries: list[LanguageEntry] = []
    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or line.startswith("!"):
            continue
        if "=" not in line:
            continue
        key, _, value = line.partition("=")
        entries.append(LanguageEntry(key=key.strip(), text=value.strip()))
    return entries


def _load_markdown_blocks(path: Path) -> list[LanguageEntry]:
    content = path.read_text(encoding="utf-8")
    pattern = re.compile(
        r"```\s*\n([^\n`]+)\n```\s*\n\n([\s\S]*?)(?=\n---\n|\Z)",
        re.MULTILINE,
    )
    entries: list[LanguageEntry] = []
    for key, text in pattern.findall(content):
        entries.append(LanguageEntry(key=key.strip(), text=text.strip()))
    return entries
