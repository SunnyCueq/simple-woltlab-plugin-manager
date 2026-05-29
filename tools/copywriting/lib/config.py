"""Load .copywriting.yml and merge with defaults."""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

try:
    import yaml
except ImportError:
    yaml = None  # type: ignore


@dataclass
class LanguageSource:
    path: Path
    locale: str
    format: str = "woltlab-xml"


@dataclass
class CopywritingConfig:
    project_root: Path
    project_name: str = "Project"
    output_dir: Path = field(default_factory=lambda: Path("copywriting-output"))
    languages: list[LanguageSource] = field(default_factory=list)
    rules_files: list[Path] = field(default_factory=list)
    project_rules: list[Path] = field(default_factory=list)
    context_files: list[Path] = field(default_factory=list)
    key_prefixes: list[str] = field(default_factory=list)
    exclude_keys: list[str] = field(default_factory=list)
    glossary: dict[str, str] = field(default_factory=dict)
    glossary_de: dict[str, str] = field(default_factory=dict)
    glossary_en: dict[str, str] = field(default_factory=dict)
    glossary_allow: list[str] = field(default_factory=list)
    llm_only_flagged: bool = True
    llm_batch_size: int = 12
    llm_max_items: int = 250


def _resolve(project_root: Path, value: str | Path) -> Path:
    p = Path(value)
    return p if p.is_absolute() else (project_root / p).resolve()


def load_config(project_root: Path, config_path: Path | None = None) -> CopywritingConfig:
    project_root = project_root.resolve()
    cfg_path = config_path or (project_root / ".copywriting.yml")
    data: dict[str, Any] = {}

    if cfg_path.is_file():
        raw = cfg_path.read_text(encoding="utf-8")
        if yaml is not None:
            data = yaml.safe_load(raw) or {}
        else:
            raise RuntimeError("PyYAML fehlt. Bitte: pip install pyyaml")
    else:
        data = _autodiscover_config_data(project_root)

    tools_dir = Path(__file__).resolve().parent.parent
    default_rules = tools_dir / "rules" / "default.md"

    languages: list[LanguageSource] = []
    for entry in data.get("languages") or []:
        languages.append(
            LanguageSource(
                path=_resolve(project_root, entry["path"]),
                locale=entry.get("locale", "de"),
                format=entry.get("format", "woltlab-xml"),
            )
        )

    rules_files = [
        _resolve(project_root, p) for p in (data.get("rules_files") or [])
    ]
    if not rules_files and default_rules.is_file():
        rules_files = [default_rules]

    project_rules = [
        _resolve(project_root, p) for p in (data.get("project_rules") or [])
    ]

    context_files = [
        _resolve(project_root, p) for p in (data.get("context_files") or [])
    ]

    output_dir = _resolve(project_root, data.get("output_dir", "copywriting-output"))

    return CopywritingConfig(
        project_root=project_root,
        project_name=data.get("project_name", project_root.name),
        output_dir=output_dir,
        languages=languages,
        rules_files=rules_files,
        project_rules=project_rules,
        context_files=context_files,
        key_prefixes=list(data.get("key_prefixes") or []),
        exclude_keys=list(data.get("exclude_keys") or []),
        glossary=dict(data.get("glossary") or {}),
        glossary_de=dict(data.get("glossary_de") or {}),
        glossary_en=dict(data.get("glossary_en") or {}),
        glossary_allow=list(data.get("glossary_allow") or []),
        llm_only_flagged=bool(data.get("llm_only_flagged", True)),
        llm_batch_size=int(data.get("llm_batch_size", 12)),
        llm_max_items=int(data.get("llm_max_items", 250)),
    )


def _autodiscover_config_data(project_root: Path) -> dict[str, Any]:
    """Find common language file layouts without .copywriting.yml."""
    candidates: list[tuple[str, Path]] = []
    for pattern in (
        "temp_edit/language/de.xml",
        "language/de.xml",
        "lang/de.xml",
        "resources/lang/de.xml",
        "locales/de.xml",
    ):
        p = project_root / pattern
        if p.is_file():
            candidates.append(("de", p))

    for pattern in (
        "temp_edit/language/en.xml",
        "language/en.xml",
        "lang/en.xml",
        "resources/lang/en.xml",
        "locales/en.xml",
    ):
        p = project_root / pattern
        if p.is_file():
            candidates.append(("en", p))

    if not candidates:
        raise FileNotFoundError(
            f"Keine .copywriting.yml und keine Sprachdateien unter {project_root}"
        )

    return {
        "project_name": project_root.name,
        "languages": [
            {"path": str(p.relative_to(project_root)), "locale": loc, "format": "woltlab-xml"}
            for loc, p in candidates
        ],
    }


def glossary_for_locale(cfg: CopywritingConfig, locale: str) -> dict[str, str]:
    merged = dict(cfg.glossary)
    loc = locale.lower().split("-")[0]
    if loc == "de":
        merged.update(cfg.glossary_de)
    elif loc == "en":
        merged.update(cfg.glossary_en)
    else:
        merged.update(cfg.glossary_de)
        merged.update(cfg.glossary_en)
    return merged


def load_rules_text(cfg: CopywritingConfig) -> str:
    parts: list[str] = []
    for path in cfg.rules_files + cfg.project_rules:
        if path.is_file():
            parts.append(f"## {path.name}\n\n{path.read_text(encoding='utf-8')}")
    return "\n\n---\n\n".join(parts) if parts else ""


def load_context_snippet(cfg: CopywritingConfig, max_chars: int = 12000) -> str:
    chunks: list[str] = []
    total = 0
    for path in cfg.context_files:
        if not path.is_file():
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        if total + len(text) > max_chars:
            text = text[: max_chars - total]
        chunks.append(f"### {path.name}\n```\n{text}\n```")
        total += len(text)
        if total >= max_chars:
            break
    return "\n\n".join(chunks)


def config_to_json(cfg: CopywritingConfig) -> str:
    payload = {
        "project_name": cfg.project_name,
        "project_root": str(cfg.project_root),
        "output_dir": str(cfg.output_dir),
        "languages": [
            {"path": str(l.path), "locale": l.locale, "format": l.format}
            for l in cfg.languages
        ],
    }
    return json.dumps(payload, indent=2, ensure_ascii=False)
