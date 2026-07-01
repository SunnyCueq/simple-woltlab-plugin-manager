# Sprach-XML (language/*.xml)

**[English version](LANGUAGE-XML.en.md)**

Erkenntnisse aus Plugin-Reviews / WoltLab 6.2.5 — verhindert ACP-Fehler beim Paket-Update.

## Kategorie ↔ Item-Name (Pflicht)

WoltLab prüft beim Import jedes `<item>` gegen die übergeordnete `<category>` (`LanguageEditor::validateItemName`).

**Regel:** Der `name` eines Items muss

- exakt dem Kategorienamen entsprechen, **oder**
- mit `Kategoriename.` beginnen.

```xml
<!-- OK -->
<category name="myapp.topLinks">
  <item name="myapp.topLinks.byViews"><![CDATA[…]]></item>
</category>

<!-- FEHLER beim Update — Item gehört nicht zur Kategorie -->
<category name="myapp.demo">
  <item name="myapp.topLinks.byViews"><![CDATA[…]]></item>
</category>
```

**Symptom im ACP:** `InvalidArgumentException` — *Die Variable „myapp.topLinks.byViews“ gehört nicht zur Kategorie „myapp.demo“.*

**Fix-Optionen:**

1. Item in passende Kategorie verschieben (`myapp.topLinks.*` → `<category name="myapp.topLinks">`).
2. Key umbenennen, wenn er zur Demo-Kategorie gehört (`myapp.demo.topLinks.byViews`).

Der Key-Name in Templates/PHP muss dann mitgezogen werden.

## Prüfung vor dem Build

```bash
python3 tools/check-language-categories.py /pfad/zum/plugin
```

Automatisch in:

- `./tools/build.sh` (bricht Build ab)
- `./tools/validate-plugin.sh` (Fehler, kein Release)

## Weitere Regeln (WoltLab-Doku)

- Kategorie: 2–3 Segmente, alphanumerisch, getrennt durch `.`
- Item: mindestens 3 Segmente
- Text in `<![CDATA[…]]>`, kein `{lang}` innerhalb von Items

Siehe auch [language PIP](../woltlab-docs/docs/package/pip/language.md) im Plugin-Manager.
