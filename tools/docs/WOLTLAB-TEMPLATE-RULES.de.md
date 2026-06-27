# WoltLab Template-Regeln (Plugins)

Kurzreferenz für `.tpl`-Dateien in WoltLab-Suite-Plugins. Der Build (`build.sh`) bricht bei Verstößen gegen **ungültige Modifier** ab (`check-template-xss.py`).

## HTML-Ausgabe

| Muster | Verhalten |
|--------|-----------|
| `{$variable}` | **Auto-Escape** via `StringUtil::encodeHTML` — Standard für HTML |
| `{$variable\|encodeHTML}` | **Ungültig** — Modifier existiert nicht → Compile-Fatal |
| `{$variable\|escape}` | **Ungültig** — wie oben (z. B. Shr1nkr 1.3.22: `demoShowcase.tpl`) |

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

1. HTML: plain `{$var}` — nicht „zusätzlich escapen“.
2. JS: `{unsafe:$var|encodeJS}` oder Daten per `data-*` + DOM.
3. Nach Template-Änderung: `python3 tools/check-template-xss.py /pfad/zum/plugin`
4. Build: `./tools/build.sh same` (oder patch/minor) — Template-Fehler stoppen den Build.

Siehe auch: `tools/docs/SECURITY-CHECKS.de.md`, `check-template-xss.py`.
