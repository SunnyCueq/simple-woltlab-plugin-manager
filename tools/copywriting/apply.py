"""Apply safe, deterministic copy fixes from a review JSON to language XML."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

TOOLS_COPYWRITING = Path(__file__).resolve().parent
if str(TOOLS_COPYWRITING) not in sys.path:
    sys.path.insert(0, str(TOOLS_COPYWRITING))

from lib.config import CopywritingConfig, LanguageSource, load_config
from lib.sources import load_language_file


def _item_pattern(key: str, variant: str | None) -> re.Pattern[str]:
    if variant:
        return re.compile(
            rf'(<item\s+name="{re.escape(key)}"\s+variant="{re.escape(variant)}"[^>]*>\s*<!\[CDATA\[)(.*?)(\]\]>\s*</item>)',
            re.DOTALL,
        )
    return re.compile(
        rf'(<item\s+name="{re.escape(key)}"(?:\s+variant="[^"]*")?[^>]*>\s*<!\[CDATA\[)(.*?)(\]\]>\s*</item>)',
        re.DOTALL,
    )


def _is_safe_replacement(original: str, suggested: str) -> bool:
    if not suggested or suggested == original:
        return False
    if "{#" in original or "{link" in original or "<a " in original:
        return False
    if len(original) > 400:
        return False
    # Platzhalter müssen erhalten bleiben
    for token in re.findall(r"\{[^}]+\}", original):
        if token not in suggested:
            return False
    return True


def apply_glossary_from_review(
    lang: LanguageSource,
    review_path: Path,
    *,
    dry_run: bool = False,
) -> int:
    data = json.loads(review_path.read_text(encoding="utf-8"))
    if data.get("locale") and data["locale"] != lang.locale:
        return 0

    xml_path = lang.path
    content = xml_path.read_text(encoding="utf-8")
    applied = 0

    for row in data.get("rule_reports", []):
        suggested = row.get("suggested")
        original = row.get("text", "")
        if not _is_safe_replacement(original, suggested):
            continue

        key = row["key"]
        variant = row.get("variant")
        pattern = _item_pattern(key, variant)
        match = pattern.search(content)
        if not match or match.group(2) != original:
            continue

        content = (
            content[: match.start(2)]
            + suggested
            + content[match.end(2) :]
        )
        applied += 1
        print(f"  + {key}" + (f' (variant={variant})' if variant else ""))

    if applied and not dry_run:
        xml_path.write_text(content, encoding="utf-8")

    return applied


def main(argv: list[str] | None = None) -> int:
    import argparse

    parser = argparse.ArgumentParser(description="Apply safe glossary fixes from review JSON")
    parser.add_argument("--project", type=Path, required=True)
    parser.add_argument("--config", type=Path)
    parser.add_argument("--review", type=Path, help="review-XX.json (default: latest)")
    parser.add_argument("--locale", action="append", help="Locale(s), default: all in config")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args(argv)

    cfg = load_config(args.project.resolve(), args.config)
    total = 0

    for lang in cfg.languages:
        if args.locale and lang.locale not in args.locale:
            continue
        if args.review:
            review_path = args.review
        else:
            candidates = sorted(
                cfg.output_dir.glob(f"review-{lang.locale}-*.json"),
                key=lambda p: p.stat().st_mtime,
            )
            if not candidates:
                print(f"Kein Review für {lang.locale} in {cfg.output_dir}", file=sys.stderr)
                continue
            review_path = candidates[-1]

        print(f"{lang.locale}: {review_path.name}")
        total += apply_glossary_from_review(lang, review_path, dry_run=args.dry_run)

    print(f"\n{'Dry-run: ' if args.dry_run else ''}{total} sichere Änderung(en).")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
