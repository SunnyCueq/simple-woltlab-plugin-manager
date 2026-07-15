# Language XML (`language/*.xml`)

So translations work on package update and at runtime. Common pitfalls from reviews and WoltLab 6.2.x.

## Category ↔ item name (required)

On import, WoltLab checks each `<item>` against its parent `<category>` (`LanguageEditor::validateItemName`).

**Rule:** An item’s `name` must

- match the category name exactly, **or**
- start with `CategoryName.`.

```xml
<!-- OK -->
<category name="myapp.topLinks">
  <item name="myapp.topLinks.byViews"><![CDATA[…]]></item>
</category>

<!-- ERROR on update — item does not belong to category -->
<category name="myapp.demo">
  <item name="myapp.topLinks.byViews"><![CDATA[…]]></item>
</category>
```

**ACP symptom:** `InvalidArgumentException` — *The variable “myapp.topLinks.byViews” does not belong to category “myapp.demo”.*

**Fix options:**

1. Move the item to the correct category (`myapp.topLinks.*` → `<category name="myapp.topLinks">`).
2. Rename the key if it belongs to the demo category (`myapp.demo.topLinks.byViews`).

Update the key name in templates/PHP accordingly.

## Formal vs informal German (Sie / Du)

WoltLab supports both tones in one string:

```xml
<item name="myapp.action.save"><![CDATA[{if LANGUAGE_USE_INFORMAL_VARIANT}Speichere{else}Speichern Sie{/if}]]></item>
```

Do not use `variant="informal"` on `<item>` — it is not in language.xsd (`check-language-integrity.py`).

**Tone consistency:** `check-language-address.py` warns heuristically when `de.xml` clearly mixes **Sie** and **Du** (without the variant IF). Warning only — not a build failure. Unify the tone or use the IF pattern.

```bash
python3 tools/check-language-address.py /path/to/plugin
```

## Check before build

```bash
python3 tools/check-language-categories.py /path/to/plugin
```

Runs automatically in:

- `./tools/build.sh` (aborts the build)
- `./tools/validate-plugin.sh` (error, no release)

## Further rules (WoltLab docs)

- Category: 2–3 segments, alphanumeric, separated by `.`
- Item: at least 3 segments
- Text in `<![CDATA[…]]>`, no `{lang}` inside items

See also the official [language PIP](https://docs.woltlab.com/6.2/package/pip/language/) documentation.
