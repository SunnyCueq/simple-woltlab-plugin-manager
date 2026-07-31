#!/usr/bin/env python3
"""Tests for check-language-pip-keys.py (<delete> ignored)."""
from __future__ import annotations

import importlib.util
import tempfile
import unittest
from pathlib import Path

TOOLS = Path(__file__).resolve().parents[1]


def _load():
    path = TOOLS / "check-language-pip-keys.py"
    spec = importlib.util.spec_from_file_location("check_language_pip_keys", path)
    assert spec and spec.loader
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


mod = _load()


class LanguagePipKeysTests(unittest.TestCase):
    def test_delete_options_not_required(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            (root / "option.xml").write_text(
                """<?xml version="1.0"?>
<options>
	<import>
		<categories>
			<category name="wsc_demo"/>
		</categories>
		<options>
			<option name="wsc_demo_alive">
				<optiontype>boolean</optiontype>
			</option>
		</options>
	</import>
	<delete>
		<category name="wsc_demo.mail.storage"/>
		<option name="wsc_demo_promo_enabled"/>
	</delete>
</options>
""",
                encoding="utf-8",
            )
            keys = {k for _, k, _ in mod.required_keys(root)}
            self.assertIn("wcf.acp.option.category.wsc_demo", keys)
            self.assertIn("wcf.acp.option.wsc_demo_alive", keys)
            self.assertNotIn("wcf.acp.option.category.wsc_demo.mail.storage", keys)
            self.assertNotIn("wcf.acp.option.wsc_demo_promo_enabled", keys)

    def test_active_option_still_required(self) -> None:
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            (root / "option.xml").write_text(
                """<?xml version="1.0"?>
<options>
	<import>
		<options>
			<option name="wsc_demo_secret_key">
				<optiontype>text</optiontype>
			</option>
		</options>
	</import>
</options>
""",
                encoding="utf-8",
            )
            keys = {k for _, k, _ in mod.required_keys(root)}
            self.assertEqual(keys, {"wcf.acp.option.wsc_demo_secret_key"})


if __name__ == "__main__":
    unittest.main()
