"""Rule-based UI copy analysis (no LLM required)."""

from __future__ import annotations

import re
from dataclasses import dataclass, field

from .config import CopywritingConfig
from .sources import LanguageEntry

TECH_PATTERNS = [
    (re.compile(r"\b[A-Z][a-z]+(?:Action|Handler|Cronjob|Listener)\b"), "Interner Klassenname"),
    (re.compile(r"\b[a-z]+_[a-z_]+\b"), "Technischer Bezeichner (snake_case)"),
    (re.compile(r"\b(?:SQL|RegEx|Regex|PDO|API-Key)\b", re.I), "Technischer Jargon"),
    (re.compile(r"\bException\b"), "Technischer Jargon"),
]

EMPTY_DESCRIPTION_KEYS = re.compile(
    r"\.(description|help|hint|tooltip)(\.|$)", re.I
)


@dataclass
class Finding:
    rule: str
    message: str
    severity: str = "warning"  # info | warning | error


@dataclass
class EntryReport:
    key: str
    text: str
    variant: str | None
    category: str | None
    findings: list[Finding] = field(default_factory=list)
    suggested: str | None = None

    @property
    def flagged(self) -> bool:
        return len(self.findings) > 0 or self.suggested is not None


def _word_count(text: str) -> int:
    plain = re.sub(r"<[^>]+>", " ", text)
    return len([w for w in plain.split() if w])


def _key_allowed(key: str, cfg: CopywritingConfig) -> bool:
    if key in cfg.exclude_keys:
        return False
    if cfg.key_prefixes:
        return any(key.startswith(p) for p in cfg.key_prefixes)
    return True


def _glossary_pattern(avoid: str) -> re.Pattern[str]:
    """Längere Phrasen wörtlich; kurze Einträge mit Wortgrenzen."""
    if len(avoid) > 3 or " " in avoid or "-" in avoid:
        return re.compile(re.escape(avoid), re.IGNORECASE)
    return re.compile(rf"\b{re.escape(avoid)}\b", re.IGNORECASE)


def _apply_glossary(
    text: str, cfg: CopywritingConfig, glossary: dict[str, str]
) -> tuple[str | None, list[Finding]]:
    findings: list[Finding] = []
    new_text = text
    changed = False

    # Längere Glossar-Einträge zuerst (z. B. „Hash-Konfiguration“ vor „Hash“)
    pairs = sorted(glossary.items(), key=lambda kv: len(kv[0]), reverse=True)

    for avoid, prefer in pairs:
        if avoid in cfg.glossary_allow:
            continue
        pattern = _glossary_pattern(avoid)
        if pattern.search(new_text):
            findings.append(
                Finding(
                    rule="glossary",
                    message=f"Begriff „{avoid}“ → bevorzugt „{prefer}“",
                    severity="warning",
                )
            )
            replaced = pattern.sub(prefer, new_text)
            if replaced != new_text:
                new_text = replaced
                changed = True

    return (new_text if changed else None), findings


def analyze_entries(
    entries: list[LanguageEntry],
    cfg: CopywritingConfig,
    *,
    glossary: dict[str, str] | None = None,
) -> list[EntryReport]:
    glossary_map = glossary if glossary is not None else cfg.glossary
    reports: list[EntryReport] = []

    for entry in entries:
        if not _key_allowed(entry.key, cfg):
            continue

        text = entry.text
        findings: list[Finding] = []
        suggested: str | None = None

        if not text.strip():
            if EMPTY_DESCRIPTION_KEYS.search(entry.key):
                findings.append(
                    Finding(
                        rule="empty",
                        message="Beschreibung/Hilfetext ist leer",
                        severity="warning",
                    )
                )
            reports.append(
                EntryReport(
                    key=entry.key,
                    text=text,
                    variant=entry.variant,
                    category=entry.category,
                    findings=findings,
                )
            )
            continue

        gloss_suggestion, gloss_findings = _apply_glossary(text, cfg, glossary_map)
        findings.extend(gloss_findings)
        if gloss_suggestion:
            suggested = gloss_suggestion

        wc = _word_count(text)
        is_description = (
            ".description" in entry.key.lower()
            or entry.key.lower().endswith(".help")
            or ".tooltip" in entry.key.lower()
            or ".info." in entry.key.lower()
            or entry.key.lower().endswith(".text")
            or ".instructions" in entry.key.lower()
            or ".note" in entry.key.lower()
            or ".step" in entry.key.lower()
        )
        key_l = entry.key.lower()
        is_message_like = (
            ".error" in key_l
            or ".confirm" in key_l
            or key_l.endswith("confirm")
            or ".message" in key_l
            or ".warning" in key_l
            or ".pagedescription" in key_l
            or ".important" in key_l
            or ".note" in key_l
            or ".instructions" in key_l
            or ".text" in key_l
            or key_l.endswith(".desc")
        )
        is_label = (
            not is_description
            and not is_message_like
            and "herocombined" not in key_l
            and "heroimage" not in key_l
            and "savefirst" not in key_l
            and "noitems" not in key_l
            and not text.strip().startswith("<")
        )

        if is_label and wc > 8:
            findings.append(
                Finding(
                    rule="label-length",
                    message=f"Label sehr lang ({wc} Wörter); Ziel: 2–4 Wörter",
                    severity="warning",
                )
            )

        if is_description and wc < 4 and "category." in entry.key:
            findings.append(
                Finding(
                    rule="description-short",
                    message="Kategorie-Beschreibung sehr kurz; Zweck/Auswirkung ergänzen",
                    severity="info",
                )
            )

        if is_description and wc > 80:
            findings.append(
                Finding(
                    rule="description-long",
                    message=f"Beschreibung sehr lang ({wc} Wörter); kürzen",
                    severity="info",
                )
            )

        for pattern, msg in TECH_PATTERNS:
            if pattern.search(text):
                findings.append(
                    Finding(rule="tech-jargon", message=msg, severity="warning")
                )

        filler = re.compile(
            r"^(Hier (können|kann|legen|finden)|In diesem Bereich)",
            re.I,
        )
        if filler.search(text.strip()):
            findings.append(
                Finding(
                    rule="filler",
                    message="Einleitungsfloskel; direkter formulieren",
                    severity="info",
                )
            )

        if "z.B." in text or "z. B." not in text and re.search(r"\bz\.?\s*B\.", text):
            if "z.B." in text:
                findings.append(
                    Finding(
                        rule="typography",
                        message="„z.B.“ → „z. B.“ (mit Leerzeichen)",
                        severity="info",
                    )
                )

        reports.append(
            EntryReport(
                key=entry.key,
                text=text,
                variant=entry.variant,
                category=entry.category,
                findings=findings,
                suggested=suggested,
            )
        )

    return reports
