# WoltLab template rules (plugins)

Quick reference for `.tpl` files in WoltLab Suite plugins. The build (`build.sh`) stops when invalid modifiers are found (`check-template-xss.py`).

## Source location

| Location | Role |
|----------|------|
| `templates/*.tpl` | **Default location** — frontend templates → `templates.tar` |
| `acptemplates/*.tpl` | ACP templates (admin area) → `acptemplates.tar` |
| Root `*.tpl` | Older fallback (warning; `--strict` / `--strict-layout` fails) |

PIP XMLs (`option.xml`, `page.xml`, …) stay in the package root. See [Package layout](PACKAGE-LAYOUT.md).

## HTML output

| Pattern | Behavior |
|---------|----------|
| `{$variable}` | **Auto-escape** via `StringUtil::encodeHTML` — default for HTML |
| `{$variable\|encodeHTML}` | **Invalid** — modifier does not exist → compile fatal |
| `{$variable\|escape}` | **Invalid** — same as above |

## JavaScript in `<script>`

| Pattern | Behavior |
|---------|----------|
| Plain `{$var}` in `<script>` | XSS risk — build warns |
| `{unsafe:$var\|encodeJS}` | Correct for JS context |
| `'{$var}'` in `<script>` without `{unsafe:…\|encodeJS}` | Build warns |

## Ignored by the check

- `{* Smarty comments *}`
- `{lang}…{/lang}` and `{jslang}…{/jslang}`
- Already compiled `{unsafe:…}` blocks

## No `{{` in `.tpl`

WoltLab treats `{{…` as the start of a template tag. JSDoc such as `/** @type {{file: File}} */` or Mustache/Vue `{{ var }}` inside `.tpl` → compile fatal (`unknown tag`) — often only on the first ACP hit. Check: `check-template-double-brace.py` (build/validate, fail).

## Practice

1. HTML: plain `{$var}` — **never** append `|encodeHTML` or `|escape` (modifiers do not exist).
2. JS: `{unsafe:$var|encodeJS}` or pass data via `data-*` + DOM.
3. **JS array from PHP list:** `{implode from=$items item=item}'{unsafe:$item|encodeJS}'{/implode}` — **not** `{foreach name=…}` with `$…Loop.last` (WoltLab stores loop state under `$tpl.foreach`, not `$fooLoop` → runtime fatal “Undefined array key”).
4. Core reference: `shared_itemListFormField.tpl` (ItemList init).
5. After template changes: `python3 tools/check-template-xss.py` and `python3 tools/check-template-foreach.py`
6. Build: `./tools/build.sh same` (or patch/minor) — template errors stop the build.
7. Clean up wrong modifiers: `python3 tools/fix-template-xss-escaping.py /path/to/plugin --dry-run`

See also: [Security checks](SECURITY-CHECKS.md), `check-template-xss.py`.
