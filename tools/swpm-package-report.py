#!/usr/bin/env python3
"""Machine-readable build / PIP check reports (wspackager --json parity)."""
from __future__ import annotations

import json
import sys
from pathlib import Path


def human_size(num: int) -> str:
    if num < 1024:
        return f"{num} B"
    if num < 1024 * 1024:
        return f"{num / 1024:.1f} KB"
    return f"{num / (1024 * 1024):.1f} MB"


def emit(payload: dict, ok: bool = True) -> None:
    stream = sys.stdout if ok else sys.stderr
    json.dump(payload, stream, indent=2, ensure_ascii=False)
    stream.write("\n")


def build_success(
    package_id: str,
    version: str,
    archive: Path,
    source_dir: Path,
) -> int:
    size = archive.stat().st_size if archive.is_file() else 0
    emit(
        {
            "ok": True,
            "tool": {
                "name": "simple-woltlab-plugin-manager",
                "component": "build.sh",
            },
            "source": str(source_dir.resolve()),
            "package": {"identifier": package_id, "version": version},
            "result": {
                "filename": archive.name,
                "path": str(archive.resolve()),
                "filesize": size,
                "filesize_human": human_size(size),
            },
        }
    )
    return 0


def build_error(message: str, *, package_id: str = "", version: str = "") -> int:
    payload = {
        "ok": False,
        "tool": {
            "name": "simple-woltlab-plugin-manager",
            "component": "build.sh",
        },
        "error": message,
    }
    if package_id:
        payload["package"] = {"identifier": package_id, "version": version}
    emit(payload, ok=False)
    return 1


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: swpm-package-report.py build-ok|build-err ...", file=sys.stderr)
        return 1
    cmd = sys.argv[1]
    if cmd == "build-ok" and len(sys.argv) >= 6:
        return build_success(sys.argv[2], sys.argv[3], Path(sys.argv[4]), Path(sys.argv[5]))
    if cmd == "build-err":
        msg = sys.argv[2] if len(sys.argv) > 2 else "build failed"
        pkg = sys.argv[3] if len(sys.argv) > 3 else ""
        ver = sys.argv[4] if len(sys.argv) > 4 else ""
        return build_error(msg, package_id=pkg, version=ver)
    print("Invalid arguments", file=sys.stderr)
    return 1


if __name__ == "__main__":
    sys.exit(main())
