# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.5.1] - 2026-01-08

### Fixed
- **WSL2 Ubuntu 24 compatibility**: Fixed `local: can only be used in a function` error in `install.sh` (line 641-647)
  - Removed incorrect use of `local` keyword outside function scope
  - Bug reported by user running WSL2 - Ubuntu 24

### Added
- `scripts/test-wsl-ubuntu.sh` - Docker-based Ubuntu 24 compatibility testing
- `scripts/simple-bash-test.sh` - Quick Bash syntax and compatibility check
- `docs/TESTING-WSL.md` - WSL2/Ubuntu testing guide for CachyOS/Arch users
- `scripts/README.md` - Overview of all available scripts

### Changed
- Improved test infrastructure for cross-platform compatibility testing

---

## [1.5.0] - 2025-11-26

### Added
- WoltLab 6.0/6.1/6.2 compatibility validation
- Plugin Store compliance checks in `validate-plugin.sh`:
  - Translation validation (DE/EN mandatory)
  - Minversion validation (6.0/6.1/6.2)
  - Package server prohibition check
  - Excludedpackages recommendation
  - SQL injection pattern detection
  - XSS risk detection in templates
  - Debug code detection (var_dump, console.log, test credentials)
  - WoltLab API best practices (HTTPRequest/Guzzle)
- New documentation files:
  - `PLUGIN-STORE-CHECKLIST.md` / `PLUGIN-STORE-CHECKLIST_EN.md` - Complete submission guide
  - `WOLTLAB-VERSIONS.md` / `WOLTLAB-VERSIONS_EN.md` - Version compatibility guide
  - `CHANGELOG.md` - This file (English only for releases)

### Changed
- **BREAKING:** `README.md` is now English (was German), German version renamed to `README_DE.md`
- English is now the primary language for the project
- `README.md` and `README_DE.md` - Added new features and documentation links
- `validate-plugin.sh` - Removed strict error handling to allow warnings
- `version.sh` - Updated to support new README naming (README.md = EN, README_DE.md = DE)
- Moved `GITHUB-WORKFLOWS.md` and `GITHUB-WORKFLOW-TEST.md` to `.github/docs/`

### Removed
- `WSPACKAGER-ANALYSE.md` - Internal analysis file (not user-relevant)

---

## [1.4.3] - 2025-11-08

### Changed
- Version bump for workflow testing

---

## [1.4.2] - 2025-11-08

### Fixed
- GitHub Actions workflow for toolkit repository

### Changed
- Updated all dates to 2025-11-08

---

## [1.4.1] - 2025-11-08

### Added
- Multilingual issue templates (DE/EN)
- `CONTRIBUTING.md` with contribution guidelines

### Changed
- Updated version and dates in README files

### Removed
- Question template from GitHub issues
- `.cursorrules` from repository (local only)

---

## [1.4.0] - 2025-11-08

### Added
- Complete logging system for all scripts
- Error handling with detailed error messages
- `validate-plugin.sh` - Plugin structure validation
- Troubleshooting documentation (DE/EN)

### Changed
- Massively improved `install.sh` with:
  - Path validation
  - WoltLab Core validation
  - Max retries to prevent endless loops
  - Comprehensive logging
- Enhanced all scripts with logging and error handling

---

## [1.0.0] - 2025-01-01

### Added
- Initial release
- Interactive installation script
- Generic build scripts (extract, update, create-release)
- Workspace templates for Cursor/VSCode
- IDE setup with Intelephense configuration
- Example plugin based on WoltLab Getting Started
- Comprehensive documentation (DE/EN)
- Multi-root workspace support
- Automatic version management scripts

---

## Release Notes

### Version Scheme
This project uses [Semantic Versioning](https://semver.org/):
- **MAJOR** version for incompatible API changes
- **MINOR** version for new functionality in a backward compatible manner
- **PATCH** version for backward compatible bug fixes

### Support
- **Maintained versions:** Latest release only
- **WoltLab Suite:** 6.0, 6.1, 6.2
- **PHP:** 8.0+

---

**Copyright (c) 2025 SunnyCueq**
**License:** MIT (Open Source)
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager
