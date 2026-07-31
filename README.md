<p align="center">
  <img src="docs/assets/swpm-logo.png" alt="SWPM — Simple WoltLab Plugin Manager" width="300">
</p>

<h1 align="center">Simple WoltLab Plugin Manager</h1>

<p align="center">
  <strong>CLI workbench for WoltLab plugins — build, validate, and release from the terminal.</strong>
</p>

<p align="center">
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/releases/latest"><img src="https://img.shields.io/github/v/release/benjarogit/simple-woltlab-plugin-manager?style=for-the-badge&color=0e7490&labelColor=0a0e18" alt="Latest release"></a>
  <a href="https://benjarogit.github.io/simple-woltlab-plugin-manager/en/"><img src="https://img.shields.io/badge/Docs-Handbook%20DE%2FEN-4f46e5?style=for-the-badge&labelColor=0a0e18" alt="Documentation"></a>
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/LICENSE"><img src="https://img.shields.io/badge/License-MIT-65a30d?style=for-the-badge&labelColor=0a0e18" alt="MIT License"></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/WoltLab%20Suite-6.2%2B-0e7490?style=flat-square&logoColor=white&labelColor=0a0e18" alt="WoltLab Suite 6.2+">
  <img src="https://img.shields.io/badge/Platform-Linux%20%7C%20macOS%20%7C%20WSL2%20%7C%20Git%20Bash-0d9488?style=flat-square&labelColor=0a0e18" alt="Platforms">
  <img src="https://img.shields.io/badge/Vibe%20Coding-Friendly-e11d48?style=flat-square&labelColor=0a0e18" alt="Vibe Coding Friendly">
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/actions/workflows/tools-tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/benjarogit/simple-woltlab-plugin-manager/tools-tests.yml?branch=main&style=flat-square&label=toolkit%20tests&labelColor=0a0e18" alt="Toolkit tests"></a>
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

Built so agent-assisted workflows stay grounded: clear CLI, local checks, and docs you can point tools at — **vibe coding friendly**, still store-aware.

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
