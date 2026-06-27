# Copywriting Review (global)

UI-Texte in Sprachdateien prüfen und verbessern – **projektübergreifend**, nicht nur WoltLab.

## Schnellstart

```bash
# Aus plugin-manager/
./tools/copywriting/run.sh --project basis-plugin

# Nur Regeln (ohne API-Key, schnell)
./tools/copywriting/run.sh --project basis-plugin --mode rules

# Mit LLM (OpenAI-kompatibel)
export OPENAI_API_KEY=sk-...
./tools/copywriting/run.sh --project basis-plugin --mode full

# CrewAI (optional, nur Python 3.10–3.13)
./tools/copywriting/run.sh --project basis-plugin --backend crewai
```

Über `./tools.sh copywriting …` (siehe `tools/tools.sh`).

## Projekt einrichten

1. `copywriting.yml.example` nach **Projektroot** kopieren: `.copywriting.yml`
2. `languages`, `glossary`, optional `project_rules` und `context_files` anpassen
3. Ohne Config: Auto-Discovery für `temp_edit/language/de.xml` und `en.xml`

## Unterstützte Formate

| `format` | Dateien |
|----------|---------|
| `woltlab-xml` | WoltLab `language/*.xml` |
| `json-flat` | Flaches oder verschachteltes JSON |
| `java-properties` | `.properties` |
| `markdown-blocks` | Markdown mit ` ```key``` ` (wie Vale-Export) |

## Ohne API-Key (manueller Review-Workflow)

1. **Regel-Review:** `./tools.sh copywriting --project basis-plugin --mode rules`
2. **Report lesen:** `copywriting-output/review-de-*.md`
3. **Texte im Editor überarbeiten** – z. B. Block `wcf.acp.option.*` in `de.xml` / `en.xml`
4. **Sichere Glossar-Fixes anwenden:** `./tools.sh copywriting:apply --project basis-plugin --dry-run` (Vorschau), dann ohne `--dry-run`
5. **Optional Vale:** `python3 temp_edit/language/extract_for_vale.py` + `vale …`

Der LLM-Teil (`--mode full`) ist optional und braucht `OPENAI_API_KEY`. Für Plugin-Texte reicht oft Regel-Review plus manuelle Überarbeitung.

## Ausgabe

`copywriting-output/review-{locale}-{timestamp}.md` und `.json` – **kein automatisches Überschreiben** der Sprachdateien. Vorschläge manuell im Editor übernehmen.

## LLM

- Standard: Zwei-Agenten-Ablauf (Editor + Lektor) über **OpenAI-kompatible API** (`urllib`, keine großen Abhängigkeiten).
- Env: `OPENAI_API_KEY` oder `COPYWRITING_API_KEY`, optional `OPENAI_BASE_URL`, `COPYWRITING_MODEL` (Default: `gpt-4o-mini`).
- **CrewAI:** nur mit Python &lt; 3.14 und `pip install crewai`; unter Python 3.14 automatisch Fallback auf OpenAI-Agenten.

## Shr1nkr / basis-plugin

Siehe `basis-plugin/.copywriting.yml` – inkl. Glossar und Projektregeln aus `temp_edit/language/`.
