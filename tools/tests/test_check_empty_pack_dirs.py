#!/usr/bin/env python3
"""Tests for check-empty-pack-dirs.py."""
from __future__ import annotations

import importlib.util
import sys
import tempfile
import unittest
from pathlib import Path

TOOLS = Path(__file__).resolve().parents[1]


def _load():
    path = TOOLS / "check-empty-pack-dirs.py"
    spec = importlib.util.spec_from_file_location("check_empty_pack_dirs", path)
    assert spec and spec.loader
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


mod = _load()


class EmptyPackDirsTests(unittest.TestCase):
    def test_flags_empty_files_acp(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            (root / "files" / "lib" / "acp" / "page").mkdir(parents=True)
            (root / "files" / "lib" / "acp" / "page" / "X.class.php").write_text(
                "<?php\n", encoding="utf-8"
            )
            (root / "files" / "acp").mkdir(parents=True)  # empty leftover
            empties = [mod.rel(root, p) for p in mod.empty_dirs(root / "files")]
            self.assertIn("files/acp", empties)

    def test_clean_tree_ok(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            (root / "files" / "acp" / "style").mkdir(parents=True)
            (root / "files" / "acp" / "style" / "x.css").write_text("a{}", encoding="utf-8")
            self.assertEqual(mod.empty_dirs(root / "files"), [])


if __name__ == "__main__":
    unittest.main()
