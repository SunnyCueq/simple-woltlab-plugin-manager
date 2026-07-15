# WoltLab Template-Regeln (Plugins)

**[English version](WOLTLAB-TEMPLATE-RULES.en.md)**

Kurzreferenz für `.tpl`-Dateien in WoltLab-Suite-Plugins. Der Build (`build.sh`) bricht ab, wenn ungültige Modifier gefunden werden (`check-template-xss.py`).

## Quellort

| Ort | Rolle |
|-----|--------|
| `templates/*.tpl` | **Standardort** — Frontend-Templates → `templates.tar` |
| `acptemplates/*.tpl` | ACP-Templates (Adminbereich) → `acptemplates.tar` |
| Root-`*.tpl` | Alter Fallback (Warnung; mit `--strict` / `--strict-layout` Fehler) |

PIP-XMLs (`option.xml`, `page.xml`, …) bleiben im Paket-Root. Siehe `PACKAGE-LAYOUT.de.md`.

## HTML-Ausgabe

| Muster | Verhalten |
|--------|-----------|
| `{$variable}` | **Auto-Escape** via `StringUtil::encodeHTML` — Standard für HTML |
| `{$variable\|encodeHTML}` | **Ungültig** — Modifier existiert nicht → Compile-Fatal |
| `{$variable\|escape}` | **Ungültig** — wie oben |

## JavaScript in `<script>`

| Muster | Verhalten |
|--------|-----------|
| Plain `{$var}` in `<script>` | XSS-Risiko — Build warnt |
| `{unsafe:$var\|encodeJS}` | Korrekt für JS-Kontext |
| `'{$var}'` in `<script>` ohne `{unsafe:…\|encodeJS}` | Build warnt |

## Ignoriert beim Check

- `{* Smarty-Kommentare *}`
- `{lang}…{/lang}` und `{jslang}…{/jslang}`
- Bereits kompilierte `{unsafe:…}`-Blöcke

## Praxis

1. HTML: plain `{$var}` — **niemals** `|encodeHTML` oder `|escape` anhängen (Modifier existieren nicht).
2. JS: `{unsafe:$var|encodeJS}` oder Daten per `data-*` + DOM.
3. **JS-Array aus PHP-Liste:** `{implode from=$items item=item}'{unsafe:$item|encodeJS}'{/implode}` — **nicht** `{foreach name=…}` mit `$…Loop.last` (WoltLab speichert Loop-State unter `$tpl.foreach`, nicht `$fooLoop` → Laufzeit-Fatal „Undefined array key").
4. Referenz im Core: `shared_itemListFormField.tpl` (ItemList-Init).
5. Nach Template-Änderung: `python3 tools/check-template-xss.py /pfad/zum/plugin` und `python3 tools/check-template-foreach.py /pfad/zum/plugin`
6. Build: `./tools/build.sh same` (oder patch/minor) — Template-Fehler stoppen den Build.
7. Cleanup falscher Modifier: `python3 tools/fix-template-xss-escaping.py /pfad/zum/plugin --dry-run`

Siehe auch: `tools/docs/SECURITY-CHECKS.de.md`, `check-template-xss.py`.
