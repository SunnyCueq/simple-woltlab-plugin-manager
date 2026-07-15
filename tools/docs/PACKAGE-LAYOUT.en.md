# Package source layout (SWPM)

**[Deutsche Version](PACKAGE-LAYOUT.de.md)**

SWPM builds an installable archive from your plugin’s source tree (`temp_edit/` or the plugin root). Validation, TypeScript builds, and store checks stay part of the normal workflow.

## Layout folders

| Folder | Produces | Meaning |
|--------|----------|---------|
| `templates/` | `templates.tar` | **Canonical** — put frontend templates (`.tpl`) here |
| `acptemplates/` | `acptemplates.tar` | Templates for the Admin Control Panel (ACP) |
| `files/` | `files.tar` | Contents of `files/` instead of `lib/`, `acp/`, `style/` in the root |
| `files_wcf/` | `files_wcf.tar` | Files for the WCF directory instead of `js/` + `lib/bootstrap/` in the root |
| `style/style.xml` | `style.tar` / `style.tgz` / `style.tar.gz` | Style PIP — archive name from `package.xml` (`pack-style-tar.sh`) |

### Style packages (theme-only)

Sources under `style/`:

| Path | Role |
|------|------|
| `style/style.xml` | Metadata and references to variables/images/templates |
| `style/variables.xml` | Style variables (WoltLab builds CSS from these) |
| `style/variables_dark.xml` | optional dark mode |
| `style/images/` | packed as `images.tar` |
| `style/templates/` | packed as `templates.tar` |
| Preview/cover images | as named in `<image>` / `<coverPhoto>` |

In `package.xml`, e.g. `<instruction type="style">style.tgz</instruction>` — SWPM writes **that** filename.

**scssphp:** Not part of SWPM. The suite compiles SCSS from variables at install/runtime. Local `.scss` under `style/` is not compiled by SWPM (the checker may warn). App plugins with their own CSS use `style/` plus `check-style-assets.py` (resolvable `url(...)`).

**Frontend templates:** The source is `templates/*.tpl`. If `.tpl` files still sit in the root, SWPM keeps packing them (legacy fallback) and warns. If **both** layouts exist at once, the build aborts — move root files into `templates/`. With `build.sh --strict-layout` or `validate-plugin.sh --strict`, root-only `*.tpl` is also an error. Unpack puts `templates.tar` into `templates/` — not as loose files in the root. PIP XMLs (`option.xml`, `page.xml`, …) stay in the package root.

If `files/` exists, packing uses **only** that folder (not `lib/` as well). Use either the classic layout (`lib/`, `acp/`, `style/`) or the `files/` layout — do not mix both.

## CLI flags

```bash
./tools/build.sh --json patch          # JSON report on stdout (CI)
./tools/build.sh --strict-layout same  # Root *.tpl → error
./tools/check-pip-sources.py --json    # PIP check as JSON
./tools/check-pip-sources.py --strict-case  # Case-sensitive paths (as on Linux servers)
./tools/check-template-layout.py [--strict] temp_edit
./tools/validate-plugin.sh --strict [plugin]
```

`--json` on `build.sh` does not hide the normal log lines; the JSON block appears at the end on success (`ok: true`) or via `build_fail` on stderr (`ok: false`).

## Other SWPM features

- `validate-plugin.sh` (store, XSS, languages)
- Git push / release
- TypeScript compilation
- Optional Docker helpers for local ACP installation
