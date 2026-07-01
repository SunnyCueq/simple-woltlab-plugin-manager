# wspackager parity (SWPM)

**[Deutsche Version](WSPACKAGER-PARITY.de.md)**

SWPM implements the **packing logic** of [wspackager](https://github.com/wbbaddons/wspackager) without requiring npm/Node. SWPM validation, TypeScript builds, and store checks remain available.

## Layout folders

| Folder | Produces | wspackager equivalent |
|--------|----------|------------------------|
| `files/` | `files.tar` | Yes — contents of `files/` instead of `lib/`, `acp/`, `style/` |
| `files_wcf/` | `files_wcf.tar` | Yes — instead of `js/` + `lib/bootstrap/` in the repo root |
| `style/style.xml` | `style.tar` | Yes — via `pack-style-tar.sh` |

If `files/` exists, packing uses **only** that folder (not `lib/` in addition). Use either the classic layout or the wspackager layout — do not mix both.

## CLI flags

```bash
./tools/build.sh --json patch          # JSON report on stdout (CI)
./tools/check-pip-sources.py --json    # PIP check as JSON
./tools/check-pip-sources.py --strict-case  # Case-sensitive paths (server parity)
```

`--json` on `build.sh` does not suppress human-readable logs; the JSON block is printed at the end on success (`ok: true`) or via `build_fail` on stderr (`ok: false`).

## What SWPM adds

- `validate-plugin.sh` (store, XSS, languages)
- Git push / release
- TypeScript compilation
- Optional Docker helpers for local ACP installation

wspackager remains useful if you already have an npm-based CI pipeline and only need packaging.
