#!/usr/bin/env python3
"""Prüft url(...)-Referenzen in ausgelieferten CSS-Dateien auf fehlende Dateien.

Hintergrund (Shr1nkr-Befund 2026-07-02): style/themes/halloween.css referenzierte
eine nicht mitgelieferte Font-Datei (@font-face → Creepster-Regular.ttf); die
Seite lieferte dadurch je nach Serverkonfiguration einen 500-Fehler.

Ausgabe: <css-datei>:<zeile>:<fehlender-pfad>
Exit-Code 0, Funde über stdout.
"""

import re
import sys
from pathlib import Path

URL_REF = re.compile(r"url\(\s*['\"]?([^'\")]+)['\"]?\s*\)")


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".")

    for css in root.glob("**/style/**/*.css"):
        if "node_modules" in css.parts:
            continue
        for lineno, line in enumerate(
            css.read_text(encoding="utf-8", errors="replace").splitlines(), start=1
        ):
            for m in URL_REF.finditer(line):
                ref = m.group(1).split("?")[0].split("#")[0].strip()
                if not ref or ref.startswith(("data:", "http://", "https://", "//")):
                    continue
                target = (css.parent / ref).resolve() if not ref.startswith("/") else None
                if target is None:
                    # absolute Pfade können nicht gegen den Quellbaum geprüft werden
                    continue
                if not target.exists():
                    print(f"{css}:{lineno}:{ref}")

    return 0


if __name__ == "__main__":
    sys.exit(main())
