# Security-Checks (Plugin-Validierung)

Wiederkehrende Fehler aus Reviews und WoltLab 6.2.x — als prüfbare Skripte.

!!! info "Nur lokal — kein Store-Upload"

    Build und Validate **laden nichts hoch**. Sie spiegeln viele automatische Store-Regeln auf deinem Rechner. Der Upload auf woltlab.com bleibt ein eigener, manueller Schritt.

## Wie die Checks laufen

| Weg | Was passiert |
|-----|--------------|
| **Build** | Liest `swpm-check-registry.txt` und führt die Einträge über `swpm-run-checks.sh` aus |
| **Validate** | Dieselben Themen (Templates, Sprache, LIKE, …) plus weitere Richtlinien-Checks (Syntax, HTTP, Debug-Code, …) — weiterhin lokal |

Vollständige Registry inkl. fail/warn und Runner-Flags: [Tools-Übersicht](TOOLS-OVERVIEW.md).

```bash
./tools/swpm-run-checks.sh --mode list
./tools/swpm-run-checks.sh --mode build /pfad/zum/plugin
```

Neue **Build**-Fail-Checks: Zeile in `swpm-check-registry.txt` + Skript unter `tools/`.

## Registry-Themen (Überblick)

Die ausführliche Tabelle steht in der [Tools-Übersicht](TOOLS-OVERVIEW.md). Hier die Deep-Dives und verwandten Guides:

| Thema | Details |
|-------|---------|
| Templates / XSS / Modifier / Foreach | Abschnitte unten + [Template-Regeln](WOLTLAB-TEMPLATE-RULES.md) |
| Sprache (Kategorie, Integrität, Anrede) | [Language-XML](LANGUAGE-XML.md) |
| LIKE / SQL-Heuristik / Event-Listener | Abschnitte unten |
| Style-Pakete / CSS-Assets | `check-style-package.py`, `check-style-assets.py` |
| RPC-Endpoints / AMD `setup` | Registry-IDs `endpoint-registration`, `js-amd-exports` |
| Store-Gesamtliste | [Plugin-Store-Checkliste](PLUGIN-STORE-CHECKLIST.md) |

## Wichtige Skripte (Deep-Dive-Fokus)

| Skript | Zweck |
|--------|--------|
| `check-template-xss.py` | Ungültige Modifier / Script-Escaping in `.tpl` |
| `check-template-layout.py` | Root-`*.tpl` → nach `templates/` (`--strict` failt) |
| `check-template-modifiers.py` | Unbekannte Template-Modifier (z. B. `\|cat`) |
| `check-like-escaping.py` | LIKE ohne `escapeLikeValue()` |
| `check-style-package.py` | Style-Paket: `style.xml` / Variablen / Ordner |
| `check-style-assets.py` | CSS `url(...)` auf fehlende Dateien |
| `check-language-address.py` | DE Anrede Sie/Du (Warnung) |
| `check-js-amd-exports.py` | AMD Named Export `setup` |
| `check-endpoint-registration.py` | RPC-Controller registriert |
| `fix-template-xss-escaping.py` | Halbautomatischer Fix (fügt **kein** `\|encodeHTML` hinzu) |

---

## XSS-Check (WoltLab-spezifisch)

**WoltLab escaped Plain-`{$var}` automatisch** — kein `|encodeHTML` (existiert nicht, Compile-Fatal).

**Meldet:**

- `|encodeHTML` in Templates (ungültiger Modifier)
- Plain `{$var}` in `<script>` ohne `{unsafe:$var|encodeJS}`

**Ignoriert:** Plain `{$var}` in HTML — korrekt per Compiler.

```bash
python3 tools/check-template-xss.py /pfad/zum/plugin
python3 tools/fix-template-xss-escaping.py /pfad/zum/plugin --dry-run
```

## LIKE-Escaping

Ab WoltLab **6.2.5** nutzt der Core `WCF::getDB()->escapeLikeValue($term)` für alle LIKE-Suchen.

**Schlecht:**

```php
['%' . addcslashes($query, '%_') . '%']
['%' . $userInput . '%']
```

**Gut:**

```php
['%' . WCF::getDB()->escapeLikeValue($query) . '%']
```

```bash
python3 tools/check-like-escaping.py /pfad/zum/plugin
```

## SQL-Injection-Heuristik (validate-plugin.sh)

Die breite „String-Concatenation in Query“-Regel wurde verengt:

- Nur noch Warnung bei **Request-Daten** (`$_GET`/`$_POST`/…) in SQL-String-Konkatenation
- Viele False Positives bei Prepared Statements mit Tabellennamen entfallen

## Referenz

- [WoltLab Update 6.2.5](https://www.woltlab.com/community/thread/318621-aktualisierung-woltlab-suite-6-2-5-6-1-22-6-0-26-5-5-26/)
- Plugin-Repo: eigene Kompatibilitäts-/Store-Doku unter `docs/store/`

## Dynamische Properties auf Page-Objekten (PHP 8.2+)

**Symptom:** `Creation of dynamic property wcf\acp\page\IndexPage::$menuBadgeText is deprecated` — unter WCF ErrorHandler oft **Fatal** auf `/acp/index.php?index/`.

**Ursache:** `$eventObj->property = …` in `assignVariables`-Listenern; Core-Pages deklarieren diese Properties nicht.

**Fix:**

```php
WCF::getTPL()->assign(['menuBadgeText' => $value]);
$parameters['variables']['menuBadgeText'] = $value; // optional
```

**Validator:** `validate-plugin.sh` → Abschnitt „Event-Listener (dynamic properties)“.
