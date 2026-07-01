# readme-ai (optional README drafts)

**[Deutsche Version](README-AI.de.md)**

[readme-ai](https://github.com/eli64s/readme-ai) can generate structured README **drafts** from the repository. SWPM uses it as a starting point only — published docs are reviewed and edited manually.

## Why not offline mode alone?

`--api offline` produces large files with `REPLACE-ME` placeholders and wrong tech badges when the workspace contains cloned WoltLab sources. Use an LLM API or Ollama for usable drafts.

## Setup

```bash
python3 -m venv .venv-readmeai
.venv-readmeai/bin/pip install -U readmeai
```

Set an API key (example OpenAI):

```bash
export OPENAI_API_KEY=your_key
```

Or run [Ollama](https://ollama.com) locally and use `--api ollama`.

## Generate drafts

```bash
./tools/docs/generate-readme-ai.sh
```

Outputs (gitignored):

- `docs/drafts/README.ai-en.md`
- `docs/drafts/README.ai-de.md`

Review, merge accurate sections into `README.md` / `README.de.md`, and delete drafts.

## Ignore rules

`.readmeaiignore` excludes `woltlab-github/`, plugin sample dirs, and venvs so analysis focuses on `tools/`.

## Bilingual workflow

1. Generate English draft (`--system-message` with project facts).
2. Generate German draft (system message: write entirely in German).
3. Keep language links at the top of each final README.
4. Update [tools/docs/README.md](README.md) when adding new guides.
