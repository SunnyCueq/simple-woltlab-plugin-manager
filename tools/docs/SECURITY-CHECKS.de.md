# Security-Checks (Plugin-Validierung)

Erkenntnisse aus Plugin-Reviews und WoltLab 6.2.5, umgesetzt als wiederverwendbare Tools.

## Tools

| Skript | Zweck |
|--------|--------|
| `check-template-xss.py` | XSS-Heuristik für `.tpl` (ohne False Positives aus `{lang}`, `{unsafe:}`, Kommentaren) |
| `check-like-escaping.py` | LIKE ohne `escapeLikeValue()` (WoltLab 6.2.5-Pattern) |
| `fix-template-xss-escaping.py` | Halbautomatischer Fix für Attribute und `<script>` |

Alle drei werden von `validate-plugin.sh` genutzt bzw. empfohlen.

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
