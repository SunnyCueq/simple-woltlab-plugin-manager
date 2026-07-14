#!/usr/bin/env python3
"""Detect invalid WoltLab foreach loop variables in .tpl files.

WoltLab's template compiler stores named foreach state under $tpl['foreach'][name],
not as a top-level $nameLoop array. Patterns copied from vanilla Smarty, e.g.

    {foreach from=$items item=code name=codeLoop}
        … {if !$codeLoop.last}, …
    {/foreach}

compile but fail at runtime: Undefined array key "codeLoop".

For comma-separated JavaScript array literals, use {implode} (see WoltLab docs:
template-plugins.md, plugin {implode}) — same as core shared_itemListFormField.tpl.

Output: <tpl-file>:<line>:<problem>
Exit code 0; findings on stdout.
"""

import re
import sys
from pathlib import Path

LOOP_VAR = re.compile(
    r"(?<!\$tpl\.)(?<!\$smarty\.foreach\.)\$([a-zA-Z_][a-zA-Z0-9_]*Loop)\."
    r"(last|first|iteration|total)\b"
)


def strip_comments(text: str) -> str:
    return re.sub(r"\{[*][^*]*[*](?:[^*]|\*+[^*/])*\*+\}", "", text, flags=re.DOTALL)


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".")
    issues = 0

    for tpl in sorted(root.glob("**/*.tpl")):
        if "node_modules" in tpl.parts or "woltlab-github" in tpl.parts:
            continue

        raw = tpl.read_text(encoding="utf-8", errors="replace")
        content = strip_comments(raw)

        for lineno, line in enumerate(content.splitlines(), start=1):
            for m in LOOP_VAR.finditer(line):
                print(
                    f"{tpl}:{lineno}:Ungültige Foreach-Loop-Variable "
                    f"'${m.group(1)}.{m.group(2)}' — in WoltLab "
                    f"{{implode}} verwenden oder $tpl.foreach.* nutzen"
                )
                issues += 1

    return 0 if issues == 0 else 0  # findings via stdout; exit 0 for pipe compatibility


if __name__ == "__main__":
    raise SystemExit(main())
