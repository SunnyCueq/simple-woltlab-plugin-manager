# Family demo fixture (SWPM)

Minimal product line for smoke tests — not a real plugin.

- `swpm-family.json` — happy path (core + addon with requiredpackage)
- `swpm-family.negative-orphan.json` — two islands (expect fail)
- `swpm-family.negative-missing.json` — missing family dep (expect fail)

```bash
python3 tools/check-family-deps.py --manifest tools/fixtures/family-demo/swpm-family.json --mode order
./tools/swpm-family.sh --manifest tools/fixtures/family-demo/swpm-family.json check
```
