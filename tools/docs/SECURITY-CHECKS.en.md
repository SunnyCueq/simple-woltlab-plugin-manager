# Security checks (plugin validation)

Recurring issues from reviews and WoltLab 6.2.x — as runnable scripts.

!!! info "Local only — no store upload"

    Build and validate **do not upload** anything. They mirror many automated store rules on your machine. Uploading to woltlab.com remains a separate, manual step.

## How checks run

| Path | What happens |
|------|----------------|
| **Build** | Reads `swpm-check-registry.txt` and runs entries via `swpm-run-checks.sh` |
| **Validate** | Same topics (templates, language, LIKE, …) plus more guideline checks (syntax, HTTP, debug code, …) — still local |

Full registry including fail/warn and runner flags: [Tools overview](TOOLS-OVERVIEW.md).

```bash
./tools/swpm-run-checks.sh --mode list
./tools/swpm-run-checks.sh --mode build /path/to/plugin
```

New **build** fail checks: add a line in `swpm-check-registry.txt` plus a script under `tools/`.

## Registry topics (overview)

The full table is in the [Tools overview](TOOLS-OVERVIEW.md). Deep-dives and related guides:

| Topic | Details |
|-------|---------|
| Templates / XSS / modifiers / foreach | Sections below + [Template rules](WOLTLAB-TEMPLATE-RULES.md) |
| Language (category, integrity, address) | [Language XML](LANGUAGE-XML.md) |
| LIKE / SQL heuristics / event listeners | Sections below |
| Style packages / CSS assets | `check-style-package.py`, `check-style-assets.py` |
| RPC endpoints / AMD `setup` | Registry IDs `endpoint-registration`, `js-amd-exports` |
| Full store list | [Plugin Store checklist](PLUGIN-STORE-CHECKLIST.md) |

## Key scripts (deep-dive focus)

| Script | Purpose |
|--------|---------|
| `check-template-xss.py` | Invalid modifiers / script escaping in `.tpl` |
| `check-template-layout.py` | Root `*.tpl` → move to `templates/` (`--strict` fails) |
| `check-template-modifiers.py` | Unknown template modifiers (e.g. `\|cat`) |
| `check-like-escaping.py` | LIKE without `escapeLikeValue()` |
| `check-style-package.py` | Style package: `style.xml` / variables / folders |
| `check-style-assets.py` | CSS `url(...)` pointing at missing files |
| `check-language-address.py` | DE Sie/Du address tone (warning) |
| `check-js-amd-exports.py` | AMD named export `setup` |
| `check-endpoint-registration.py` | RPC controllers registered |
| `fix-template-xss-escaping.py` | Semi-automatic fix (**never** adds `\|encodeHTML`) |

---

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
