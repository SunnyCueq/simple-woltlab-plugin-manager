# Contributing to Simple WoltLab Plugin Manager

**[Deutsche Version](CONTRIBUTING.de.md)**

SWPM is a **generic** toolkit for any WoltLab plugin project. It must not contain hardcoded references to a single product plugin.

## Hard rules

1. Resolve package metadata from the user's `package.xml` (`tools/swpm-package-resolve.sh`) or `tools/.env` — never hardcode vendor/package/app paths.
2. Put product-specific scripts, test data, and docs in the **plugin repository**, not here.
3. Docker helpers are **optional** local-dev shortcuts only; core tools (`build.sh`, `validate-plugin.sh`, `tools.sh`) must work without Docker.

See `.cursor/rules/swpm-generic-only.mdc` for the full policy.

## Documentation

- Every guide in **English and German** (`*.en.md` / `*.de.md` or `README.md` / `README.de.md`).
- Index: [tools/docs/README.md](tools/docs/README.md)

