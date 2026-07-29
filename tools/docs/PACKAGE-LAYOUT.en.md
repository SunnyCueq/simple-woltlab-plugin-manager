# Package source layout (SWPM)

Short version: which folders hold your files, and what ends up in the installable `.tar.gz`?

SWPM packs from `temp_edit/` (or the plugin root — the **working copy** with `package.xml`). Build, TypeScript, and validate are part of the normal flow.

**PIP** = Package Installation Plugin: install steps in `package.xml` (files, templates, options, …). Template details: [Template rules](WOLTLAB-TEMPLATE-RULES.md). Multiple packages: [Product line](PRODUCT-LINE.md).

## Built package (release layout)

After `./tools/build.sh`, the installable archive lives **centrally** under the SWPM workspace:

```text
releases/
├── basis-plugin/
│   └── com.vendor.myapp_v1.2.3.tar.gz
└── mein-plugin-b/
    └── com.vendor.other_v0.1.0.tar.gz
```

The subfolder matches your plugin folder name (not the package ID). `unpack`, `prepare-acp-install`, and `gitpush` look there first; legacy `.tar.gz` files next to the plugin root are still found as a fallback. The build keeps the last five versions per plugin.

**Important — one folder per product:** Reusing the same slot (e.g. `basis-plugin/`) for different packages can leave old PIP archives (`templates.tar`, …) on disk. The build clears them before packing and only includes archives required by the current `package.xml`.

Additionally, the build stores the last built package ID in `.swpm-slot-package-id`. If the ID changes in the same folder (demo → another plugin), the **build aborts**. One-shot override: `SWPM_ALLOW_SLOT_SWITCH=1 ./tools/build.sh …` (then wipes slot artifacts). Prefer a **dedicated folder per plugin**.

## Layout folders

| Folder | Produces | Meaning |
|--------|----------|---------|
| `templates/` | `templates.tar` | **Default location** — put frontend templates (`.tpl`) here |
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

**scssphp:** Not part of SWPM. WoltLab builds CSS from the variables itself. Local `.scss` under `style/` is not compiled (may warn only). App plugins with ready CSS: `style/` plus `check-style-assets.py`.

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
