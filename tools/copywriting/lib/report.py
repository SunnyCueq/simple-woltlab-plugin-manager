"""Write review reports (Markdown + JSON)."""

from __future__ import annotations

import json
from dataclasses import asdict
from datetime import datetime, timezone
from pathlib import Path

from .analyzer import EntryReport
from .llm_agents import LlmSuggestion


def write_reports(
    output_dir: Path,
    *,
    locale: str,
    project_name: str,
    rule_reports: list[EntryReport],
    llm_suggestions: list[LlmSuggestion],
    meta: dict,
) -> tuple[Path, Path]:
    output_dir.mkdir(parents=True, exist_ok=True)
    ts = datetime.now(timezone.utc).strftime("%Y%m%d-%H%M%S")
    md_path = output_dir / f"review-{locale}-{ts}.md"
    json_path = output_dir / f"review-{locale}-{ts}.json"

    flagged = [r for r in rule_reports if r.flagged]
    glossary_auto = [r for r in rule_reports if r.suggested and r.suggested != r.text]

    lines = [
        f"# Copywriting-Review: {project_name}",
        f"",
        f"- **Locale:** {locale}",
        f"- **Zeit (UTC):** {ts}",
        f"- **Einträge gesamt:** {len(rule_reports)}",
        f"- **Mit Befund:** {len(flagged)}",
        f"- **LLM-Vorschläge:** {len(llm_suggestions)}",
        f"- **Glossar-Auto:** {len(glossary_auto)}",
        f"",
        "## Meta",
        f"",
        "```json",
        json.dumps(meta, indent=2, ensure_ascii=False),
        "```",
        f"",
    ]

    if llm_suggestions:
        lines.append("## LLM-Vorschläge (Editor + Lektor)")
        lines.append("")
        lines.append("| Key | Original | Vorschlag | Grund |")
        lines.append("|-----|----------|-----------|-------|")
        for s in llm_suggestions:
            o = s.original.replace("|", "\\|").replace("\n", " ")
            n = s.suggested.replace("|", "\\|").replace("\n", " ")
            r = s.reason.replace("|", "\\|")
            v = s.variant or ""
            key = f"{s.key}" + (f' variant="{v}"' if v else "")
            lines.append(f"| `{key}` | {o} | {n} | {r} |")
        lines.append("")

    if glossary_auto:
        lines.append("## Glossar (regelbasiert)")
        lines.append("")
        for r in glossary_auto:
            lines.append(f"### `{r.key}`")
            if r.variant:
                lines.append(f"- variant: `{r.variant}`")
            lines.append(f"- **Alt:** {r.text}")
            lines.append(f"- **Neu:** {r.suggested}")
            lines.append("")

    lines.append("## Regel-Befunde")
    lines.append("")
    for r in flagged:
        if llm_suggestions and any(s.key == r.key for s in llm_suggestions):
            continue
        lines.append(f"### `{r.key}`")
        if r.variant:
            lines.append(f"- variant: `{r.variant}`")
        if r.category:
            lines.append(f"- category: `{r.category}`")
        lines.append(f"- **Text:** {r.text[:500]}")
        for f in r.findings:
            lines.append(f"- [{f.severity}] **{f.rule}:** {f.message}")
        if r.suggested and r.suggested != r.text:
            lines.append(f"- **Vorschlag (Glossar):** {r.suggested}")
        lines.append("")

    md_path.write_text("\n".join(lines), encoding="utf-8")

    payload = {
        "meta": meta,
        "locale": locale,
        "rule_reports": [
            {
                "key": r.key,
                "text": r.text,
                "variant": r.variant,
                "category": r.category,
                "findings": [asdict(f) for f in r.findings],
                "suggested": r.suggested,
            }
            for r in rule_reports
            if r.flagged
        ],
        "llm_suggestions": [asdict(s) for s in llm_suggestions],
    }
    json_path.write_text(json.dumps(payload, indent=2, ensure_ascii=False), encoding="utf-8")

    return md_path, json_path
