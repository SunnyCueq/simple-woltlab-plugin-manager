#!/usr/bin/env python3
"""Prüft, ob alle RPC-Endpoint-Controller (#[GetRequest]/#[PostRequest]/#[DeleteRequest])
auch registriert werden (ControllerCollecting-Event im Bootstrap).

Hintergrund (Praxis-Befund): Controller-Klassen unter
lib/system/endpoint/controller/ existierten, waren aber nicht im Bootstrap
registriert — jede Grid-Löschaktion endete mit `404 unknown_endpoint`.
WoltLab sammelt Endpoints ausschließlich über das ControllerCollecting-Event;
eine Klasse ohne Registrierung ist toter Code mit kaputter UI.

Registrierung wird erkannt als:
  - new \\Fully\\Qualified\\Name( / Fully\\Qualified\\Name::class
  - new ShortName( / ShortName::class nur wenn ein use genau diesen FQCN importiert
    (inkl. Alias: use FQCN as Alias)

Ausgabe (eine Zeile pro Fund, kompatibel zu validate-plugin.sh):
    <datei>:<zeile>:<meldung>

Exit-Code 0, Funde werden über stdout gemeldet.
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

REQUEST_ATTR = re.compile(r"#\[(Get|Post|Delete)Request\(")
CLASS_NAME = re.compile(r"^\s*(?:final\s+)?class\s+(\w+)", re.MULTILINE)
NAMESPACE = re.compile(r"^namespace\s+([\w\\]+)\s*;", re.MULTILINE)


def normalize_fqcn(name: str) -> str:
    return name.lstrip("\\")


def is_registered(short_name: str, fqcn: str, bootstrap_text: str) -> bool:
    """True, wenn Bootstrap genau diesen Controller registriert (nicht nur gleichen Kurznamen)."""
    fqcn_n = normalize_fqcn(fqcn)
    fqcn_re = re.escape(fqcn_n)

    # Vollqualifiziert: new \Foo\Bar\Baz( / new Foo\Bar\Baz(
    if re.search(r"new\s+\\?" + fqcn_re + r"\s*\(", bootstrap_text):
        return True
    if re.search(r"\\?" + fqcn_re + r"::class", bootstrap_text):
        return True

    # use-Imports, die genau diesen FQCN meinen → Kurzname oder Alias erlaubt
    aliases: set[str] = set()
    for m in re.finditer(
        r"use\s+\\?([\w\\]+)\s+as\s+(\w+)\s*;",
        bootstrap_text,
    ):
        if normalize_fqcn(m.group(1)) == fqcn_n:
            aliases.add(m.group(2))
    for m in re.finditer(
        r"use\s+\\?([\w\\]+)\s*;",
        bootstrap_text,
    ):
        if normalize_fqcn(m.group(1)) == fqcn_n:
            aliases.add(short_name)

    for alias in aliases:
        if re.search(r"new\s+" + re.escape(alias) + r"\s*\(", bootstrap_text):
            return True
        if re.search(re.escape(alias) + r"::class", bootstrap_text):
            return True

    return False


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".")

    controllers: list[tuple[str, str, Path, int]] = []  # short, fqcn, file, line
    for php in root.glob("**/lib/system/endpoint/controller/**/*.class.php"):
        text = php.read_text(encoding="utf-8", errors="replace")
        if not REQUEST_ATTR.search(text):
            continue
        m = CLASS_NAME.search(text)
        if not m:
            continue
        ns_m = NAMESPACE.search(text)
        short = m.group(1)
        fqcn = f"{ns_m.group(1)}\\{short}" if ns_m else short
        line = text[: m.start()].count("\n") + 1
        controllers.append((short, fqcn, php, line))

    if not controllers:
        return 0

    bootstrap_text = ""
    for bootstrap in root.glob("**/lib/bootstrap/*.php"):
        bootstrap_text += bootstrap.read_text(encoding="utf-8", errors="replace")

    if not bootstrap_text:
        for short, _fqcn, php, line in controllers:
            print(
                f"{php}:{line}:Endpoint-Controller {short} gefunden, "
                "aber kein lib/bootstrap/*.php vorhanden"
            )
        return 0

    for short, fqcn, php, line in controllers:
        if not is_registered(short, fqcn, bootstrap_text):
            print(
                f"{php}:{line}:Endpoint-Controller {short} ist NICHT im Bootstrap "
                "registriert (ControllerCollecting) — Requests enden mit 404 unknown_endpoint"
            )

    return 0


if __name__ == "__main__":
    sys.exit(main())
