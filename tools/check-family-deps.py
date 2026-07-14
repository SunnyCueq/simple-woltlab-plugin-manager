#!/usr/bin/env python3
"""SWPM product-line family: scan paths, build requiredpackage graph, validate, order.

Rules (fail-safe):
  1. Manifest paths = search map; optional packages[] whitelist
  2. Family = packages found under paths (after whitelist)
  3. Edges = requiredpackage to another family id (com.woltlab.* ignored for edges)
  4. Exactly one weakly connected component (N>=1)
  5. Non-WoltLab requiredpackage must exist in family
  6. No cycles; minversion of family deps must be satisfied

Top-level manifest "id" is product-line metadata — need NOT match any package.xml name.
Whitelist packages[].id MUST match package.xml @name at that root.

Usage:
  check-family-deps.py --manifest PATH [--mode check|order|list] [--json]
  check-family-deps.py --manifest PATH --mode check   # exit 0/2
"""
from __future__ import annotations

import argparse
import json
import re
import sys
import xml.etree.ElementTree as ET
from collections import defaultdict, deque
from pathlib import Path
from typing import Any


WOLTLAB_PREFIX = "com.woltlab."


def local_tag(tag: str) -> str:
    return tag.split("}")[-1] if "}" in tag else tag


def find_package_xml(root: Path) -> Path | None:
    for candidate in (
        root / "temp_edit" / "package.xml",
        root / "package.xml",
        root / "_extracted" / "package.xml",
    ):
        if candidate.is_file():
            return candidate
    return None


def parse_version_tuple(version: str) -> tuple[int, ...]:
    """Best-effort numeric prefix for minversion compare (1.4.20, 6.2.0 RC 1 → 6.2.0)."""
    version = (version or "").strip()
    m = re.match(r"^(\d+(?:\.\d+)*)", version)
    if not m:
        return (0,)
    return tuple(int(x) for x in m.group(1).split("."))


def version_gte(have: str, need: str) -> bool:
    a = parse_version_tuple(have)
    b = parse_version_tuple(need)
    # pad
    n = max(len(a), len(b))
    a = a + (0,) * (n - len(a))
    b = b + (0,) * (n - len(b))
    return a >= b


def read_package_meta(xml_path: Path) -> dict[str, Any]:
    tree = ET.parse(xml_path)
    root = tree.getroot()
    pkg_id = root.attrib.get("name", "").strip()
    version = ""
    requires: list[tuple[str, str]] = []  # (id, minversion)

    for child in root:
        tag = local_tag(child.tag)
        if tag == "packageinformation":
            for sub in child:
                if local_tag(sub.tag) == "version" and sub.text:
                    version = sub.text.strip()
        elif tag == "requiredpackages":
            for req in child:
                if local_tag(req.tag) != "requiredpackage":
                    continue
                rid = (req.text or "").strip()
                if not rid:
                    continue
                minv = req.attrib.get("minversion", "").strip()
                requires.append((rid, minv))

    return {"id": pkg_id, "version": version, "requires": requires, "xml": str(xml_path)}


def package_root_from_xml(xml_path: Path) -> Path:
    """Map package.xml location to plugin root (temp_edit/ → parent)."""
    xml_path = xml_path.resolve()
    if xml_path.parent.name == "temp_edit":
        return xml_path.parent.parent
    return xml_path.parent


def discover_under(path: Path, max_depth: int = 3) -> list[Path]:
    """Return unique plugin roots under path (dirs with package.xml / temp_edit/package.xml)."""
    path = path.resolve()
    found: dict[str, Path] = {}
    if not path.is_dir():
        return []

    def consider_xml(xml: Path) -> None:
        root = package_root_from_xml(xml)
        try:
            rel = root.relative_to(path)
            depth = len(rel.parts)
        except ValueError:
            # xml outside path (shouldn't happen)
            if root == path:
                depth = 0
            else:
                return
        # path itself is depth 0; children depth 1..max_depth
        if root == path or depth <= max_depth:
            found[str(root)] = root

    # Direct package at path
    direct = find_package_xml(path)
    if direct:
        consider_xml(direct)

    # Nested package.xml files
    # Skip demo/example trees so a core app path does not pull in sample add-ons
    skip_names = {
        ".git",
        "node_modules",
        "vendor",
        "examples",
        "example",
        "fixtures",
        "fixture",
        "_extracted",
    }
    for xml in path.rglob("package.xml"):
        if any(part in skip_names for part in xml.parts):
            continue
        # Prefer temp_edit/package.xml over sibling root package.xml for same root
        if xml.parent.name != "temp_edit" and (xml.parent / "temp_edit" / "package.xml").is_file():
            continue
        consider_xml(xml)

    return list(found.values())


def load_manifest(manifest_path: Path) -> dict[str, Any]:
    data = json.loads(manifest_path.read_text(encoding="utf-8"))
    if not isinstance(data, dict):
        raise ValueError("Manifest must be a JSON object")
    return data


def resolve_path(base: Path, raw: str) -> Path:
    p = Path(raw)
    if p.is_absolute():
        return p.resolve()
    return (base / p).resolve()


def normalize_whitelist(packages_field: Any) -> list[dict[str, str]] | None:
    """None = no whitelist (all). Empty list = no whitelist. Non-empty = filter."""
    if packages_field is None:
        return None
    if packages_field == []:
        return None
    out: list[dict[str, str]] = []
    for item in packages_field:
        if isinstance(item, str):
            out.append({"id": item, "path": ""})
        elif isinstance(item, dict):
            pid = str(item.get("id", "")).strip()
            ppath = str(item.get("path", "")).strip()
            if not pid:
                raise ValueError(f"packages[] entry missing id: {item}")
            out.append({"id": pid, "path": ppath})
        else:
            raise ValueError(f"Invalid packages[] entry: {item}")
    return out


def build_family(manifest_path: Path) -> tuple[dict[str, Any], list[str]]:
    """Return (result_dict, errors). result has packages ordered if ok."""
    errors: list[str] = []
    warnings: list[str] = []
    manifest = load_manifest(manifest_path)
    manifest_dir = manifest_path.parent.resolve()
    line_id = str(manifest.get("id", "")).strip() or "(unnamed-line)"

    paths_raw = manifest.get("paths")
    if not paths_raw or not isinstance(paths_raw, list):
        return {}, [f"Manifest fehlt 'paths' (Liste): {manifest_path}"]

    whitelist = normalize_whitelist(manifest.get("packages"))

    # Discover
    roots: list[Path] = []
    for raw in paths_raw:
        resolved = resolve_path(manifest_dir, str(raw))
        if not resolved.exists():
            errors.append(f"path existiert nicht: {raw} → {resolved}")
            continue
        roots.extend(discover_under(resolved))

    # Explicit whitelist paths
    if whitelist:
        for entry in whitelist:
            if entry["path"]:
                roots.append(resolve_path(manifest_dir, entry["path"]))

    # Unique roots
    uniq_roots: dict[str, Path] = {}
    for r in roots:
        if r.is_dir():
            uniq_roots[str(r.resolve())] = r.resolve()

    packages: dict[str, dict[str, Any]] = {}
    for root in uniq_roots.values():
        xml = find_package_xml(root)
        if not xml:
            continue
        try:
            meta = read_package_meta(xml)
        except ET.ParseError as exc:
            errors.append(f"package.xml parse error {xml}: {exc}")
            continue
        pid = meta["id"]
        if not pid:
            errors.append(f"package.xml ohne name-Attribut: {xml}")
            continue
        if pid in packages:
            errors.append(
                f"Doppelte Package-ID '{pid}': {packages[pid]['path']} und {root}"
            )
            continue
        packages[pid] = {
            "id": pid,
            "path": str(root),
            "version": meta["version"],
            "requires": meta["requires"],
            "xml": meta["xml"],
        }

    # Whitelist filter + id match
    if whitelist is not None:
        wanted = {e["id"]: e for e in whitelist}
        for wid, entry in wanted.items():
            if wid not in packages:
                # try path-only entry
                if entry["path"]:
                    root = resolve_path(manifest_dir, entry["path"])
                    xml = find_package_xml(root)
                    if xml:
                        meta = read_package_meta(xml)
                        if meta["id"] != wid:
                            errors.append(
                                f"Whitelist packages[].id '{wid}' ≠ package.xml name "
                                f"'{meta['id']}' at {root}"
                            )
                        else:
                            packages[wid] = {
                                "id": wid,
                                "path": str(root.resolve()),
                                "version": meta["version"],
                                "requires": meta["requires"],
                                "xml": meta["xml"],
                            }
                    else:
                        errors.append(f"Whitelist-Paket nicht gefunden: {wid}")
                else:
                    errors.append(f"Whitelist-Paket nicht unter paths gefunden: {wid}")
        # keep only whitelist ids
        packages = {k: v for k, v in packages.items() if k in wanted}
        for wid, entry in wanted.items():
            if wid in packages and entry["path"]:
                expected = resolve_path(manifest_dir, entry["path"])
                actual = Path(packages[wid]["path"]).resolve()
                if actual != expected.resolve():
                    errors.append(
                        f"Whitelist packages[].path für '{wid}' zeigt auf {expected}, "
                        f"gefunden unter {actual}"
                    )

    if errors:
        return {
            "ok": False,
            "line_id": line_id,
            "packages": [],
            "errors": errors,
            "warnings": warnings,
        }, errors

    if not packages:
        errors.append("Keine Pakete unter paths gefunden")
        return {
            "ok": False,
            "line_id": line_id,
            "packages": [],
            "errors": errors,
            "warnings": warnings,
        }, errors

    ids = set(packages.keys())

    # Family edges (ignore woltlab for connectivity)
    undirected: dict[str, set[str]] = defaultdict(set)
    directed: dict[str, set[str]] = defaultdict(set)  # pkg -> depends on
    missing_deps: list[str] = []

    for pid, pkg in packages.items():
        for req_id, minv in pkg["requires"]:
            if req_id.startswith(WOLTLAB_PREFIX):
                continue
            if req_id not in ids:
                missing_deps.append(
                    f"{pid} requires '{req_id}' (minversion={minv or '-'}), "
                    f"aber nicht in der Familie"
                )
                continue
            undirected[pid].add(req_id)
            undirected[req_id].add(pid)
            directed[pid].add(req_id)

    if missing_deps:
        errors.extend(missing_deps)

    # Weakly connected components
    seen: set[str] = set()
    components: list[list[str]] = []
    for start in sorted(ids):
        if start in seen:
            continue
        comp: list[str] = []
        q = deque([start])
        seen.add(start)
        while q:
            n = q.popleft()
            comp.append(n)
            for nb in undirected[n]:
                if nb not in seen:
                    seen.add(nb)
                    q.append(nb)
            # isolated nodes still form a component of 1
        components.append(sorted(comp))

    # Isolated packages never added to undirected — still need visit
    # (handled: start from every id)

    if len(components) != 1:
        errors.append(
            f"Genau eine zusammenhängende Produktlinie erwartet, gefunden: "
            f"{len(components)} Insel(n): {components}. "
            f"paths enger setzen oder packages[] Whitelist nutzen. "
            f"(Anti-Pattern: paths: [\"..\"])"
        )

    # Cycles (DFS on directed)
    WHITE, GRAY, BLACK = 0, 1, 2
    color = {i: WHITE for i in ids}
    cycle_found = False

    def dfs(u: str, stack: list[str]) -> None:
        nonlocal cycle_found
        color[u] = GRAY
        stack.append(u)
        for v in directed[u]:
            if color[v] == GRAY:
                cycle_found = True
                errors.append(f"Zyklus in requiredpackage: {' → '.join(stack + [v])}")
                return
            if color[v] == WHITE:
                dfs(v, stack)
                if cycle_found:
                    return
        stack.pop()
        color[u] = BLACK

    for i in sorted(ids):
        if color[i] == WHITE:
            dfs(i, [])
            if cycle_found:
                break

    # minversion for family deps
    for pid, pkg in packages.items():
        for req_id, minv in pkg["requires"]:
            if not minv or req_id not in packages:
                continue
            if req_id.startswith(WOLTLAB_PREFIX):
                continue
            have = packages[req_id]["version"]
            if not have:
                errors.append(
                    f"{pid} braucht {req_id} minversion {minv}, "
                    f"aber {req_id} hat keine <version> in package.xml"
                )
            elif not version_gte(have, minv):
                errors.append(
                    f"{pid} braucht {req_id} minversion {minv}, "
                    f"Familie hat {have}"
                )

    # Multi-root note
    roots_dir = [i for i in ids if not directed[i]]
    if len(roots_dir) > 1 and len(components) == 1:
        warnings.append(
            f"Mehrere Basen in einer Komponente (Topo-Roots): {sorted(roots_dir)} — "
            f"erlaubt aber unüblich; paths/Whitelist eng halten."
        )

    # Topological order (Kahn): build dependencies first
    indeg = {i: 0 for i in ids}
    # edge A→B means A depends on B → B must come before A
    # Kahn: process nodes with no outgoing deps first... 
    # Actually: for each edge pkg→dep, dep must be before pkg.
    # indegree[pkg] = number of deps still needed
    for pkg, deps in directed.items():
        indeg[pkg] = len(deps)

    # Wait: Kahn usually uses edges dep→pkg (dep must be done). Rebuild:
    dependents: dict[str, set[str]] = defaultdict(set)
    indeg2 = {i: 0 for i in ids}
    for pkg, deps in directed.items():
        for dep in deps:
            dependents[dep].add(pkg)
            indeg2[pkg] += 1

    order: list[str] = []
    ready = sorted([i for i in ids if indeg2[i] == 0])
    while ready:
        n = ready.pop(0)
        order.append(n)
        newly: list[str] = []
        for child in sorted(dependents[n]):
            indeg2[child] -= 1
            if indeg2[child] == 0:
                newly.append(child)
        ready = sorted(ready + newly)

    if len(order) != len(ids) and not cycle_found:
        errors.append("Topo-Sort unvollständig (interner Fehler oder Zyklus)")

    ordered_pkgs = [packages[i] for i in order if i in packages]

    ok = len(errors) == 0
    result = {
        "ok": ok,
        "line_id": line_id,
        "versionStrategy": manifest.get("versionStrategy", "independent"),
        "packages": ordered_pkgs,
        "components": components,
        "errors": errors,
        "warnings": warnings,
    }
    return result, errors


def discover_workspace(main_dir: Path) -> list[Path]:
    """Top-level plugin roots under SWPM workspace (shared discovery for menu/build)."""
    main_dir = main_dir.resolve()
    skip = {
        "woltlab-github",
        "woltlab-docs",
        "woltlab-core",
        "woltlab-d-ts",
        "tools",
        "maintainer",
        "docs",
        ".git",
        ".audit",
        "node_modules",
    }
    # Priority order (same as legacy common.sh)
    priority = ("basis-plugin", "mein-plugin", "plugins-integrieren")
    found: dict[str, Path] = {}
    ordered: list[Path] = []

    def add_root(root: Path) -> None:
        key = str(root.resolve())
        if key not in found:
            found[key] = root.resolve()
            ordered.append(root.resolve())

    for name in priority:
        child = main_dir / name
        if child.is_dir() and find_package_xml(child):
            add_root(child)
            # Nested under priority dirs (max depth 3), still skip examples/fixtures
            for nested in discover_under(child, max_depth=3):
                if nested != child.resolve():
                    add_root(nested)

    for child in sorted(main_dir.iterdir()):
        if not child.is_dir() or child.name in skip or child.name.startswith("."):
            continue
        if find_package_xml(child):
            add_root(child)

    return ordered


def main() -> int:
    parser = argparse.ArgumentParser(description="SWPM family dependency check / order / scan")
    parser.add_argument("--manifest", type=Path, default=None)
    parser.add_argument(
        "--mode",
        choices=("check", "order", "list"),
        default="check",
    )
    parser.add_argument("--json", action="store_true")
    parser.add_argument(
        "--scan-workspace",
        type=Path,
        metavar="DIR",
        help="List plugin roots under workspace (one path per line); no manifest needed",
    )
    parser.add_argument(
        "--scan-dir",
        type=Path,
        metavar="DIR",
        help="List package roots under DIR (discover_under); no manifest needed",
    )
    args = parser.parse_args()

    if args.scan_workspace is not None:
        root = args.scan_workspace.resolve()
        if not root.is_dir():
            print(f"Kein Verzeichnis: {root}", file=sys.stderr)
            return 2
        for p in discover_workspace(root):
            print(p)
        return 0

    if args.scan_dir is not None:
        root = args.scan_dir.resolve()
        if not root.is_dir():
            print(f"Kein Verzeichnis: {root}", file=sys.stderr)
            return 2
        for p in discover_under(root):
            print(p)
        return 0

    if args.manifest is None:
        parser.error("--manifest ist erforderlich (außer --scan-workspace / --scan-dir)")

    manifest = args.manifest.resolve()
    if not manifest.is_file():
        print(f"Manifest nicht gefunden: {manifest}", file=sys.stderr)
        return 2

    try:
        result, errors = build_family(manifest)
    except (json.JSONDecodeError, ValueError, OSError) as exc:
        print(f"Manifest-Fehler: {exc}", file=sys.stderr)
        return 2

    if args.json:
        json.dump(result, sys.stdout, indent=2, ensure_ascii=False)
        sys.stdout.write("\n")
        return 0 if result.get("ok") else 2

    if args.mode == "list":
        print(f"Produktlinie: {result.get('line_id', '?')}")
        for pkg in result.get("packages") or []:
            reqs = [
                r[0]
                for r in pkg.get("requires", [])
                if not r[0].startswith(WOLTLAB_PREFIX)
            ]
            print(f"  {pkg['id']}\t{pkg['path']}\trequires={','.join(reqs) or '-'}")
        for w in result.get("warnings") or []:
            print(f"WARN: {w}", file=sys.stderr)
        for e in errors:
            print(f"ERR: {e}", file=sys.stderr)
        return 0 if result.get("ok") else 2

    if args.mode == "order":
        for pkg in result.get("packages") or []:
            # TSV: id \t path \t version
            print(f"{pkg['id']}\t{pkg['path']}\t{pkg.get('version', '')}")
        for w in result.get("warnings") or []:
            print(f"WARN: {w}", file=sys.stderr)
        for e in errors:
            print(f"ERR: {e}", file=sys.stderr)
        return 0 if result.get("ok") else 2

    # check
    for w in result.get("warnings") or []:
        print(f"WARN: {w}", file=sys.stderr)
    if errors:
        for e in errors:
            print(f"ERR: {e}", file=sys.stderr)
        print(f"Family-Check FEHLGESCHLAGEN ({len(errors)} Fehler)", file=sys.stderr)
        return 2
    n = len(result.get("packages") or [])
    print(f"OK: Produktlinie '{result.get('line_id')}' — {n} Paket(e), Graph gültig")
    return 0


if __name__ == "__main__":
    sys.exit(main())
