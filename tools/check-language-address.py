#!/usr/bin/env python3
"""Heuristic: German Sie vs. Du consistency in language/de.xml (and de_*.xml).

WoltLab supports both forms in one string via:
  {if LANGUAGE_USE_INFORMAL_VARIANT}du{else}Sie{/if}
Those items are ignored.

Warns when the file clearly mixes exclusive formal and exclusive informal
pronouns across different items (tone inconsistency), or when a single item
contains both without the WoltLab if/else pattern.

Exit 0 always (warn-level; findings on stdout). Empty stdout = clean.
"""

from __future__ import annotations

import re
import sys
from pathlib import Path
from xml.etree import ElementTree as ET

# Skip intentional WoltLab formal/informal branching
VARIANT_MARKER = "LANGUAGE_USE_INFORMAL_VARIANT"

# Capitalized formal address (UI German)
FORMAL = re.compile(
    r"\b("
    r"Sie|Ihnen|"
    r"Ihr|Ihre|Ihren|Ihrem|Ihrer|Ihres"
    r")\b"
)

# Informal address (allow leading capital at sentence start)
INFORMAL = re.compile(
    r"\b("
    r"[Dd]u|[Dd]ir|[Dd]ich|"
    r"[Dd]ein|[Dd]eine|[Dd]einen|[Dd]einem|[Dd]einer|[Dd]eines|"
    r"[Ee]uch|[Ee]uer|[Ee]ure|[Ee]uren|[Ee]urem|[Ee]urer"
    r")\b"
)

# Avoid counting "Sie" inside common false friends is hard; keep thresholds high enough.
MIN_SIDE = 3  # need at least this many exclusive items per side to warn about mix
MAX_EXAMPLES = 5


def local(tag: str) -> str:
    return tag.split("}")[-1] if "}" in tag else tag


def item_texts(path: Path) -> list[tuple[str, str]]:
    """Return list of (item_name, plain_text)."""
    out: list[tuple[str, str]] = []
    try:
        tree = ET.parse(path)
    except ET.ParseError:
        return out
    for elem in tree.iter():
        if local(elem.tag) != "item":
            continue
        name = (elem.get("name") or "").strip() or "?"
        text = "".join(elem.itertext()).strip()
        if text:
            out.append((name, text))
    return out


def score(text: str) -> tuple[int, int]:
    if VARIANT_MARKER in text:
        return 0, 0
    return len(FORMAL.findall(text)), len(INFORMAL.findall(text))


def check_file(path: Path) -> list[str]:
    findings: list[str] = []
    formal_only: list[str] = []
    informal_only: list[str] = []
    mixed_in_item: list[str] = []

    for name, text in item_texts(path):
        f, i = score(text)
        if f and i:
            mixed_in_item.append(name)
        elif f and not i:
            formal_only.append(name)
        elif i and not f:
            informal_only.append(name)

    rel = path.name
    for name in mixed_in_item[:MAX_EXAMPLES]:
        findings.append(
            f"{rel}:1:Key '{name}' mischt Sie- und Du-Formen ohne "
            f"{{if LANGUAGE_USE_INFORMAL_VARIANT}}…{{else}}…{{/if}}"
        )
    if len(mixed_in_item) > MAX_EXAMPLES:
        findings.append(
            f"{rel}:1:… und {len(mixed_in_item) - MAX_EXAMPLES} weitere Keys mit "
            "Sie+Du im selben Text (ohne Varianten-IF)"
        )

    if len(formal_only) >= MIN_SIDE and len(informal_only) >= MIN_SIDE:
        findings.append(
            f"{rel}:1:Anrede inkonsistent: ~{len(formal_only)} Keys eher »Sie«, "
            f"~{len(informal_only)} Keys eher »Du« — Ton vereinheitlichen oder "
            "LANGUAGE_USE_INFORMAL_VARIANT im Wert nutzen"
        )
        ex_f = ", ".join(formal_only[:3])
        ex_i = ", ".join(informal_only[:3])
        findings.append(f"{rel}:1:Beispiele Sie: {ex_f}")
        findings.append(f"{rel}:1:Beispiele Du: {ex_i}")

    return findings


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".").resolve()
    lang = root / "language"
    if not lang.is_dir():
        # also accept plugin root with temp_edit/language
        alt = root / "temp_edit" / "language"
        lang = alt if alt.is_dir() else lang
    if not lang.is_dir():
        return 0

    files = sorted(lang.glob("de.xml")) + sorted(lang.glob("de_*.xml"))
    # Some packages use only language/de.xml; skip non-German
    issues: list[str] = []
    for path in files:
        issues.extend(check_file(path))

    for line in issues:
        print(line)
    return 0


if __name__ == "__main__":
    sys.exit(main())
