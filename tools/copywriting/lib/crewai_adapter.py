"""Optional CrewAI backend (Python <3.14 only)."""

from __future__ import annotations

import sys
from typing import TYPE_CHECKING

if TYPE_CHECKING:
    from .analyzer import EntryReport
    from .llm_agents import LlmSuggestion


def crewai_supported() -> bool:
    if sys.version_info >= (3, 14):
        return False
    try:
        import crewai  # noqa: F401

        return True
    except ImportError:
        return False


def review_with_crewai(
    flagged: list[EntryReport],
    *,
    rules: str,
    context: str,
    locale: str,
    project_name: str,
    batch_size: int,
    max_items: int,
) -> list[LlmSuggestion]:
    """Run a minimal CrewAI crew when the package is installed."""
    from crewai import Agent, Crew, Process, Task

    from .llm_agents import LlmSuggestion, llm_available

    if not llm_available():
        return []

    import json

    from .llm_agents import _parse_json_array

    editor = Agent(
        role="UI Copy Editor",
        goal="Verbessere Nutzertexte für Software-UI klar und konsistent.",
        backstory="Du schreibst präzise UI-Texte für Administratoren und Endnutzer.",
        verbose=False,
    )
    reviewer = Agent(
        role="Terminology Reviewer",
        goal="Prüfe Vorschläge gegen Regeln und Glossar.",
        backstory="Du achtest auf einheitliche Begriffe und verständliche Sprache.",
        verbose=False,
    )

    work = flagged[:max_items]
    results: list[LlmSuggestion] = []

    for i in range(0, len(work), batch_size):
        batch = work[i : i + batch_size]
        payload = json.dumps(
            [
                {
                    "key": r.key,
                    "variant": r.variant,
                    "text": r.text,
                    "issues": [f.message for f in r.findings],
                }
                for r in batch
            ],
            ensure_ascii=False,
        )
        edit_task = Task(
            description=(
                f"Projekt: {project_name}, Locale: {locale}\nRegeln:\n{rules}\n\n"
                f"Kontext:\n{context}\n\nEinträge:\n{payload}\n\n"
                "Antworte mit JSON-Array: key, variant, suggested, reason."
            ),
            expected_output="JSON-Array mit Verbesserungsvorschlägen",
            agent=editor,
        )
        review_task = Task(
            description="Prüfe die Vorschläge des Editors und liefere das finale JSON-Array.",
            expected_output="Bereinigtes JSON-Array",
            agent=reviewer,
            context=[edit_task],
        )
        crew = Crew(
            agents=[editor, reviewer],
            tasks=[edit_task, review_task],
            process=Process.sequential,
            verbose=False,
        )
        raw = str(crew.kickoff())
        for row in _parse_json_array(raw):
            if row.get("suggested") and row.get("suggested") != row.get("original"):
                results.append(
                    LlmSuggestion(
                        key=row["key"],
                        original=row.get("original", ""),
                        suggested=row["suggested"],
                        reason=row.get("reason", ""),
                        variant=row.get("variant"),
                    )
                )

    return results
