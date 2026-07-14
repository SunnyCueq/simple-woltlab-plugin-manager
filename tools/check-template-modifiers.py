#!/usr/bin/env python3
"""Prüft Templates auf unbekannte Template-Modifier.

Hintergrund (Shr1nkr-Befunde 2026-07-02, 2026-07-08):
- {$var|formatNumeric} kompiliert nicht — WoltLab kennt nur die PHP-Funktions-
  Whitelist (TemplateScriptingCompiler::$allowedModifierFunctions) plus
  Modifier-Plugins (z. B. |time, |truncate). Zahlformatierung ist {#$var}.
- Smarty-|cat existiert nicht — String-Zusammenbau in PHP (assign-Array), nicht
  {assign value='prefix-'|cat:$id}. Whitelist: ggf. |concat (PHP-Funktion).
- Der Fehler fällt erst zur Laufzeit auf, wenn das Template erstmals gerendert
  wird ("Template compilation failed: unknown modifier") — selten gerenderte
  Templates (Box-Inhalte, Fehlerseiten) rutschen so durch Tests.

Erkennt zusätzlich Modifier-Plugins des geprüften Plugins selbst
(lib/system/template/plugin/*ModifierTemplatePlugin.class.php).

Ausgabe: <tpl-datei>:<zeile>:<problem>
Exit-Code 0, Funde über stdout.
"""

import re
import sys
from pathlib import Path

# TemplateScriptingCompiler::$allowedModifierFunctions (WoltLab 6.2)
ALLOWED_FUNCTIONS = {
    "abs", "array_key_exists", "array_keys", "array_pop", "array_values",
    "base64_decode", "base64_encode", "basename", "ceil", "concat", "count",
    "currency", "current", "date", "defined", "empty", "end", "explode",
    "filesize", "floatval", "floor", "function_exists", "get_class", "gmdate",
    "implode", "in_array", "is_array", "is_null", "is_numeric", "is_object",
    "is_string", "iterator_count", "intval", "is_subclass_of", "isset", "key",
    "ltrim", "max", "mb_strlen", "mb_strpos", "mb_strtolower", "mb_strtoupper",
    "mb_substr", "md5", "method_exists", "microtime", "min", "preg_match",
    "preg_replace", "print_r", "random_int", "rawurlencode", "reset", "round",
    "sha1", "spl_object_hash", "strip_tags", "strlen", "str_contains",
    "str_ends_with", "str_repeat", "str_replace", "str_starts_with",
    "strtolower", "strtoupper", "substr", "trim", "ucfirst", "var_dump",
    "version_compare", "wcfDebug",
}

# Kern-Modifier-Plugins (wcf/lib/system/template/plugin/*ModifierTemplatePlugin)
CORE_PLUGIN_MODIFIERS = {
    "concat", "currency", "date", "datediff", "encodejs", "escapecdata",
    "filesize", "filesizebinary", "ipsearch", "json", "language",
    "newlinetobreak", "phrase", "plaintime", "shortunit", "tablewordwrap",
    "time", "truncate",
}

# {$var|mod}, {@$var|mod}, {#$var|mod}, auch verkettet {$var|mod1|mod2:arg}
OUTPUT_TAG = re.compile(r"\{[@#]?\$[^{}]*\}")
MODIFIER = re.compile(r"\|\s*([a-zA-Z_][a-zA-Z0-9_]*)")


def plugin_modifiers(root: Path) -> set[str]:
    """Eigene Modifier-Plugins des Plugins (Dateiname → Modifier-Name)."""
    found = set()
    for f in root.glob("**/template/plugin/*ModifierTemplatePlugin.class.php"):
        found.add(f.name.removesuffix("ModifierTemplatePlugin.class.php").lower())
    return found


def main() -> int:
    root = Path(sys.argv[1] if len(sys.argv) > 1 else ".")
    allowed = {m.lower() for m in ALLOWED_FUNCTIONS} | CORE_PLUGIN_MODIFIERS
    allowed |= plugin_modifiers(root)

    for tpl in root.glob("**/*.tpl"):
        if "node_modules" in tpl.parts:
            continue
        for lineno, line in enumerate(
            tpl.read_text(encoding="utf-8", errors="replace").splitlines(), start=1
        ):
            for tag in OUTPUT_TAG.finditer(line):
                # Pipes in String-Literalen ('…|…' / "…|…") nicht als Modifier werten
                stripped = re.sub(r"'[^']*'|\"[^\"]*\"", "", tag.group(0))
                for m in MODIFIER.finditer(stripped):
                    name = m.group(1).lower()
                    if name not in allowed:
                        print(
                            f"{tpl}:{lineno}:Unbekannter Template-Modifier "
                            f"'|{m.group(1)}' — kompiliert erst zur Laufzeit mit "
                            f"Fatal Error (Zahlformat: {{#$var}} verwenden)"
                        )

    return 0


if __name__ == "__main__":
    sys.exit(main())
