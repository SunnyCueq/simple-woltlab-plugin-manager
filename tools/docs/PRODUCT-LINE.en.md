# Product line: base + add-ons

**[Deutsche Version](PRODUCT-LINE.de.md)**

This guide explains **how to place, check, and build several related WoltLab packages** (a base app plus optional add-ons) with SWPM — in the right order, with mistakes caught early.

You do not need graph theory. You need folders, a small JSON file, and clear dependencies in each `package.xml`.

---

## What is a product line?

A **product line** (in SWPM also called a **family**) is a group of packages that belong together:

| Role | Meaning | Example |
|------|---------|---------|
| **Base** | The main package. Without it, add-ons do not make sense. | `com.vendor.myapp` |
| **Add-on** | Optional feature package. Requires the base. | `com.vendor.myapp.specials` |

**Important:** The base and each add-on are **separate installable WoltLab packages** (own `.tar.gz`, own folder). SWPM builds them in order — base first, then add-ons.

Nothing is hard-wired to one commercial product. Examples use placeholders (`com.vendor.myapp`). Replace them with your IDs.

---

## Three commands — do not mix them up

| What you want | Command | What happens |
|---------------|---------|--------------|
| Build **one** package | `./tools/build.sh my-plugin patch` | That folder only |
| Build the **product line** | `./tools.sh family:build patch` | Only packages from the manifest, **base first** |
| Find and build everything in the workspace | Menu “all” / `build:all` | **Not** the family — no manifest |

If you mean a product line, always use `family:*`. Otherwise you may build unrelated folders or in the wrong order.

---

## Where folders may live

Two common layouts — both are valid.

### Option A — Sibling folders (recommended for real apps)

SWPM and your packages sit **next to each other**. The manifest lives in a shared parent folder, or points at packages with `../…`.

```
Dokumente/
├── plugin-manager/          ← SWPM (tools/, tools.sh)
├── swpm-family.json         ← Manifest (here or wherever you put it)
├── myapp/                   ← Base
│   └── package.xml          ← or temp_edit/package.xml (see below)
├── myapp-specials/          ← Add-on
│   └── package.xml
└── myapp-vouchers/          ← Another add-on
    └── package.xml
```

In the manifest, for example:

```json
"paths": [
  "myapp",
  "myapp-specials",
  "myapp-vouchers"
]
```

when the manifest is **in the same folder** as `myapp` — or:

```json
"paths": [
  "../myapp",
  "../myapp-specials",
  "../myapp-vouchers"
]
```

when the manifest is **in the SWPM root** (`plugin-manager/`).

### Option B — Packages inside the SWPM root

```
plugin-manager/
├── tools/
├── swpm-family.json
├── myapp/
│   └── temp_edit/package.xml
├── myapp-specials/
│   └── temp_edit/package.xml
└── myapp-vouchers/
    └── temp_edit/package.xml
```

```json
"paths": ["myapp", "myapp-specials", "myapp-vouchers"]
```

---

## Where `package.xml` lives (scaffold vs real plugin)

SWPM looks for `package.xml` in these places (per package root):

1. `temp_edit/package.xml` — typical for **scaffold** and many SWPM workflows  
2. `package.xml` at the package root — typical for **full** plugins/apps  
3. `_extracted/package.xml` — after unpacking an archive  

**Rule:** One root folder per package. SWPM must find a valid `package.xml` inside it. Root or `temp_edit/` — both are allowed; scaffold creates `temp_edit/`.

Build a single package (no family):

```bash
./tools/build.sh myapp patch
```

Internals of one package (`lib/`, templates/, …): [PACKAGE-LAYOUT.en.md](PACKAGE-LAYOUT.en.md).

---

## Required: dependency in `package.xml`

Every **add-on** must declare the base as a required package:

```xml
<requiredpackages>
	<requiredpackage minversion="6.2.0">com.woltlab.wcf</requiredpackage>
	<requiredpackage minversion="1.0.0">com.vendor.myapp</requiredpackage>
</requiredpackages>
```

| Term | Plain language |
|------|----------------|
| `requiredpackage` | “This package may only be installed if that one is already installed.” |
| `minversion` | “At least this version of the base.” |
| `com.woltlab.wcf` | WoltLab Suite Core — does **not** count as a family edge (always allowed). |

**Without** `requiredpackage` pointing at the base, SWPM sees two separate islands → **abort**. That is intentional: missing dependencies show up immediately.

The base only needs WCF (and any other real dependencies), **not** the add-ons.

---

## The manifest `swpm-family.json`

A small map of **where** SWPM should search. The **truth** about IDs and dependencies comes from the `package.xml` files.

### All fields

| Field | Required | Meaning |
|-------|----------|---------|
| `schemaVersion` | yes | Format version, currently `1` |
| `id` | recommended | Name of the **line** (free), e.g. `com.vendor.myapp.line` — does **not** need to match any package `name` |
| `versionStrategy` | no | e.g. `independent` (lockstep planned later; P0 stores only) |
| `paths` | yes | Search locations (folders). Relative to the manifest or absolute |
| `packages` | no | Empty `[]` = all packages found under `paths`. Non-empty = **whitelist** of those IDs only |

### Full example

```json
{
  "schemaVersion": 1,
  "id": "com.vendor.myapp.line",
  "versionStrategy": "independent",
  "paths": [
    "myapp",
    "myapp-specials",
    "myapp-vouchers"
  ],
  "packages": []
}
```

### Whitelist (when `paths` still contain unrelated packages)

```json
"packages": [
  { "id": "com.vendor.myapp", "path": "myapp" },
  { "id": "com.vendor.myapp.specials", "path": "myapp-specials" }
]
```

Here `id` must match the `name="…"` attribute in that package’s `package.xml` exactly. The line’s manifest `id` (`com.vendor.myapp.line`) is independent.

### Where does the file live?

1. Explicit: `./tools/swpm-family.sh --manifest /path/swpm-family.json …`  
2. Or in `tools/.env`: `WOLTLAB_FAMILY_MANIFEST=swpm-family.json` (relative to SWPM root or absolute)  
3. Or: `swpm-family.json` directly in the SWPM root  

---

## Step by step (from zero to first check)

### Option 1 — Scaffold (starter help)

In the SWPM directory:

```bash
./tools.sh family:init --scaffold \
  --base-id com.vendor.myapp \
  --base-dir myapp \
  --addons myapp-specials,myapp-vouchers \
  --wcf-min 6.2.0
```

This creates:

- Manifest `swpm-family.json`
- Folders `myapp/`, `myapp-specials/`, … with `temp_edit/package.xml` (+ minimal `lib/` placeholders)
- Add-ons with `requiredpackage` on the base

Then:

```bash
./tools.sh family:check
./tools.sh family:list
./tools.sh family:order
```

**Scaffold ≠ store-ready plugin.** You still add real files (`lib`, templates, …) per [PACKAGE-LAYOUT.en.md](PACKAGE-LAYOUT.en.md). Graph checks (`family:check`) only verify dependencies, not store quality.

Another add-on:

```bash
./tools.sh family:add-addon myapp-messages --base-id com.vendor.myapp
```

Without `--base-id`, SWPM tries to derive the base ID from a valid family graph (via the dependency check’s JSON output).

### Option 2 — Wire existing folders

1. Create one folder per package (option A or B).  
2. In each add-on, set `requiredpackage` to the base.  
3. Write `swpm-family.json` with narrow `paths`.  
4. `./tools.sh family:check` — must report **OK**.  
5. `./tools.sh family:build patch` or `family:validate`.

Manifest only (no folders):

```bash
./tools.sh family:init
```

(you must already have the folders; otherwise `family:check` fails on missing paths.)

---

## What SWPM checks in a family

In plain terms: **Everything you mean as one family must hang together via `requiredpackage`.**

| Check | When it fails |
|-------|----------------|
| At least one package found | Wrong or empty `paths` |
| Exactly **one** connected group | Add-on without dep on base, or `paths` too broad |
| Every non-WoltLab dependency is in the family | Typo in package ID |
| No cycle (A needs B, B needs A) | Fix dependencies |
| Base `minversion` ≤ actual base version | Bump version in base `package.xml` or lower minversion |
| No duplicate package ID | Two folders with the same `name` |
| Whitelist `id` = `package.xml` `name` | Align IDs |

`com.woltlab.*` does **not** create a family edge and may be absent from the family set.

Multiple bases in **one** group (add-on needs A and B) are allowed but uncommon — SWPM warns.

---

## Product-line commands

| Command | Purpose |
|---------|---------|
| `family:list` | Show packages and dependencies |
| `family:order` | Build order (base first) |
| `family:check` | Dependency check only |
| `family:build [patch\|minor\|major\|same]` | Check, then build each package |
| `family:validate [--strict]` | Check, then validate each package |
| `family:init` | Create manifest |
| `family:init --scaffold` | Manifest + stub folders |
| `family:add-addon <slug>` | Another add-on stub + update `paths` |

Direct:

```bash
./tools/swpm-family.sh --manifest /path/swpm-family.json check
./tools/swpm-family.sh --manifest /path/swpm-family.json build patch
```

### Notes on `.env`

For `family:*`, `WOLTLAB_PACKAGE_ID` / `WOLTLAB_APP_ABBREV` are **ignored** (with a warning). Those values are for a single package — otherwise the whole line would get the wrong ID.

Default: stop on first error. With `--continue` (local only), keep going after a failed package.

---

## Anti-patterns (please avoid)

| Mistake | Result |
|---------|--------|
| `paths: [".."]` or a far-too-wide folder | Many unconnected plugins → abort |
| Add-on without `requiredpackage` on the base | Second island → abort |
| Unrelated plugins under the same `paths` without whitelist | Abort or wrong line |
| Mixing up `family:build` and `build:all` | Wrong set / order |
| Treating scaffold as a store release | Validate/build fail on missing sources |

Under an app root, search deliberately skips e.g. `examples/`, `fixtures/`, `node_modules/`, `.git/` — so demo add-ons do **not** silently join the family. To include a demo on purpose: its own `paths` entry or a whitelist.

---

## Checklist before the first `family:build`

- [ ] Each package has its own folder and a findable `package.xml`
- [ ] Each add-on has `<requiredpackage …>your.base.id</requiredpackage>`
- [ ] Base and add-ons have a `<version>`
- [ ] `swpm-family.json` exists; `paths` point exactly at those folders
- [ ] `family:check` ends with OK
- [ ] `family:order` shows the base **before** the add-ons
- [ ] You know which manifest you mean (`--manifest` or `.env`)

---

## Bundled mini example (fixture)

Under `tools/fixtures/family-demo/`:

- a valid line (core + add-on with `requiredpackage`)
- intentionally broken manifests (missing dependency, two islands)

```bash
python3 tools/check-family-deps.py --manifest tools/fixtures/family-demo/swpm-family.json --mode order
./tools/swpm-family.sh --manifest tools/fixtures/family-demo/swpm-family.json check
```

---

## Short glossary

| Term | Meaning |
|------|---------|
| Product line / family | Group of base + add-ons under one manifest |
| Base / core | The package add-ons require |
| Add-on | Optional package with `requiredpackage` on the base |
| Manifest | `swpm-family.json` — search locations, not a replacement `package.xml` |
| `paths` | Where to search |
| Whitelist `packages[]` | Which package IDs may belong to the line |
| Build order | Technically: topological sort — base before add-ons |

---

## See also

- [PACKAGE-LAYOUT.en.md](PACKAGE-LAYOUT.en.md) — layout of **one** package (files, templates/, files/)
- [PLUGIN-STORE-CHECKLIST.en.md](PLUGIN-STORE-CHECKLIST.en.md) — store; add-ons need the core listed
- [ACP-PACKAGE-INSTALL.en.md](ACP-PACKAGE-INSTALL.en.md) — local ACP install (optional, Docker)
