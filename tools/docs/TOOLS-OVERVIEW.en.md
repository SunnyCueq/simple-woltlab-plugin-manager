# Tools overview (SWPM)

**[Deutsche Version](TOOLS-OVERVIEW.de.md)**

Which scripts exist, and what are they for? A short transparency list — details and commands live in the [tools reference](../README.md).

## Everyday path (short)

1. Plugin sources in a folder with `package.xml` (often `temp_edit/`)
2. `./tools.sh build` — build the package (includes build checks)
3. `./tools.sh validate …` — check before store/release
4. Optional: `./tools.sh push` — commit, tag, GitHub release

Product line (multiple packages): `./tools.sh family:check` / `family:build` — see [PRODUCT-LINE.en.md](PRODUCT-LINE.en.md).

```bash
./tools.sh help                          # all CLI commands
./tools/swpm-run-checks.sh --mode list   # build check registry
```

---

## Core tools (menu / CLI)

| Script / command | Role |
|------------------|------|
| `tools.sh` | Entry: menu and CLI |
| `build.sh` | Build installable `.tar.gz`, version bump/same |
| `validate-plugin.sh` | Store, security, and structure checks |
| `typescript.sh` | TypeScript → JavaScript |
| `unpack.sh` | Unpack package into `temp_edit/` |
| `gitpush.sh` | Commit, push, tag, release (plugin) |
| `setup-minimal.sh` | Core, docs, d.ts, paths |
| `help.sh` | Open documentation |
| `swpm-family.sh` | Product line (core + add-ons) |
| `pack-style-tar.sh` | Style archive (`style.tar` / `style.tgz`) |

---

## Build checks (registry)

Run on **build** via `swpm-run-checks.sh`. Source: `swpm-check-registry.txt`.

| ID | Level | Script | Short |
|----|-------|--------|-------|
| language-categories | fail | `check-language-categories.py` | Language XML: category ↔ item |
| language-integrity | fail | `check-language-integrity.py` | Language XML integrity |
| template-xss | fail | `check-template-xss.py` | Invalid modifiers / script escaping |
| template-modifiers | fail | `check-template-modifiers.py` | Modifier whitelist |
| template-foreach | fail | `check-template-foreach.py` | Foreach loop variables |
| endpoint-registration | fail | `check-endpoint-registration.py` | RPC endpoints registered |
| like-escaping | fail | `check-like-escaping.py` | LIKE + `escapeLikeValue` |
| js-amd-exports | fail | `check-js-amd-exports.py` | AMD named exports (`setup`) |
| style-package | fail | `check-style-package.py` | Style package `style.xml` / variables |
| template-layout | warn | `check-template-layout.py` | Templates under `templates/` |
| template-notices | warn | `check-template-notices.py` | Notice boxes |
| style-assets | warn | `check-style-assets.py` | CSS `url(...)` files |
| language-keys | warn | `check-language-keys.py` | DE/EN keys |
| first-release-hygiene | warn | `check-first-release-hygiene.py` | 1.0.0 without update leftovers |
| package-descriptions | warn | `check-package-descriptions.py` | `packagedescription` in `package.xml` |

**Outside the registry (but important):** `check-pip-sources.py` — PIP sources vs `package.xml` (build/validate).

More topics and heuristics: [SECURITY-CHECKS.en.md](SECURITY-CHECKS.en.md).

---

## Optional

| Script / command | Role |
|------------------|------|
| `check-typescript.sh` | `tsc` when `tsconfig` / `ts/` exist |
| `run-phpstan.sh` | PHPStan only with `phpstan.neon(.dist)` |
| `lint-manager-python.sh` / `./tools.sh lint:python` | ruff on manager `tools/*.py` |
| `fix-template-xss-escaping.py` | Semi-automatic template fix (no `\|encodeHTML`) |

---

## Docker (optional, local)

| Script | Role |
|--------|------|
| `prepare-acp-install.sh` | Copy package into the web container |
| `check-woltlab-app-permissions.sh` | Check permissions |
| `fix-woltlab-app-permissions.sh` | Fix permissions after `docker cp` |
| `reset-app-for-acp-install.sh` | Clean up a half-finished app install |

See [ACP-PACKAGE-INSTALL.en.md](ACP-PACKAGE-INSTALL.en.md).

---

## Internal / support

Rarely needed day to day; used by the core scripts:

| Script | Role |
|--------|------|
| `common.sh`, `ui.sh` | Colors, menu, shared helpers |
| `swpm-package-resolve.sh` | Metadata from `package.xml` / `.env` |
| `swpm-package-report.py` | JSON build report |
| `swpm-run-checks.sh` | Registry runner |
| `swpm-family-resolve.sh` | Resolve family manifest |
| `check-family-deps.py` | Product-line dependency graph |
| `download-woltlab-core.sh` | Fetch Core (setup) |
| `sync-woltlab-references.sh` | Refresh docs/WCF mirrors |
| `update-woltlab-version.sh` | Version info |
| `manager-push.sh` | maintainer only (if present) |

`tools/woltlab-plugin-recovery/` is a separate recovery helper — not part of the normal build/validate menu.
