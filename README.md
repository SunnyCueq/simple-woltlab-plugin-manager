<p align="center">
  <img src="docs/assets/swpm-logo.jpg" alt="SWPM — Simple WoltLab Plugin Manager" width="320">
</p>

<h1 align="center">Simple WoltLab Plugin Manager</h1>

<p align="center">
  <strong>Cross-platform CLI workbench for WoltLab Suite plugin development — born from building real plugins, semi-automated with checks and validation.</strong>
</p>

<p align="center">
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/releases/latest"><img src="https://img.shields.io/github/v/release/benjarogit/simple-woltlab-plugin-manager?style=flat-square&label=release" alt="Latest release"></a>
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT License"></a>
  <img src="https://img.shields.io/badge/platform-Linux%20%7C%20macOS%20%7C%20WSL2%20%7C%20Git%20Bash-2d6a4f?style=flat-square" alt="Platforms">
  <img src="https://img.shields.io/badge/WoltLab%20Suite-6.2%2B-1a5fb4?style=flat-square" alt="WoltLab Suite 6.2+">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Bash-4EAA25?style=flat-square&logo=gnu-bash&logoColor=white" alt="Bash">
  <img src="https://img.shields.io/badge/Python-3776AB?style=flat-square&logo=python&logoColor=white" alt="Python">
  <img src="https://img.shields.io/badge/Shell_Scripts-121011?style=flat-square&logo=gnubash&logoColor=white" alt="Shell">
  <img src="https://img.shields.io/badge/TypeScript-3178C6?style=flat-square&logo=typescript&logoColor=white" alt="TypeScript (optional)">
  <img src="https://img.shields.io/badge/Docker-optional-2496ED?style=flat-square&logo=docker&logoColor=white" alt="Docker optional">
</p>

<p align="center"><a href="README.de.md"><strong>Deutsche Version</strong></a></p>

---

## About the project

**SWPM** started as a personal workbench: while building my own WoltLab Suite plugins, I kept hitting the same manual steps — packaging `package.xml`, running sanity checks, validating store rules, keeping translations in sync. SWPM semi-automates that workflow in one terminal menu so I can focus on the plugin itself.

Today it is a **generic**, cross-platform CLI toolkit for the full plugin lifecycle: setup, build, validation, and GitHub release — without tying you to a single product plugin.

It stays aligned with the official [WoltLab Plugin Store guidelines](https://www.woltlab.com/pluginstore/en/guidelines/).

---

## Features

- **Interactive menu** — `./tools.sh` for build, validate, TypeScript, Git push, and setup.
- **Package build** — Creates installable `.tar.gz` archives with patch/minor/major/same versioning; [wspackager-compatible layouts](tools/docs/WSPACKAGER-PARITY.en.md) (`files/`, `files_wcf/`, `--json` for CI).
- **Validation** — PHP/XML syntax, XSS/SQL heuristics, DE/EN language keys, PIP source checks (DevTools parity), store checklist mapping.
- **Workspace setup** — Optional download of WoltLab Core, clone of official docs, WCF source, and [WoltLab d.ts](https://github.com/WoltLab/d.ts) typings.
- **Release workflow** — Commit, push, tag, and GitHub release via `gitpush.sh`.
- **Cross-platform** — Linux, macOS, Windows (WSL2 or Git Bash); entry via `tools.cmd` on Windows.
- **Optional Docker helpers** — Local ACP install and permission fixes; **not required** for core tools.

---

## Tech stack

| Layer | Technology |
|-------|------------|
| Entry & orchestration | Bash (`tools.sh`, `tools/*.sh`) |
| Validation & checks | Python 3 (`check-*.py`, `validate-plugin.sh`) |
| Plugin assets (optional) | Node.js / TypeScript in your plugin project |
| Packaging | `tar`, `package.xml`, WoltLab PIP archives |
| Local test (optional) | Docker, DDEV |

Core tools run with **Bash + Git + tar**. Python 3 is strongly recommended for validation.

---

## Installation

### Prerequisites

| Requirement | Required | Notes |
|-------------|----------|-------|
| Bash | Yes | WSL2 or [Git for Windows](https://git-scm.com/download/win) on Windows |
| Git | Yes | Clone and release workflow |
| tar | Yes | Package archives |
| Python 3 | Recommended | Validation scripts |
| Node.js | Optional | Only if your plugin uses TypeScript |

### Steps

**1. Clone the repository**

```bash
git clone https://github.com/benjarogit/simple-woltlab-plugin-manager.git
cd simple-woltlab-plugin-manager
```

**2. Start the toolkit**

```bash
./tools.sh
```

On Windows without a Unix shell:

```cmd
tools.cmd
```

**3. (Recommended) Run setup when you need Core, docs, or typings**

```bash
./tools.sh setup
```

**4. Configure local paths (optional)**

```bash
cp tools/.env.example tools/.env
# Edit tools/.env — WoltLab path, GIT_REPO_URL, etc.
```

Place your plugin in a folder with `package.xml` (e.g. `basis-plugin/`) or unpack an existing package into `temp_edit/`.

---

## Usage

### Interactive menu

```bash
./tools.sh
```

Example menu (abbreviated):

```text
╔══════════════════════════════════════════════════════════╗
║              Simple WoltLab Plugin Manager               ║
╚══════════════════════════════════════════════════════════╝

  Plugins
  ✓ My Plugin    v1.0.0 [basis-plugin]

  ENTWICKLUNG
  1   Build / Update-Paket
  2   TypeScript
  3   Unpack
  4   Plugin validieren
  …
```

### Common commands

```bash
# Build with patch version bump
./tools.sh build

# Build without changing version (development)
./tools.sh build:same

# Validate before store submission
./tools.sh validate basis-plugin

# TypeScript compile
./tools.sh typescript

# Commit, push, GitHub release
./tools.sh push

# CLI overview
./tools.sh help
```

### CI-friendly build (JSON report)

```bash
./tools/build.sh --json patch
```

### Workspace layout

| Folder | Purpose |
|--------|---------|
| `basis-plugin/` | Your main plugin (example name) |
| `tools/` | All scripts |
| `woltlab-core/` | Core files after setup |
| `woltlab-d-ts/` | TypeScript typings after setup |

**Plugin source layout:** Put frontend templates in `templates/` (packed as `templates.tar`). ACP templates stay in `acptemplates/`. PIP XMLs (`option.xml`, `page.xml`, …) remain in the package root. Root-level `*.tpl` still packs with a warning; use `--strict-layout` / `validate-plugin.sh --strict` to fail. Details: [WSPACKAGER-PARITY.en.md](tools/docs/WSPACKAGER-PARITY.en.md).

Full tools reference: **[tools/README.md](tools/README.md)** · Guides index: **[tools/docs/README.md](tools/docs/README.md)**

---

## Contributing

Contributions are welcome. SWPM is a **generic** toolkit — do not add hardcoded paths or scripts for a single product plugin.

1. Fork the repository and create a feature branch.
2. Keep changes focused; match existing Bash/Python style.
3. Update **English and German** docs when you change user-facing behavior.
4. Open a pull request with a clear description of what and why.

Details: **[CONTRIBUTING.md](CONTRIBUTING.md)** (DE: [CONTRIBUTING.de.md](CONTRIBUTING.de.md))

---

## License

This project is licensed under the **[MIT License](LICENSE)**.

You may use, modify, and distribute SWPM freely. Keep the copyright notice intact. Contributions are appreciated; passing the work off as your own proprietary product without attribution is not the intent of this license.

---

## Links

- [Latest release](https://github.com/benjarogit/simple-woltlab-plugin-manager/releases/latest)
- [Changelog](CHANGELOG.md)
- [WoltLab Suite Download](https://www.woltlab.com/en/woltlab-suite-download/)
- [WoltLab Plugin Store Guidelines](https://www.woltlab.com/pluginstore/en/guidelines/)
