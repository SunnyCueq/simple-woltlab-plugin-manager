#!/usr/bin/env python3
"""
Global UI copy review for language files (WoltLab XML, JSON, properties, …).

  ./tools/copywriting/run.sh --project basis-plugin
  ./tools/copywriting/run.sh --project basis-plugin --mode rules
  ./tools/copywriting/run.sh --project basis-plugin --mode llm --backend crewai
"""

from __future__ import annotations

import argparse
import os
import sys
from pathlib import Path

TOOLS_COPYWRITING = Path(__file__).resolve().parent
if str(TOOLS_COPYWRITING) not in sys.path:
    sys.path.insert(0, str(TOOLS_COPYWRITING))

from lib.analyzer import analyze_entries  # noqa: E402
from lib.config import (  # noqa: E402
    CopywritingConfig,
    glossary_for_locale,
    load_config,
    load_context_snippet,
    load_rules_text,
)
from lib.crewai_adapter import crewai_supported, review_with_crewai  # noqa: E402
from lib.llm_agents import llm_available, review_with_llm  # noqa: E402
from lib.report import write_reports  # noqa: E402
from lib.sources import load_language_file  # noqa: E402


def _load_dotenv() -> None:
    tools_dir = TOOLS_COPYWRITING.parent
    for env_path in (tools_dir / ".env", TOOLS_COPYWRITING / ".env"):
        if not env_path.is_file():
            continue
        for line in env_path.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, _, value = line.partition("=")
            key = key.strip()
            value = value.strip().strip('"').strip("'")
            if key and key not in os.environ:
                os.environ[key] = value


def process_locale(
    cfg: CopywritingConfig,
    locale: str,
    lang_path: Path,
    fmt: str,
    *,
    mode: str,
    backend: str,
) -> tuple[Path, Path]:
    entries = load_language_file(lang_path, fmt)
    reports = analyze_entries(
        entries, cfg, glossary=glossary_for_locale(cfg, locale)
    )

    flagged = [r for r in reports if r.flagged]
    llm_targets = flagged if cfg.llm_only_flagged else reports

    rules = load_rules_text(cfg)
    context = load_context_snippet(cfg)
    llm_suggestions = []

    if mode in ("llm", "full"):
        if not llm_available():
            print(
                f"  [{locale}] LLM übersprungen: OPENAI_API_KEY oder COPYWRITING_API_KEY fehlt.",
                file=sys.stderr,
            )
        elif backend == "crewai":
            if crewai_supported():
                llm_suggestions = review_with_crewai(
                    llm_targets,
                    rules=rules,
                    context=context,
                    locale=locale,
                    project_name=cfg.project_name,
                    batch_size=cfg.llm_batch_size,
                    max_items=cfg.llm_max_items,
                )
            else:
                print(
                    f"  [{locale}] CrewAI nicht verfügbar (Python 3.14 oder Paket fehlt). "
                    "Fallback: OpenAI-API-Agenten.",
                    file=sys.stderr,
                )
                llm_suggestions = review_with_llm(
                    llm_targets,
                    rules=rules,
                    context=context,
                    locale=locale,
                    project_name=cfg.project_name,
                    glossary=glossary_for_locale(cfg, locale),
                    batch_size=cfg.llm_batch_size,
                    max_items=cfg.llm_max_items,
                )
        else:
            llm_suggestions = review_with_llm(
                llm_targets,
                rules=rules,
                context=context,
                locale=locale,
                project_name=cfg.project_name,
                glossary=cfg.glossary,
                batch_size=cfg.llm_batch_size,
                max_items=cfg.llm_max_items,
            )

    meta = {
        "mode": mode,
        "backend": backend,
        "language_file": str(lang_path),
        "llm_available": llm_available(),
        "crewai_supported": crewai_supported(),
        "llm_only_flagged": cfg.llm_only_flagged,
        "entries_total": len(reports),
        "entries_flagged": len(flagged),
    }

    return write_reports(
        cfg.output_dir,
        locale=locale,
        project_name=cfg.project_name,
        rule_reports=reports,
        llm_suggestions=llm_suggestions,
        meta=meta,
    )


def main() -> int:
    _load_dotenv()

    parser = argparse.ArgumentParser(description="Global UI copy review")
    parser.add_argument(
        "--project",
        type=Path,
        required=True,
        help="Projektroot (Ordner mit .copywriting.yml oder Sprachdateien)",
    )
    parser.add_argument("--config", type=Path, help="Pfad zu .copywriting.yml")
    parser.add_argument(
        "--mode",
        choices=("rules", "llm", "full"),
        default="full",
        help="rules=nur Regeln; llm=nur LLM; full=beides (Standard)",
    )
    parser.add_argument(
        "--backend",
        choices=("openai", "crewai"),
        default="openai",
        help="LLM-Backend (crewai nur Python <3.14)",
    )
    parser.add_argument("--locale", help="Nur diese Locale verarbeiten (z. B. de)")
    args = parser.parse_args()

    project_root = args.project.resolve()
    if not project_root.is_dir():
        print(f"Projekt nicht gefunden: {project_root}", file=sys.stderr)
        return 1

    try:
        cfg = load_config(project_root, args.config)
    except FileNotFoundError as e:
        print(e, file=sys.stderr)
        return 1

    if not cfg.languages:
        print("Keine Sprachdateien konfiguriert.", file=sys.stderr)
        return 1

    print(f"Copywriting-Review: {cfg.project_name}")
    print(f"  Projekt:  {cfg.project_root}")
    print(f"  Ausgabe:  {cfg.output_dir}")
    print(f"  Modus:    {args.mode}  |  Backend: {args.backend}")
    print(f"  Regeln:   {len(cfg.rules_files) + len(cfg.project_rules)} Datei(en)")
    print(f"  Glossar:  {len(cfg.glossary)} Einträge")
    print()

    written: list[Path] = []
    for lang in cfg.languages:
        if args.locale and lang.locale != args.locale:
            continue
        if not lang.path.is_file():
            print(f"  Übersprungen (fehlt): {lang.path}", file=sys.stderr)
            continue
        print(f"  Verarbeite {lang.locale}: {lang.path.name} …")
        md_path, json_path = process_locale(
            cfg,
            lang.locale,
            lang.path,
            lang.format,
            mode=args.mode,
            backend=args.backend,
        )
        written.extend([md_path, json_path])
        print(f"    → {md_path.name}")

    if not written:
        print("Nichts verarbeitet.", file=sys.stderr)
        return 1

    print()
    print(f"Fertig. {len(written)} Datei(en) in {cfg.output_dir}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
