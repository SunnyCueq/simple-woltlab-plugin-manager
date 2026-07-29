# Tools-Übersicht (SWPM)

Welche Skripte gibt es, und wofür? Kurze Transparenzliste — Details und Befehle stehen in der [Tools-Referenz](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/tools/README.de.md).

!!! info "Build & Validate laden nichts hoch"

    `./tools.sh build` und `./tools.sh validate` prüfen dein Plugin **nur lokal** — analog zu den automatischen Regeln, die WoltLab später beim Store-Upload anwendet. Es wird **nichts** zu woltlab.com gesendet. Der echte Store-Upload ist ein späterer, manueller Schritt (siehe [Plugin-Store-Checkliste](PLUGIN-STORE-CHECKLIST.md)).

## Alltagsweg (kurz)

1. Plugin-Quellcode in einem Ordner mit `package.xml` (oft `temp_edit/` = entpackte Arbeitskopie)
2. `./tools.sh build` — Paket bauen (inkl. Build-Checks)
3. `./tools.sh validate …` — lokale Qualitäts- und Richtlinien-Checks
4. Optional: `./tools.sh push` — Commit, Tag, GitHub-Release (dein Repo)

Produktlinie (mehrere Pakete): `./tools.sh family:check` / `family:build` — siehe [Produktlinie](PRODUCT-LINE.md).

### Kurze Begriffe

| Begriff | Bedeutung |
|---------|-----------|
| **ACP** | Admin Control Panel — Adminbereich der WoltLab-Suite |
| **PIP** | Package Installation Plugin — Installationsschritt in `package.xml` (Dateien, Templates, Optionen, …) |
| **temp_edit/** | Typischer Ordner für die entpackte Plugin-Arbeitskopie |
| **fail** | Check bricht Build/Validate ab |
| **warn** | Nur Hinweis; mit `--strict-layout` kann Layout zum Fehler werden |

```bash
./tools.sh help                          # alle CLI-Befehle
./tools/swpm-run-checks.sh --mode list   # Build-Check-Registry anzeigen
```

---

## Häufige CLI-Befehle

| Befehl | Zweck |
|--------|--------|
| `./tools.sh` / `./tools.sh help` | Menü bzw. Befehlsliste |
| `./tools.sh build` / `build:same` / `build:dry-run` | Paket bauen (Version bump / unverändert / Probelauf) |
| `./tools.sh validate [plugin]` | Lokale Qualitätschecks (breiter als nur Build-Registry) |
| `./tools.sh family:list` / `order` / `check` | Produktlinie anzeigen / Reihenfolge / Graph prüfen |
| `./tools.sh family:build` / `validate` | Alle Pakete der Linie in Dep-Reihenfolge |
| `./tools.sh family:init` / `add-addon` | Manifest anlegen / Add-on ergänzen |
| `./tools.sh typescript` | TypeScript kompilieren (wenn vorhanden) |
| `./tools.sh phpstan [plugin]` | PHPStan nur mit `phpstan.neon(.dist)` |
| `./tools.sh lint:python [--fix]` | ruff für Manager-`tools/*.py` |
| `./tools.sh push` | Commit, Tag, GitHub-Release |
| `./tools.sh setup` | Core/Docs/d.ts optional laden |
| `./tools.sh sync-woltlab-refs` | Referenz-Spiegel aktualisieren (Maintainer) |

---

## Kern-Werkzeuge (Menü / CLI)

| Skript / Befehl | Rolle |
|-----------------|--------|
| `tools.sh` | Einstieg: Menü und CLI |
| `build.sh` | Installierbares `.tar.gz` unter `releases/<plugin>/` bauen, Version bump/same |
| `validate-plugin.sh` | Lokale Qualitäts-, Struktur- und Richtlinien-Checks |
| `typescript.sh` | TypeScript → JavaScript |
| `unpack.sh` | Paket nach `temp_edit/` entpacken |
| `gitpush.sh` | Commit, Push, Tag, Release; Notes = Changelog + Compare/Commits |
| `setup-minimal.sh` | Core, Docs, d.ts, Pfade |
| `help.sh` | Dokumentation öffnen |
| `swpm-family.sh` | Produktlinie (Basis + Add-ons) |
| `pack-style-tar.sh` | Style-Archiv (`style.tar` / `style.tgz`) laut `package.xml` |

---

## Build-Checks (Registry)

Laufen beim **Build** über `swpm-run-checks.sh`. Quelle: `swpm-check-registry.txt`.

**Build vs Validate:** Build führt die Registry aus (fail bricht ab, warn loggt). Validate deckt dieselben Themen ab und ergänzt u. a. PHP/XML-Syntax, PIP-Quellen, HTTP-APIs, Debug-Code, Cloud-Verbote — weiterhin **nur lokal**.

### Runner-Flags

```bash
./tools/swpm-run-checks.sh --mode list              # Registry anzeigen
./tools/swpm-run-checks.sh --mode build [plugin]    # Checks ausführen
./tools/swpm-run-checks.sh --mode build --strict-layout [plugin]
./tools/swpm-run-checks.sh --mode build --amd-prefix=MyApp [plugin]
```

- **`needs`:** Checks mit `language` / `templates` / `lib` / `style` / `js_acp` laufen nur, wenn passende Dateien im Plugin liegen — sonst Skip (kein Fehler).
- **`--amd-prefix`:** Prefix für AMD-/JS-Checks, wenn nicht aus `package.xml` ableitbar.
- **Exit:** `0` ok, `1` fail-Check fehlgeschlagen, `2` Runner-/Argumentfehler.

| ID | Stufe | Skript | Kurz |
|----|-------|--------|------|
| language-categories | fail | `check-language-categories.py` | Sprach-XML: Kategorie ↔ Item → [Language-XML](LANGUAGE-XML.md) |
| language-integrity | fail | `check-language-integrity.py` | Sprach-XML Integrität |
| template-xss | fail | `check-template-xss.py` | Ungültige Modifier / Script-Escaping → [Template-Regeln](WOLTLAB-TEMPLATE-RULES.md) |
| template-modifiers | fail | `check-template-modifiers.py` | Modifier-Whitelist |
| template-foreach | fail | `check-template-foreach.py` | Foreach-Loop-Variablen |
| template-double-brace | fail | `check-template-double-brace.py` | Doppelte `{{` (JSDoc/Mustache) → Compile-Fatal |
| language-option-placeholders | fail | `check-language-option-placeholders.py` | Option-Hilfe ohne Template-Platzhalter (`{$url}` o. Ä.) |
| endpoint-registration | fail | `check-endpoint-registration.py` | RPC-Endpoints registriert |
| like-escaping | fail | `check-like-escaping.py` | LIKE + `escapeLikeValue` |
| js-amd-exports | fail | `check-js-amd-exports.py` | AMD Named Exports (`setup`) |
| style-package | fail | `check-style-package.py` | Style-Paket `style.xml` / Variablen |
| template-layout | warn | `check-template-layout.py` | Templates nach `templates/` |
| template-notices | warn | `check-template-notices.py` | Hinweis-Boxen |
| style-assets | warn | `check-style-assets.py` | CSS `url(...)`-Dateien |
| language-keys | warn | `check-language-keys.py` | DE/EN-Keys (Code-Nutzung) |
| language-pip-keys | warn | `check-language-pip-keys.py` | Implizite PIP-Keys (Optionen/Gruppenrechte/ACP-Menü) → [Language-XML](LANGUAGE-XML.md) |
| language-address | warn | `check-language-address.py` | DE Anrede Sie/Du (Heuristik) |
| first-release-hygiene | warn | `check-first-release-hygiene.py` | 1.0.0 ohne Update-Altlasten |
| package-descriptions | warn | `check-package-descriptions.py` | `packagedescription` in `package.xml` |

**Außerhalb der Registry (aber wichtig):** `check-pip-sources.py` — PIP-Quellen vs. `package.xml` (Build/Validate).

Tiefere Erklärungen: [Security-Checks](SECURITY-CHECKS.md). Weitere Guides: [Language-XML](LANGUAGE-XML.md), [Template-Regeln](WOLTLAB-TEMPLATE-RULES.md), [ACP-Install](ACP-PACKAGE-INSTALL.md), [Docker-Rechte](DOCKER-APP-PERMISSIONS.md), [Logging](LOGGING.md).

---

## Optional

| Skript / Befehl | Rolle |
|-----------------|--------|
| `check-typescript.sh` | `tsc`, wenn `tsconfig` / `ts/` vorhanden |
| `run-phpstan.sh` | PHPStan nur mit `phpstan.neon(.dist)` — sonst Skip |
| `lint-manager-python.sh` / `./tools.sh lint:python` | ruff für Manager-`tools/*.py` (nicht für dein Plugin-PHP) |
| `fix-template-xss-escaping.py` | Halbautomatischer Template-Fix (kein `\|encodeHTML`) |

---

## Docker (optional, lokal)

Nur nötig, wenn du eine **lokale WoltLab-Testinstanz** in Docker hast. Kern-Build/Validate brauchen kein Docker.

| Skript | Rolle |
|--------|--------|
| `prepare-acp-install.sh` | Paket in den Web-Container legen |
| `check-woltlab-app-permissions.sh` | Rechte prüfen |
| `fix-woltlab-app-permissions.sh` | Rechte nach `docker cp` korrigieren |
| `reset-app-for-acp-install.sh` | Halbfertige App-Installation bereinigen |

Siehe [ACP-Install](ACP-PACKAGE-INSTALL.md) und [Docker-Rechte](DOCKER-APP-PERMISSIONS.md).

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

### Bei Fehlern

Log-Pfad und Kontext: [Logging](LOGGING.md).
