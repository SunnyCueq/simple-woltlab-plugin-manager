# Tools overview (SWPM)

Which scripts exist, and what are they for? A short transparency list — details and commands live in the [tools reference](https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/tools/README.md).

!!! info "Build & Validate do not upload anything"

    `./tools.sh build` and `./tools.sh validate` check your plugin **locally only** — similar to the automated rules WoltLab applies later on store upload. **Nothing** is sent to woltlab.com. Real store upload is a later, manual step (see [Plugin Store checklist](PLUGIN-STORE-CHECKLIST.md)).

## Everyday path (short)

1. Plugin sources in a folder with `package.xml` (often `temp_edit/` = unpacked working copy)
2. `./tools.sh build` — build the package (includes build checks)
3. `./tools.sh validate …` — local quality and guideline checks
4. Optional: `./tools.sh push` — commit, tag, GitHub release (your repo)

Product line (multiple packages): `./tools.sh family:check` / `family:build` — see [Product line](PRODUCT-LINE.md).

### Short glossary

| Term | Meaning |
|------|---------|
| **ACP** | Admin Control Panel — WoltLab Suite admin area |
| **PIP** | Package Installation Plugin — install step in `package.xml` (files, templates, options, …) |
| **temp_edit/** | Typical folder for the unpacked plugin working copy |
| **fail** | Check aborts build/validate |
| **warn** | Hint only; with `--strict-layout`, layout can become a hard error |

```bash
./tools.sh help                          # all CLI commands
./tools/swpm-run-checks.sh --mode list   # show build check registry
```

---

## Common CLI commands

| Command | Purpose |
|---------|---------|
| `./tools.sh` / `./tools.sh help` | Menu or command list |
| `./tools.sh build` / `build:same` / `build:dry-run` | Build package (version bump / unchanged / dry run) |
| `./tools.sh validate [plugin]` | Local quality checks (broader than build registry alone) |
| `./tools.sh family:list` / `order` / `check` | Show product line / order / graph check |
| `./tools.sh family:build` / `validate` | All packages in dependency order |
| `./tools.sh family:init` / `add-addon` | Create manifest / add an add-on |
| `./tools.sh typescript` | Compile TypeScript (when present) |
| `./tools.sh phpstan [plugin]` | PHPStan only with `phpstan.neon(.dist)` |
| `./tools.sh lint:python [--fix]` | ruff for manager `tools/*.py` |
| `./tools.sh push` | Commit, tag, GitHub release |
| `./tools.sh setup` | Optionally load Core/docs/d.ts |
| `./tools.sh sync-woltlab-refs` | Refresh reference mirrors (maintainers) |

---

## Core tools (menu / CLI)

| Script / command | Role |
|------------------|------|
| `tools.sh` | Entry: menu and CLI |
| `build.sh` | Build installable `.tar.gz`, version bump/same |
| `validate-plugin.sh` | Local quality, structure, and guideline checks |
| `typescript.sh` | TypeScript → JavaScript |
| `unpack.sh` | Unpack package into `temp_edit/` |
| `gitpush.sh` | Commit, push, tag, release; notes = changelog + Compare/commits |
| `setup-minimal.sh` | Core, docs, d.ts, paths |
| `help.sh` | Open documentation |
| `swpm-family.sh` | Product line (core + add-ons) |
| `pack-style-tar.sh` | Style archive (`style.tar` / `style.tgz`) from `package.xml` |

---

## Build checks (registry)

Run on **build** via `swpm-run-checks.sh`. Source: `swpm-check-registry.txt`.

**Build vs validate:** Build runs the registry (fail aborts, warn logs). Validate covers the same topics and adds PHP/XML syntax, PIP sources, HTTP APIs, debug code, cloud bans — still **local only**.

### Runner flags

```bash
./tools/swpm-run-checks.sh --mode list              # list registry
./tools/swpm-run-checks.sh --mode build [plugin]    # run checks
./tools/swpm-run-checks.sh --mode build --strict-layout [plugin]
./tools/swpm-run-checks.sh --mode build --amd-prefix=MyApp [plugin]
```

- **`needs`:** Checks tagged `language` / `templates` / `lib` / `style` / `js_acp` run only when matching files exist — otherwise skip (not an error).
- **`--amd-prefix`:** Prefix for AMD/JS checks when it cannot be derived from `package.xml`.
- **Exit:** `0` ok, `1` fail check failed, `2` runner/argument error.

| ID | Level | Script | Summary |
|----|-------|--------|---------|
| language-categories | fail | `check-language-categories.py` | Language XML: category ↔ item → [Language XML](LANGUAGE-XML.md) |
| language-integrity | fail | `check-language-integrity.py` | Language XML integrity |
| template-xss | fail | `check-template-xss.py` | Invalid modifiers / script escaping → [Template rules](WOLTLAB-TEMPLATE-RULES.md) |
| template-modifiers | fail | `check-template-modifiers.py` | Modifier whitelist |
| template-foreach | fail | `check-template-foreach.py` | Foreach loop variables |
| endpoint-registration | fail | `check-endpoint-registration.py` | RPC endpoints registered |
| like-escaping | fail | `check-like-escaping.py` | LIKE + `escapeLikeValue` |
| js-amd-exports | fail | `check-js-amd-exports.py` | AMD named exports (`setup`) |
| style-package | fail | `check-style-package.py` | Style package `style.xml` / variables |
| template-layout | warn | `check-template-layout.py` | Templates under `templates/` |
| template-notices | warn | `check-template-notices.py` | Notice boxes |
| style-assets | warn | `check-style-assets.py` | CSS `url(...)` files |
| language-keys | warn | `check-language-keys.py` | DE/EN keys (used in code) |
| language-pip-keys | warn | `check-language-pip-keys.py` | Implicit PIP keys (options / group permissions / ACP menu) → [Language XML](LANGUAGE-XML.md) |
| language-address | warn | `check-language-address.py` | DE Sie/Du address tone (heuristic) |
| first-release-hygiene | warn | `check-first-release-hygiene.py` | 1.0.0 without update leftovers |
| package-descriptions | warn | `check-package-descriptions.py` | `packagedescription` in `package.xml` |

**Outside the registry (but important):** `check-pip-sources.py` — PIP sources vs `package.xml` (build/validate).

Deeper explanations: [Security checks](SECURITY-CHECKS.md). More guides: [Language XML](LANGUAGE-XML.md), [Template rules](WOLTLAB-TEMPLATE-RULES.md), [ACP install](ACP-PACKAGE-INSTALL.md), [Docker permissions](DOCKER-APP-PERMISSIONS.md), [Logging](LOGGING.md).

---

## Optional

| Script / command | Role |
|------------------|------|
| `check-typescript.sh` | `tsc` when `tsconfig` / `ts/` exists |
| `run-phpstan.sh` | PHPStan only with `phpstan.neon(.dist)` — otherwise skip |
| `lint-manager-python.sh` / `./tools.sh lint:python` | ruff for manager `tools/*.py` (not your plugin PHP) |
| `fix-template-xss-escaping.py` | Semi-automatic template fix (never adds `\|encodeHTML`) |

---

## Docker (optional, local)

Only needed if you have a **local WoltLab test instance** in Docker. Core build/validate do not require Docker.

| Script | Role |
|--------|------|
| `prepare-acp-install.sh` | Place package in the web container |
| `check-woltlab-app-permissions.sh` | Check permissions |
| `fix-woltlab-app-permissions.sh` | Fix permissions after `docker cp` |
| `reset-app-for-acp-install.sh` | Clean up a half-finished app install |

See [ACP install](ACP-PACKAGE-INSTALL.md) and [Docker permissions](DOCKER-APP-PERMISSIONS.md).

---

## Internal / support

Rarely needed day to day; used by core scripts:

| Script | Role |
|--------|------|
| `common.sh`, `ui.sh` | Colors, menu, shared helpers |
| `swpm-package-resolve.sh` | Metadata from `package.xml` / `.env` |
| `swpm-package-report.py` | JSON build report |
| `swpm-run-checks.sh` | Registry runner |
| `swpm-family-resolve.sh` | Resolve family manifest |
| `check-family-deps.py` | Product-line dependency graph |
| `download-woltlab-core.sh` | Load Core (setup) |
| `sync-woltlab-references.sh` | Refresh docs/WCF mirrors |
| `update-woltlab-version.sh` | Version info |
| `manager-push.sh` | maintainers only (if present) |

`tools/woltlab-plugin-recovery/` is a separate recovery helper — not part of the normal build/validate menu.

### When something fails

Log path and context: [Logging](LOGGING.md).
