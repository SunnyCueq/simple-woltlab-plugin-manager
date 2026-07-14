#!/usr/bin/env python3
"""Prüft Templates auf ungültige/veraltete Hinweis-Boxen.

Hintergrund (Shr1nkr-Befund 2026-07-02):
- <woltlab-core-notice type="danger"> — gültig sind nur error|info|success|warning
  (WoltlabCoreNoticeElementType); ungültige Typen rendern ohne Farbe/Icon.
- Legacy-Markup <p class="info">…</p> statt <woltlab-core-notice> — funktioniert,
  ist aber inkonsistent zum WoltLab-6.x-Standard und fällt im Store-Review auf.
- Legacy-Alert-Klasse auf <woltlab-core-notice class="info"> — redundant, die
  Komponente setzt die Typ-Klasse selbst aus dem type-Attribut (Laufzeit).

Ausgabe: <tpl-datei>:<zeile>:<problem>
Exit-Code 0, Funde über stdout.
"""

import re
import sys
from pathlib import Path

VALID_TYPES = {"error", "info", "success", "warning"}
NOTICE_TYPE = re.compile(r"<woltlab-core-notice[^>]*\btype=\"([^\"]*)\"")
NOTICE_CLASS = re.compile(r"<woltlab-core-notice[^>]*\bclass=\"([^\"]*)\"")
LEGACY_NOTICE = re.compile(r"<p\s+class=\"(info|warning|success|error)\"")


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".")

    for tpl in root.glob("**/*.tpl"):
        if "node_modules" in tpl.parts:
            continue
        for lineno, line in enumerate(
            tpl.read_text(encoding="utf-8", errors="replace").splitlines(), start=1
        ):
            for m in NOTICE_TYPE.finditer(line):
                if m.group(1) not in VALID_TYPES:
                    print(
                        f"{tpl}:{lineno}:Ungültiger notice-Typ '{m.group(1)}' "
                        f"— erlaubt sind {', '.join(sorted(VALID_TYPES))}"
                    )
            for m in NOTICE_CLASS.finditer(line):
                for cls in m.group(1).split():
                    if cls in VALID_TYPES:
                        print(
                            f"{tpl}:{lineno}:Redundante Legacy-Klasse '{cls}' auf "
                            f"<woltlab-core-notice> — type-Attribut genügt"
                        )
            for m in LEGACY_NOTICE.finditer(line):
                print(
                    f"{tpl}:{lineno}:Legacy-Notice <p class=\"{m.group(1)}\"> "
                    f"— <woltlab-core-notice type=\"{m.group(1)}\"> verwenden"
                )

    return 0


if __name__ == "__main__":
    sys.exit(main())
