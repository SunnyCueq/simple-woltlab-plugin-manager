# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

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
  - `CHANGELOG.md` - This file

### Changed
- `README.md` and `README_EN.md` - Added new features and documentation links
- `validate-plugin.sh` - Removed strict error handling to allow warnings
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
