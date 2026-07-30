#!/usr/bin/env python3
"""Lint package install/update PHP scripts for known ACP-fatal API mistakes.

CherryMagic 1.0.4 blew up on install with:
  Call to undefined method ACPTemplateEngine::clearTemplates()
because WCF::getTPL() in ACP is ACPTemplateEngine, which has no clearTemplates().

Correct APIs:
  TemplateEngine::deleteCompiledTemplates()
  ACPTemplateEngine::deleteCompiledACPTemplates()
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

# (regex, message)
BANNED: list[tuple[re.Pattern[str], str]] = [
    (
        re.compile(r"getTPL\s*\(\s*\)\s*->\s*clearTemplates\s*\("),
        "WCF::getTPL()->clearTemplates() — ACPTemplateEngine has no clearTemplates(); "
        "use TemplateEngine::deleteCompiledTemplates() / ACPTemplateEngine::deleteCompiledACPTemplates()",
    ),
    (
        re.compile(r"->\s*clearTemplates\s*\("),
        "clearTemplates() is not a WoltLab TemplateEngine API; "
        "use TemplateEngine::deleteCompiledTemplates()",
    ),
    (
        re.compile(r"getTPL\s*\(\s*\)\s*->\s*deleteCompiledTemplates\s*\("),
        "deleteCompiledTemplates() is static — call TemplateEngine::deleteCompiledTemplates(), not getTPL()->…",
    ),
    (
        re.compile(r"getTPL\s*\(\s*\)\s*->\s*deleteCompiledACPTemplates\s*\("),
        "deleteCompiledACPTemplates() is static — call ACPTemplateEngine::deleteCompiledACPTemplates()",
    ),
]


def script_roots(root: Path) -> list[Path]:
    dirs = [
        root / "files" / "acp",
        root / "acp",
        root / "files_wcf" / "acp",
    ]
    return [d for d in dirs if d.is_dir()]


def is_package_script(path: Path) -> bool:
    name = path.name.lower()
    return (
        name.startswith("install_")
        or name.startswith("update_")
        or name.startswith("uninstall_")
        or "postinstall" in name
        or "post_update" in name
    )


def scan(root: Path) -> list[str]:
    issues: list[str] = []
    for base in script_roots(root):
        for path in sorted(base.rglob("*.php")):
            if not is_package_script(path) and "update_" not in path.name and "install_" not in path.name:
                # still scan any *.php directly under acp/ used as package scripts
                if path.parent != base and path.parent.name not in {"database", "be.files", "style"}:
                    continue
            try:
                text = path.read_text(encoding="utf-8", errors="replace")
            except OSError as exc:
                issues.append(f"{path.relative_to(root)}: read-error: {exc}")
                continue
            for i, line in enumerate(text.splitlines(), start=1):
                stripped = line.strip()
                if stripped.startswith("//") or stripped.startswith("*") or stripped.startswith("#"):
                    continue
                for pattern, msg in BANNED:
                    if pattern.search(line):
                        rel = path.relative_to(root)
                        issues.append(f"{rel}:{i}: {msg}")
    return issues


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".").resolve()
    issues = scan(root)
    for line in issues:
        print(line)
    return 1 if issues else 0


if __name__ == "__main__":
    sys.exit(main())
