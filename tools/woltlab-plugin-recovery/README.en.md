# WoltLab Plugin Recovery Tool

> Emergency recovery for WoltLab Suite 6.0+ — repairs the ACP, uninstalls broken plugins, restores missing plugin files, and clears caches when the admin panel is inaccessible.

**Language / Sprache:** [English](README.en.md) | [Deutsch](README.md)

[![Release](https://img.shields.io/github/v/release/benjarogit/sc-woltlab-plugin-recovery)](https://github.com/benjarogit/sc-woltlab-plugin-recovery/releases)
![WoltLab Suite](https://img.shields.io/badge/WoltLab%20Suite-6.0+-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

<p align="center">
  <img src="https://github.com/user-attachments/assets/387d02cb-4d84-47e9-8d2b-aac2cebccf8a" width="100%">
</p>

## Installation

Download everything from **[Releases](https://github.com/benjarogit/sc-woltlab-plugin-recovery/releases)** — do not use “Download ZIP” on the repo page.

1. **[Download `plugin-recovery-tool.php`](https://github.com/benjarogit/sc-woltlab-plugin-recovery/releases/latest/download/plugin-recovery-tool.php)** and upload it to your **WoltLab root** (same folder as `global.php`).
2. Open `https://your-domain.com/plugin-recovery-tool.php` in your browser.
3. Complete auth (below) — the tool then downloads **`recovery-2.0.0.tar.gz`** from GitHub and extracts it to `recovery-tool/`.
4. If automatic download fails, use the link on the tool’s page and extract the archive into `recovery-tool/` manually.

```
your-woltlab-root/
├── global.php
├── plugin-recovery-tool.php
├── plugin-recovery-auth.php      ← after upload
└── recovery-tool/                ← created by the tool
```

**Upgrading from 1.x:** Replace the old `plugin-recovery-tool.php` with the new release file. The auth flow is unchanged.

### Sign-in (auth file)

On first visit the tool creates `plugin-recovery-auth.php` **on your server** (same idea as WoltLab’s [recovery tool](https://manual.woltlab.com/de/recovery-tool/)):

1. Open the tool URL in your browser  
2. Download `plugin-recovery-auth.php` from the server and upload it back to the same folder  
3. Click **“Start Recovery Tool”** — valid for **24 hours**

### Remove when done

Use **“Remove Recovery Tool completely”** in the tool. It deletes itself, the auth file, the `recovery-tool/` folder, `uploads/`, and recovery logs under `log/`. **Do not leave it on the server.**

---

## When to use it

- ACP errors such as “Call to a member function toHtml() on null”
- Failed plugin install broke the admin panel
- `ClassNotFoundException` after partial deletion of plugin files
- You need to remove a plugin but the ACP is unreachable
- Caches must be cleared and nothing else works
- Reset an admin password without the ACP

## Modes

| Mode | Purpose |
|------|---------|
| **ACP Repair** | Remove broken ACP menu items by package ID or archive |
| **Plugin Uninstall** | Fully remove a plugin from database and filesystem (wizard with backup) |
| **User Management** | Search users, password, groups, email, 2FA |
| **Cache Clear** | Clear templates and cache directories |
| **Package List Repair** | Clean orphaned package entries in the database |
| **Repair plugin files** | Restore missing `.class.php` from package archive |
| **Recovery Wizard** | Guided repair: diagnose → plan → execute |

<p align="center">
  <img src="https://github.com/user-attachments/assets/0278a36b-419b-45be-8b60-971c1ff1d3d0" width="100%">
</p>

---

## Features

### ACP Repair

Remove broken ACP menu items (by identifier or uploaded `.tar`/`.tar.gz`), with preview before deletion. Clears cache afterwards.

### Plugin Uninstall

3-step wizard: analyse all PIP resources → optional SQL backup → execute. Dry-run available. **Irreversible** — back up your database first.

### User Management

Search users, set passwords, groups, email, activate accounts, disable 2FA.

### Cache Clear

Deletes `tmp/`, `cache/`, `templates/compiled/`, `acp/templates/compiled/` on the filesystem.

### Package List Repair

Cleans orphaned `wcf_package_installation_queue` and `wcf_application` entries.

### Repair plugin files & Recovery Wizard

Upload a package archive; the tool detects missing bootstrap classes and copies files from `files.tar` / `files_wcf.tar`. The wizard guides you through diagnosis and repair.

---

## Security

- **Delete immediately after use** — full database access
- Auth token expires after 24 hours
- Protected system directories cannot be deleted by accident
- Use the WoltLab ACP for normal uninstalls

## Requirements

- WoltLab Suite 6.0+
- PHP 8.1+
- Write access in the WoltLab directory (including `log/` for error debugging)

## Version & changelog

Current version and release notes: [Releases](https://github.com/benjarogit/sc-woltlab-plugin-recovery/releases)

## License

MIT — Copyright (c) 2025 Sunny C.
