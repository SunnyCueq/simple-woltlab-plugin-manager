#!/usr/bin/env python3
"""Prüft, ob alle RPC-Endpoint-Controller (#[GetRequest]/#[PostRequest]/#[DeleteRequest])
auch registriert werden (ControllerCollecting-Event im Bootstrap).

Hintergrund (Shr1nkr-Befund 2026-07-02): Controller-Klassen unter
lib/system/endpoint/controller/ existierten, waren aber nicht im Bootstrap
registriert — jede Grid-Löschaktion endete mit `404 unknown_endpoint`.
WoltLab sammelt Endpoints ausschließlich über das ControllerCollecting-Event;
eine Klasse ohne Registrierung ist toter Code mit kaputter UI.

Ausgabe (eine Zeile pro Fund, kompatibel zu validate-plugin.sh):
    <datei>:<zeile>:<meldung>

Exit-Code 0, Funde werden über stdout gemeldet.
"""

import re
import sys
from pathlib import Path

REQUEST_ATTR = re.compile(r"#\[(Get|Post|Delete)Request\(")
CLASS_NAME = re.compile(r"^\s*(?:final\s+)?class\s+(\w+)", re.MULTILINE)


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".")

    controllers = []  # (short_name, file, line)
    for php in root.glob("**/lib/system/endpoint/controller/**/*.class.php"):
        text = php.read_text(encoding="utf-8", errors="replace")
        if not REQUEST_ATTR.search(text):
            continue
        m = CLASS_NAME.search(text)
        if not m:
            continue
        line = text[: m.start()].count("\n") + 1
        controllers.append((m.group(1), php, line))

    if not controllers:
        return 0

    bootstrap_text = ""
    for bootstrap in root.glob("**/lib/bootstrap/*.php"):
        bootstrap_text += bootstrap.read_text(encoding="utf-8", errors="replace")

    if not bootstrap_text:
        for name, php, line in controllers:
            print(f"{php}:{line}:Endpoint-Controller {name} gefunden, aber kein lib/bootstrap/*.php vorhanden")
        return 0

    for name, php, line in controllers:
        # Registrierung: `new <Name>(` oder `<Name>::class` im Bootstrap
        # (auch via Alias: `use ...\<Name> as Alias;` → Alias suchen).
        aliases = {name}
        for m in re.finditer(
            r"use\s+[\w\\]+\\" + re.escape(name) + r"\s+as\s+(\w+)\s*;", bootstrap_text
        ):
            aliases.add(m.group(1))
        registered = any(
            re.search(r"new\s+" + re.escape(a) + r"\s*\(", bootstrap_text)
            or re.search(re.escape(a) + r"::class", bootstrap_text)
            for a in aliases
        )
        if not registered:
            print(f"{php}:{line}:Endpoint-Controller {name} ist NICHT im Bootstrap registriert (ControllerCollecting) — Requests enden mit 404 unknown_endpoint")

    return 0


if __name__ == "__main__":
    sys.exit(main())
