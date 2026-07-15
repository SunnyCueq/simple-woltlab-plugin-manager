<p align="center">
  <img src="docs/assets/swpm-logo.jpg" alt="SWPM — Simple WoltLab Plugin Manager" width="320">
</p>

<h1 align="center">Simple WoltLab Plugin Manager</h1>

<p align="center">
  <strong>CLI workbench for WoltLab plugins — build, validate, and release from the terminal.</strong>
</p>

<p align="center">
  <a href="https://benjarogit.github.io/simple-woltlab-plugin-manager/en/"><img src="https://img.shields.io/badge/Documentation-Handbook%20DE%2FEN-1a5fb4?style=for-the-badge" alt="Documentation"></a>
</p>

<p align="center">
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/releases/latest"><img src="https://img.shields.io/github/v/release/benjarogit/simple-woltlab-plugin-manager?style=flat-square&label=release" alt="Latest release"></a>
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT License"></a>
  <img src="https://img.shields.io/badge/platform-Linux%20%7C%20macOS%20%7C%20WSL2%20%7C%20Git%20Bash-2d6a4f?style=flat-square" alt="Platforms">
  <img src="https://img.shields.io/badge/WoltLab%20Suite-6.2%2B-1a5fb4?style=flat-square" alt="WoltLab Suite 6.2+">
</p>

<p align="center"><a href="README.de.md"><strong>Deutsche Version</strong></a></p>

---

## Documentation (handbook)

The full guide lives in the **online handbook** — navigation, search, German and English. This README is only the entry point.

**→ [SWPM Documentation](https://benjarogit.github.io/simple-woltlab-plugin-manager/en/)**

Source in the repo: `tools/docs/` (built to GitHub Pages automatically on push to `main`).

---

## What is SWPM?

A generic toolkit for WoltLab Suite plugins: packaging (`package.xml`), checks, store rules, and translations — in one terminal menu, not tied to a single product plugin. Aligned with the [WoltLab Plugin Store guidelines](https://www.woltlab.com/pluginstore/en/guidelines/).

At a glance:

- Interactive menu: `./tools.sh`
- Build → installable `.tar.gz` (patch/minor/major/same)
- Validation (PHP/XML, templates, languages, store heuristics)
- Optional: product line (core + add-ons), TypeScript, PHPStan, Docker helpers for ACP

Guides and details: **[handbook](https://benjarogit.github.io/simple-woltlab-plugin-manager/en/)**.

---

## Quick start

```bash
git clone https://github.com/benjarogit/simple-woltlab-plugin-manager.git
cd simple-woltlab-plugin-manager
./tools.sh
```

Windows without a Unix shell: `tools.cmd`. Optional: `./tools.sh setup` and `tools/.env` from `.env.example`.

Common commands: `./tools.sh build` · `./tools.sh validate …` · `./tools.sh help`  
CLI details: [tools/README.md](tools/README.md)

---

## Contributing & license

Contributions welcome — keep SWPM **generic** (no product hardcoding). See [CONTRIBUTING.md](CONTRIBUTING.md).

**[MIT License](LICENSE)** · [Changelog](CHANGELOG.md) · [Releases](https://github.com/benjarogit/simple-woltlab-plugin-manager/releases/latest)
