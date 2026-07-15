# Tools-Übersicht (SWPM)

**[English version](TOOLS-OVERVIEW.en.md)**

Welche Skripte gibt es, und wofür? Kurze Transparenzliste — Details und Befehle stehen in der [Tools-Referenz](../README.de.md).

## Alltagsweg (kurz)

1. Plugin-Quellcode in einem Ordner mit `package.xml` (oft `temp_edit/`)
2. `./tools.sh build` — Paket bauen (inkl. Build-Checks)
3. `./tools.sh validate …` — vor Store/Release prüfen
4. Optional: `./tools.sh push` — Commit, Tag, GitHub-Release

Produktlinie (mehrere Pakete): `./tools.sh family:check` / `family:build` — siehe [PRODUCT-LINE.de.md](PRODUCT-LINE.de.md).

```bash
./tools.sh help                          # alle CLI-Befehle
./tools/swpm-run-checks.sh --mode list   # Build-Check-Registry
```

---

## Kern-Werkzeuge (Menü / CLI)

| Skript / Befehl | Rolle |
|-----------------|--------|
| `tools.sh` | Einstieg: Menü und CLI |
| `build.sh` | Installierbares `.tar.gz` bauen, Version bump/same |
| `validate-plugin.sh` | Store-/Sicherheits- und Strukturchecks |
| `typescript.sh` | TypeScript → JavaScript |
| `unpack.sh` | Paket nach `temp_edit/` entpacken |
| `gitpush.sh` | Commit, Push, Tag, Release (Plugin) |
| `setup-minimal.sh` | Core, Docs, d.ts, Pfade |
| `help.sh` | Dokumentation öffnen |
| `swpm-family.sh` | Produktlinie (Basis + Add-ons) |
| `pack-style-tar.sh` | Style-Archiv (`style.tar` / `style.tgz`) |

---

## Build-Checks (Registry)

Laufen beim **Build** über `swpm-run-checks.sh`. Quelle: `swpm-check-registry.txt`.

| ID | Stufe | Skript | Kurz |
|----|-------|--------|------|
| language-categories | fail | `check-language-categories.py` | Sprach-XML: Kategorie ↔ Item |
| language-integrity | fail | `check-language-integrity.py` | Sprach-XML Integrität |
| template-xss | fail | `check-template-xss.py` | Ungültige Modifier / Script-Escaping |
| template-modifiers | fail | `check-template-modifiers.py` | Modifier-Whitelist |
| template-foreach | fail | `check-template-foreach.py` | Foreach-Loop-Variablen |
| endpoint-registration | fail | `check-endpoint-registration.py` | RPC-Endpoints registriert |
| like-escaping | fail | `check-like-escaping.py` | LIKE + `escapeLikeValue` |
| js-amd-exports | fail | `check-js-amd-exports.py` | AMD Named Exports (`setup`) |
| style-package | fail | `check-style-package.py` | Style-Paket `style.xml` / Variablen |
| template-layout | warn | `check-template-layout.py` | Templates nach `templates/` |
| template-notices | warn | `check-template-notices.py` | Hinweis-Boxen |
| style-assets | warn | `check-style-assets.py` | CSS `url(...)`-Dateien |
| language-keys | warn | `check-language-keys.py` | DE/EN-Keys |
| first-release-hygiene | warn | `check-first-release-hygiene.py` | 1.0.0 ohne Update-Altlasten |
| package-descriptions | warn | `check-package-descriptions.py` | `packagedescription` in `package.xml` |

**Außerhalb der Registry (aber wichtig):** `check-pip-sources.py` — PIP-Quellen vs. `package.xml` (Build/Validate).

Weitere Themen und Heuristiken: [SECURITY-CHECKS.de.md](SECURITY-CHECKS.de.md).

---

## Optional

| Skript / Befehl | Rolle |
|-----------------|--------|
| `check-typescript.sh` | `tsc`, wenn `tsconfig` / `ts/` vorhanden |
| `run-phpstan.sh` | PHPStan nur mit `phpstan.neon(.dist)` |
| `lint-manager-python.sh` / `./tools.sh lint:python` | ruff für Manager-`tools/*.py` |
| `fix-template-xss-escaping.py` | Halbautomatischer Template-Fix (kein `\|encodeHTML`) |

---

## Docker (optional, lokal)

| Skript | Rolle |
|--------|--------|
| `prepare-acp-install.sh` | Paket in den Web-Container legen |
| `check-woltlab-app-permissions.sh` | Rechte prüfen |
| `fix-woltlab-app-permissions.sh` | Rechte nach `docker cp` korrigieren |
| `reset-app-for-acp-install.sh` | Halbfertige App-Installation bereinigen |

Siehe [ACP-PACKAGE-INSTALL.de.md](ACP-PACKAGE-INSTALL.de.md).

---

## Intern / Unterstützung

Für den Alltag selten direkt nötig; werden von den Kern-Skripten genutzt:

| Skript | Rolle |
|--------|--------|
| `common.sh`, `ui.sh` | Farben, Menü, gemeinsame Hilfen |
| `swpm-package-resolve.sh` | Metadaten aus `package.xml` / `.env` |
| `swpm-package-report.py` | JSON-Build-Report |
| `swpm-run-checks.sh` | Registry-Runner |
| `swpm-family-resolve.sh` | Family-Manifest auflösen |
| `check-family-deps.py` | Abhängigkeitsgraph der Produktlinie |
| `download-woltlab-core.sh` | Core laden (Setup) |
| `sync-woltlab-references.sh` | Docs/WCF-Spiegel aktualisieren |
| `update-woltlab-version.sh` | Versionsinfo |
| `manager-push.sh` | nur Maintainer (falls vorhanden) |

`tools/woltlab-plugin-recovery/` ist ein separates Recovery-Hilfsmittel — nicht Teil des normalen Build-/Validate-Menüs.
