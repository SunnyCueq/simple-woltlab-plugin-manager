# Contributing to Simple WoltLab Plugin Manager

**[Deutsche Version](CONTRIBUTING.de.md)**

SWPM is a **generic** toolkit for any WoltLab plugin project. It must not hard-wire references to a single product plugin.

## Hard rules

1. Read package metadata from the user’s `package.xml` (`tools/swpm-package-resolve.sh`) or from `tools/.env` — never bake in vendor/package/app paths.
2. Put product-specific scripts, test data, and docs in the **plugin repository**, not here.
3. Docker helpers are **optional** for local development; core tools (`build.sh`, `validate-plugin.sh`, `tools.sh`) must work without Docker.

See `.cursor/rules/swpm-generic-only.mdc` for the full policy.

## Documentation

- Every guide in **English and German** (`*.en.md` / `*.de.md` or `README.md` / `README.de.md`).
- Index / handbook: [MkDocs site](https://benjarogit.github.io/simple-woltlab-plugin-manager/en/) (source `tools/docs/`)
- New build fail checks: add a line in `tools/swpm-check-registry.txt` plus a script under `tools/` (see [SECURITY-CHECKS.en.md](tools/docs/SECURITY-CHECKS.en.md)).

