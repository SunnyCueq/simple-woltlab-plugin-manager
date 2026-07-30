#!/usr/bin/env python3
"""Unit tests for check-package-pip-archives.py (stdlib unittest)."""
from __future__ import annotations

import importlib.util
import sys
import tempfile
import unittest
from pathlib import Path

TOOLS = Path(__file__).resolve().parents[1]
HYPHEN = TOOLS / "check-package-pip-archives.py"


def _load_mod():
    spec = importlib.util.spec_from_file_location("check_package_pip_archives", HYPHEN)
    assert spec and spec.loader
    mod = importlib.util.module_from_spec(spec)
    sys.modules["check_package_pip_archives"] = mod
    spec.loader.exec_module(mod)
    return mod


mod = _load_mod()


def _write_pkg(dir_path: Path, instructions: str) -> Path:
    pkg = dir_path / "package.xml"
    pkg.write_text(
        f"""<?xml version="1.0" encoding="UTF-8"?>
<package name="com.vendor.test" xmlns="http://www.woltlab.com">
  <packageinformation>
    <version>1.0.0</version>
  </packageinformation>
  <instructions type="install">
    {instructions}
  </instructions>
</package>
""",
        encoding="utf-8",
    )
    return pkg


class ExpectedArchivesTests(unittest.TestCase):
    def test_empty_body_defaults(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            pkg = _write_pkg(
                root,
                '<instruction type="file" />\n'
                '<instruction type="template" />\n'
                '<instruction type="acpTemplate" />',
            )
            self.assertEqual(
                mod.expected_archives(pkg),
                {"files.tar", "templates.tar", "acptemplates.tar"},
            )

    def test_explicit_body_and_wcf_files(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            pkg = _write_pkg(
                root,
                '<instruction type="file">custom-files.tar</instruction>\n'
                '<instruction type="file" application="wcf" />',
            )
            self.assertEqual(
                mod.expected_archives(pkg),
                {"custom-files.tar", "files_wcf.tar"},
            )

    def test_list_root_archives_ignores_product_tarball(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            (root / "files.tar").write_bytes(b"x")
            (root / "com.vendor.test_v1.0.0.tar.gz").write_bytes(b"y")
            (root / "style.tar.gz").write_bytes(b"z")
            self.assertEqual(
                mod.list_root_archives(root),
                {"files.tar", "style.tar.gz"},
            )


if __name__ == "__main__":
    unittest.main()
