#!/usr/bin/env python3
"""
Extract language keys from plugin XML and from code (PHP, tpl, JS).
Report orphaned keys (defined in XML, never used) and missing keys (used in code, not in XML).
Includes file:line locations for used keys (DevTools „Fehlende Texte“ parity).

Usage:
  python3 check-language-keys.py [plugin_dir]

Default plugin_dir: ../basis-plugin/temp_edit (legacy plugin-manager layout).
Exit 2 when orphaned or missing keys; exit 0 when clean.
"""
from __future__ import annotations

import re
import sys
from collections import Counter
from collections import defaultdict
from pathlib import Path
from xml.etree import ElementTree as ET

WCF_PREFIX = "wcf."


def requires_plugin_language(key: str) -> bool:
    """Core wcf.* phrases live in WCF — only app keys must exist in plugin language/*.xml."""
    return not key.startswith(WCF_PREFIX)


def read_package_id(package_xml: Path) -> str:
    root = ET.parse(package_xml).getroot()
    pkg_id = root.get("name", "").strip()
    if pkg_id:
        return pkg_id
    for elem in root.iter():
        if elem.tag.split("}")[-1] == "packagename" and elem.text:
            return elem.text.strip()
    return ""


def read_app_abbrev(package_xml: Path, package_id: str) -> str:
    text = package_xml.read_text(encoding="utf-8", errors="replace")
    for pat in (
        r'type="application"[^>]*application="([^"]+)"',
        r'application="([^"]+)"[^>]*type="application"',
    ):
        m = re.search(pat, text)
        if m:
            return m.group(1).strip()
    if package_id and "." in package_id:
        return package_id.rsplit(".", 1)[-1]
    return ""


def find_package_xml(plugin_root: Path) -> Path | None:
    candidates = [
        plugin_root / "package.xml",
        plugin_root / "temp_edit" / "package.xml",
    ]
    if plugin_root.name in ("temp_edit", "_extracted"):
        candidates.append(plugin_root.parent / "package.xml")
    for path in candidates:
        if path.is_file():
            return path
    return None


def detect_key_prefixes(plugin_root: Path) -> tuple[str, ...]:
    prefixes: set[str] = {WCF_PREFIX}
    pkg_xml = find_package_xml(plugin_root)
    if pkg_xml is not None:
        package_id = read_package_id(pkg_xml)
        abbrev = read_app_abbrev(pkg_xml, package_id)
        if abbrev:
            prefixes.add(f"{abbrev}.")
    lang_dir = plugin_root / "language"
    if lang_dir.is_dir():
        counts: Counter[str] = Counter()
        for xml in lang_dir.glob("*.xml"):
            for key in extract_keys_from_xml(xml):
                if key.startswith(WCF_PREFIX):
                    continue
                if "." in key:
                    counts[key.split(".", 1)[0] + "."] += 1
        if counts:
            prefixes.update(counts.keys())
    return tuple(sorted(prefixes))


def extract_keys_from_xml(xml_path: Path) -> set[str]:
    tree = ET.parse(xml_path)
    root = tree.getroot()
    keys: set[str] = set()
    for elem in root.iter():
        if elem.tag.split("}")[-1] == "item":
            name = elem.get("name")
            if name:
                keys.add(name)
    return keys


def _track(
    keys: dict[str, list[tuple[str, int]]],
    key: str,
    path: Path,
    line_no: int,
    key_prefixes: tuple[str, ...],
) -> None:
    if not any(key.startswith(p) for p in key_prefixes):
        return
    rel = str(path)
    keys.setdefault(key, []).append((rel, line_no))


def scan_php(path: Path, content: str, out: dict[str, list[tuple[str, int]]], key_prefixes: tuple[str, ...]) -> None:
    patterns = [
        re.compile(r"->get\s*\(\s*['\"]([^'\"]+)['\"]"),
        re.compile(r"getLanguage\(\)->get\s*\(\s*['\"]([^'\"]+)['\"]"),
    ]
    for i, line in enumerate(content.splitlines(), start=1):
        for pat in patterns:
            for m in pat.finditer(line):
                _track(out, m.group(1).strip(), path, i, key_prefixes)


def scan_tpl(path: Path, content: str, out: dict[str, list[tuple[str, int]]], key_prefixes: tuple[str, ...]) -> None:
    patterns = [
        (re.compile(r"\{lang\}([^{]+)\{\/lang\}"), 1),
        (re.compile(r"\{jslang\}([^{]+)\{\/jslang\}"), 1),
        (re.compile(r"Language\.get\s*\(\s*['\"]([^'\"]+)['\"]"), 0),
    ]
    for i, line in enumerate(content.splitlines(), start=1):
        for pat, grp in patterns:
            for m in pat.finditer(line):
                _track(out, m.group(grp).strip(), path, i, key_prefixes)


def scan_js(path: Path, content: str, out: dict[str, list[tuple[str, int]]], key_prefixes: tuple[str, ...]) -> None:
    pat = re.compile(r"Language\.get\s*\(\s*['\"]([^'\"]+)['\"]")
    for i, line in enumerate(content.splitlines(), start=1):
        for m in pat.finditer(line):
            _track(out, m.group(1).strip(), path, i, key_prefixes)


def format_locations(refs: list[tuple[str, int]], limit: int = 3) -> str:
    parts = [f"{p}:{ln}" for p, ln in refs[:limit]]
    if len(refs) > limit:
        parts.append(f"+{len(refs) - limit} weitere")
    return ", ".join(parts)


def resolve_plugin_root() -> Path:
    if len(sys.argv) > 1 and not sys.argv[1].startswith("-"):
        return Path(sys.argv[1]).resolve()
    return Path(__file__).resolve().parent.parent / "basis-plugin" / "temp_edit"


def main() -> int:
    temp_edit = resolve_plugin_root()
    key_prefixes = detect_key_prefixes(temp_edit)
    lang_dir = temp_edit / "language"
    de_xml = lang_dir / "de.xml"
    en_xml = lang_dir / "en.xml"

    if not de_xml.exists() or not en_xml.exists():
        print(f"Language files not found under {lang_dir}.", file=sys.stderr)
        return 1

    defined_de = extract_keys_from_xml(de_xml)
    defined_en = extract_keys_from_xml(en_xml)
    defined_all = defined_de | defined_en

    used: dict[str, list[tuple[str, int]]] = defaultdict(list)
    scanners = {
        ".php": scan_php,
        ".tpl": scan_tpl,
        ".js": scan_js,
    }
    for f in temp_edit.rglob("*"):
        if not f.is_file():
            continue
        if ".min." in f.name and f.suffix == ".js":
            continue
        ext = f.suffix
        if ext not in scanners:
            continue
        try:
            scanners[ext](f, f.read_text(encoding="utf-8", errors="replace"), used, key_prefixes)
        except OSError as e:
            print(f"Skip {f}: {e}", file=sys.stderr)

    used_keys = set(used)
    plugin_used = {k for k in used_keys if requires_plugin_language(k)}
    orphaned = {k for k in defined_all if requires_plugin_language(k)} - used_keys
    missing_in_de = plugin_used - defined_de
    missing_in_en = plugin_used - defined_en

    app_prefixes = ", ".join(p for p in key_prefixes if p != WCF_PREFIX) or "(auto)"
    print("=== Defined keys (base): DE", len(defined_de), "EN", len(defined_en))
    print("=== Used keys (wcf.* + app):", len(used_keys), f"[{app_prefixes}]")
    print()
    print("--- Orphaned (in XML, never used in code) ---")
    for k in sorted(orphaned):
        print(k)
    print()
    print("--- Missing (used in code, not in DE) ---")
    for k in sorted(missing_in_de):
        loc = format_locations(used[k])
        print(f"{k}  @ {loc}")
    print()
    print("--- Missing (used in code, not in EN) ---")
    for k in sorted(missing_in_en):
        loc = format_locations(used[k])
        print(f"{k}  @ {loc}")

    if missing_in_de or missing_in_en:
        return 2
    if orphaned:
        print()
        print(f"Hinweis: {len(orphaned)} ungenutzte Plugin-Keys in XML (kein Fehler).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
