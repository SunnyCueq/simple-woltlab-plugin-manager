#!/usr/bin/env python3
"""Fail-Hinweise für doppelte geschweifte Klammern in WoltLab-Templates.

Hintergrund (DIS / sunnyc.de 2026-07-19):
- TemplateScriptingCompiler behandelt „{{…“ als Tag-Start.
- JSDoc in eingebettetem JS wie ``/** @type {{file: File}} */`` bricht die
  Kompilierung mit „unknown tag {{file: …“ — oft erst beim ersten ACP-Aufruf.
- Auch Vue-/Mustache-artige ``{{ var }}`` sind in .tpl unzulässig.

Ausgabe: <tpl-datei>:<zeile>:<problem>
Exit-Code immer 0 (Funde über stdout, wie die anderen check-template-*.py).
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

# Mindestens zwei öffnende Klammern hintereinander (ggf. mit Whitespace)
DOUBLE_OPEN = re.compile(r"\{\s*\{")


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".")
    for tpl in sorted(root.glob("**/*.tpl")):
        if "node_modules" in tpl.parts or "compiled" in tpl.parts:
            continue
        try:
            text = tpl.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        for lineno, line in enumerate(text.splitlines(), start=1):
            if DOUBLE_OPEN.search(line):
                print(
                    f"{tpl}:{lineno}:Doppelte geschweifte Klammer "
                    "{{ — WoltLab kompiliert das als Template-Tag "
                    "(kein JSDoc @type {{…}}, kein Mustache/Vue in .tpl)"
                )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
