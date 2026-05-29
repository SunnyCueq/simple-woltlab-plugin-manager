"""Multi-agent LLM review (OpenAI-compatible API, no CrewAI required)."""

from __future__ import annotations

import json
import os
import re
import urllib.error
import urllib.request
from dataclasses import dataclass
from typing import Any

from .analyzer import EntryReport


@dataclass
class LlmSuggestion:
    key: str
    original: str
    suggested: str
    reason: str
    variant: str | None = None


def llm_available() -> bool:
    return bool(os.environ.get("OPENAI_API_KEY") or os.environ.get("COPYWRITING_API_KEY"))


def _api_base() -> str:
    return os.environ.get("OPENAI_BASE_URL", "https://api.openai.com/v1").rstrip("/")


def _api_key() -> str:
    return os.environ.get("COPYWRITING_API_KEY") or os.environ.get("OPENAI_API_KEY") or ""


def _model() -> str:
    return os.environ.get("COPYWRITING_MODEL", os.environ.get("OPENAI_MODEL", "gpt-4o-mini"))


def _chat(messages: list[dict[str, str]], temperature: float = 0.3) -> str:
    payload = {
        "model": _model(),
        "messages": messages,
        "temperature": temperature,
    }
    req = urllib.request.Request(
        f"{_api_base()}/chat/completions",
        data=json.dumps(payload).encode("utf-8"),
        headers={
            "Content-Type": "application/json",
            "Authorization": f"Bearer {_api_key()}",
        },
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=120) as resp:
            data = json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="replace")
        raise RuntimeError(f"LLM API Fehler {e.code}: {body}") from e

    return data["choices"][0]["message"]["content"]


def _parse_json_array(raw: str) -> list[dict[str, Any]]:
    raw = raw.strip()
    fence = re.search(r"```(?:json)?\s*([\s\S]*?)```", raw)
    if fence:
        raw = fence.group(1).strip()
    start = raw.find("[")
    end = raw.rfind("]")
    if start >= 0 and end > start:
        raw = raw[start : end + 1]
    return json.loads(raw)


def run_editor_agent(
    batch: list[EntryReport],
    rules: str,
    context: str,
    locale: str,
    project_name: str,
) -> list[LlmSuggestion]:
    items = [
        {
            "key": r.key,
            "variant": r.variant,
            "text": r.text,
            "issues": [f.message for f in r.findings],
        }
        for r in batch
    ]
    system = (
        "Du bist ein erfahrener UI-Copy-Editor. "
        "Du verbesserst Nutzertexte für Software-Oberflächen. "
        "Antworte NUR mit gültigem JSON (Array)."
    )
    user = f"""Projekt: {project_name}
Locale: {locale}

Regeln:
{rules}

{f"Kontext:{chr(10)}{context}" if context else ""}

Einträge (JSON):
{json.dumps(items, ensure_ascii=False, indent=2)}

Gib ein JSON-Array zurück. Pro Eintrag:
- key (string, unverändert)
- variant (string|null)
- suggested (string, verbesserter Text)
- reason (string, kurz, Deutsch)

Wichtig:
- Platzhalter, HTML und Markup unverändert lassen.
- Leere suggested nur wenn Text schon optimal ist (dann suggested = original).
- Keine erfundenen Features."""

    content = _chat(
        [
            {"role": "system", "content": system},
            {"role": "user", "content": user},
        ]
    )
    parsed = _parse_json_array(content)
    out: list[LlmSuggestion] = []
    for row in parsed:
        key = row.get("key")
        if not key:
            continue
        orig = next((b.text for b in batch if b.key == key and b.variant == row.get("variant")), "")
        suggested = row.get("suggested", orig)
        if suggested == orig:
            continue
        out.append(
            LlmSuggestion(
                key=key,
                original=orig,
                suggested=suggested,
                reason=row.get("reason", ""),
                variant=row.get("variant"),
            )
        )
    return out


def run_reviewer_agent(
    suggestions: list[LlmSuggestion],
    rules: str,
    glossary: dict[str, str],
) -> list[LlmSuggestion]:
    if not suggestions:
        return []

    system = (
        "Du bist Lektor und prüfst UI-Text-Vorschläge auf Klarheit, Glossar und Regeln. "
        "Antworte NUR mit gültigem JSON (Array)."
    )
    user = f"""Regeln:
{rules}

Glossar (vermeiden → bevorzugen):
{json.dumps(glossary, ensure_ascii=False, indent=2)}

Vorschläge:
{json.dumps([s.__dict__ for s in suggestions], ensure_ascii=False, indent=2)}

Gib dasselbe Array zurück, ggf. mit korrigiertem suggested und reason.
Entferne Einträge, bei denen original bereits optimal ist."""

    content = _chat(
        [
            {"role": "system", "content": system},
            {"role": "user", "content": user},
        ],
        temperature=0.2,
    )
    parsed = _parse_json_array(content)
    out: list[LlmSuggestion] = []
    for row in parsed:
        key = row.get("key")
        suggested = row.get("suggested")
        if not key or not suggested or suggested == row.get("original"):
            continue
        out.append(
            LlmSuggestion(
                key=key,
                original=row.get("original", ""),
                suggested=suggested,
                reason=row.get("reason", ""),
                variant=row.get("variant"),
            )
        )
    return out or suggestions


def review_with_llm(
    flagged: list[EntryReport],
    *,
    rules: str,
    context: str,
    locale: str,
    project_name: str,
    glossary: dict[str, str],
    batch_size: int,
    max_items: int,
) -> list[LlmSuggestion]:
    if not llm_available():
        return []

    work = flagged[:max_items]
    all_suggestions: list[LlmSuggestion] = []

    for i in range(0, len(work), batch_size):
        batch = work[i : i + batch_size]
        edited = run_editor_agent(batch, rules, context, locale, project_name)
        reviewed = run_reviewer_agent(edited, rules, glossary)
        all_suggestions.extend(reviewed)

    return all_suggestions
