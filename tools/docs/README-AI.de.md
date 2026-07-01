# readme-ai (optionale README-Entwürfe)

**[English version](README-AI.md)**

[readme-ai](https://github.com/eli64s/readme-ai) erzeugt strukturierte README-**Entwürfe** aus dem Repository. SWPM nutzt das nur als Ausgangspunkt — veröffentlichte Doku wird manuell geprüft und angepasst.

## Warum nicht nur Offline-Modus?

`--api offline` erzeugt riesige Dateien mit `REPLACE-ME`-Platzhaltern und falschen Tech-Badges, wenn geklonte WoltLab-Quellen im Workspace liegen. Für brauchbare Entwürfe LLM-API oder Ollama verwenden.

## Setup

```bash
python3 -m venv .venv-readmeai
.venv-readmeai/bin/pip install -U readmeai
```

API-Key setzen (Beispiel OpenAI):

```bash
export OPENAI_API_KEY=dein_key
```

Oder [Ollama](https://ollama.com) lokal und `--api ollama`.

## Entwürfe erzeugen

```bash
./tools/docs/generate-readme-ai.sh
```

Ausgabe (gitignored):

- `docs/drafts/README.ai-en.md`
- `docs/drafts/README.ai-de.md`

Prüfen, korrekte Abschnitte in `README.md` / `README.de.md` übernehmen, Entwürfe löschen.

## Ignore-Regeln

`.readmeaiignore` schließt `woltlab-github/`, Beispiel-Plugins und Venvs aus — Analyse fokussiert auf `tools/`.

## Zweisprachiger Workflow

1. Englischen Entwurf erzeugen (System-Message mit Projektfakten).
2. Deutschen Entwurf erzeugen (System-Message: komplett auf Deutsch).
3. Sprachlink oben in jedem finalen README behalten.
4. [tools/docs/README.de.md](README.de.md) bei neuen Guides aktualisieren.
