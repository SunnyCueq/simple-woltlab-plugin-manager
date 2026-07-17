# Sprach-XML (language/*.xml)

Damit Übersetzungen beim Paket-Update und zur Laufzeit greifen. Typische Stolperfallen aus Reviews und WoltLab 6.2.x.

## Kategorie ↔ Item-Name (Pflicht)

Beim Import prüft WoltLab jedes `<item>` gegen die übergeordnete `<category>` (`LanguageEditor::validateItemName`).

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

**Symptom zur Laufzeit (ohne PIP-Fehler):** Rohe Keys wie `myapp.acp.link.tab.core` im ACP, obwohl `language/*.xml` und sogar DB-Einträge existieren.

**Ursache Laufzeit:** `Language::get($key)` lädt die Sprach-PHP-Datei anhand der **ersten drei Segmente** des Keys (`myapp.acp.link` bei `myapp.acp.link.tab.core`), nicht anhand einer beliebigen XML-Kategorie. Steht das Item in `<category name="myapp.acp.url">`, wird die Datei `1_myapp.acp.link.php` beim Lookup nicht mit diesem Item befüllt.

**Fix-Optionen:**

1. Item in passende Kategorie verschieben (`myapp.topLinks.*` → `<category name="myapp.topLinks">`).
2. Key umbenennen, wenn er zur Demo-Kategorie gehört (`myapp.demo.topLinks.byViews`).

Der Key-Name in Templates/PHP muss dann mitgezogen werden.

## Anrede Sie / Du (Deutsch)

WoltLab kennt **förmlich und informell** in einem String:

```xml
<item name="myapp.action.save"><![CDATA[{if LANGUAGE_USE_INFORMAL_VARIANT}Speichere{else}Speichern Sie{/if}]]></item>
```

Nicht: `variant="informal"` am `<item>` — das gibt es in der language.xsd nicht (`check-language-integrity.py`).

**Ton-Konsistenz:** `check-language-address.py` warnt heuristisch, wenn `de.xml` klar **Sie** und **Du** mischt (ohne Varianten-IF). Kein Build-Abbruch — nur Warnung. Manuell prüfen und vereinheitlichen.

```bash
python3 tools/check-language-address.py /pfad/zum/plugin
```

## Implizite PIP-Sprachkeys (Optionen / Gruppenrechte / ACP-Menü)

`option.xml`, `userGroupOption.xml` und `acpMenu.xml` erzeugen Sprachkeys **aus den `name`-Attributen** — sie stehen oft **nirgends im PHP/Template-Code**. `check-language-keys.py` (Code-Nutzung) findet sie deshalb nicht.

Beispiele (laut WoltLab-Doku):

| PIP | Key-Muster |
|-----|------------|
| `userGroupOption` Kategorie `user.foo` | `wcf.acp.group.option.category.user.foo` |
| `userGroupOption` Option `user.foo.canBar` | `wcf.acp.group.option.user.foo.canBar` |
| `option` Kategorie / Option | `wcf.acp.option.category.…` / `wcf.acp.option.…` |
| `acpMenu` | Key = `name` des Menüpunkts (z. B. `myapp.acp.menu.link.…`) |

Fehlt der Key, zeigt das ACP den **rohen Key** (z. B. als Tab-Titel in den Gruppenrechten).

```bash
python3 tools/check-language-pip-keys.py /pfad/zum/plugin
```

Registry: `language-pip-keys` (**warn**). Wer eine bereits im Core existierende Kategorie erneut deklariert, kann einen False Positive bekommen — Kategorien nur anlegen, wenn das Plugin sie wirklich einführt.

## Prüfung vor dem Build

```bash
python3 tools/check-language-categories.py /pfad/zum/plugin
python3 tools/check-language-pip-keys.py /pfad/zum/plugin
```

Automatisch in:

- `./tools/build.sh` (bricht Build ab)
- `./tools/validate-plugin.sh` (Fehler, kein Release)

**Nach manuellem Sprach-Import** (`post_update`, Hotfix-SQL): zusätzlich `LanguageCacheBuilder::getInstance()->reset()` und `LanguageEditor::updateCategory()` für jede betroffene Kategorie und Sprache — sonst bleiben alte PHP-Sprachdateien aktiv.

## Weitere Regeln (WoltLab-Doku)

- Kategorie: 2–3 Segmente, alphanumerisch, getrennt durch `.`
- Item: mindestens 3 Segmente
- Text in `<![CDATA[…]]>`, kein `{lang}` innerhalb von Items

Siehe auch die offizielle [language PIP](https://docs.woltlab.com/6.2/package/pip/language/)-Dokumentation.
