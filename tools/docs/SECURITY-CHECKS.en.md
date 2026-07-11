# Security checks (plugin validation)

**[Deutsche Version](SECURITY-CHECKS.de.md)**

Findings from plugin reviews and WoltLab 6.2.5, implemented as reusable tools.

## Tools

| Script | Purpose |
|--------|---------|
| `check-template-xss.py` | XSS heuristics for `.tpl` (without false positives from `{lang}`, `{unsafe:}`, comments) |
| `check-template-layout.py` | Root `*.tpl` → warning “move to templates/” (`--strict` fails) |
| `check-like-escaping.py` | LIKE without `escapeLikeValue()` (WoltLab 6.2.5 pattern) |
| `fix-template-xss-escaping.py` | Semi-automatic fix for attributes and `<script>` |

Used or recommended by `validate-plugin.sh`.

## XSS check (WoltLab-specific)

**WoltLab auto-escapes plain `{$var}`** — do not use `|encodeHTML` (invalid modifier, compile fatal).

**Reports:**

- `|encodeHTML` in templates (invalid modifier)
- Plain `{$var}` in `<script>` without `{unsafe:$var|encodeJS}`

**Ignores:** Plain `{$var}` in HTML — correct per compiler.

```bash
python3 tools/check-template-xss.py /path/to/plugin
python3 tools/fix-template-xss-escaping.py /path/to/plugin --dry-run
```

## LIKE escaping

From WoltLab **6.2.5**, the core uses `WCF::getDB()->escapeLikeValue($term)` for all LIKE searches.

**Bad:**

```php
['%' . addcslashes($query, '%_') . '%']
['%' . $userInput . '%']
```

**Good:**

```php
['%' . WCF::getDB()->escapeLikeValue($query) . '%']
```

```bash
python3 tools/check-like-escaping.py /path/to/plugin
```

## SQL injection heuristics (`validate-plugin.sh`)

The broad “string concatenation in query” rule was narrowed:

- Warning only when **request data** (`$_GET`/`$_POST`/…) appears in SQL string concatenation
- Fewer false positives with prepared statements and table names

## Reference

- [WoltLab update 6.2.5](https://www.woltlab.com/community/thread/318621-aktualisierung-woltlab-suite-6-2-5-6-1-22-6-0-26-5-5-26/)
- Plugin repo: project-specific compatibility/store docs under `docs/store/`

## Dynamic properties on page objects (PHP 8.2+)

**Symptom:** `Creation of dynamic property wcf\acp\page\IndexPage::$menuBadgeText is deprecated` — under WCF ErrorHandler often **fatal** on `/acp/index.php?index/`.

**Cause:** `$eventObj->property = …` in `assignVariables` listeners; core pages do not declare these properties.

**Fix:**

```php
WCF::getTPL()->assign(['menuBadgeText' => $value]);
$parameters['variables']['menuBadgeText'] = $value; // optional
```

**Validator:** `validate-plugin.sh` → section “Event listeners (dynamic properties)”.
