# IDE Setup - VSCode

**Last Updated:** 2025-11-08  
**Status:** Current

**Last Change:** Initial version
- Reason: Creation of VSCode setup guide

---

This guide explains how to set up VSCode for WoltLab plugin development.

## Prerequisites

- VSCode installed
- Workspace created (see [WORKSPACE-SETUP_EN.md](WORKSPACE-SETUP_EN.md))
- WoltLab Core available

## Step 1: Open Workspace

Open the workspace file in VSCode:

```bash
code woltlab-plugin-dev.code-workspace
```

Or via menu:
1. `File → Open Workspace from File...`
2. Select `woltlab-plugin-dev.code-workspace`

## Step 2: Install Extensions

VSCode will automatically suggest recommended extensions. Install:

### Intelephense (Required)

- **Name:** Intelephense
- **ID:** `bmewburn.vscode-intelephense-client`
- **Purpose:** PHP Auto-Completion for WoltLab classes

Installation:
1. `Ctrl+Shift+X` (Open Extensions)
2. Search: "Intelephense"
3. Install

### Xdebug (Optional)

- **Name:** PHP Debug
- **ID:** `xdebug.php-debug`
- **Purpose:** PHP Debugging

### EditorConfig (Recommended)

- **Name:** EditorConfig for VS Code
- **ID:** `EditorConfig.EditorConfig`
- **Purpose:** Consistent code formatting

## Step 3: Configure Intelephense

The Intelephense configuration is already included in the workspace. You can also adjust it in User Settings:

1. `Ctrl+,` (Open Settings)
2. Search: "intelephense"
3. Adjust settings

Or directly in `settings.json`:

```json
{
  "intelephense.environment.includePaths": [
    "/path/to/woltlab/core/lib"
  ],
  "intelephense.environment.phpVersion": "8.4.0",
  "intelephense.diagnostics.undefinedTypes": false,
  "intelephense.diagnostics.undefinedMethods": false
}
```

## Step 4: Restart IDE

After installing extensions:

1. Close VSCode completely
2. Open VSCode again
3. Open the workspace

## Step 5: Test Auto-Completion

Create a test file:

```php
<?php
use wcf\system\WCF;
use wcf\data\DatabaseObject;

// Auto-completion should work here:
$db = WCF::getDB();  // <- Ctrl+Space should show methods
```

### Expected Behavior

- No red squiggles on `use wcf\...` statements
- Auto-completion on `WCF::` shows methods
- Auto-completion on `FormContainer::create()` shows parameters
- No "Class not found" errors

## Troubleshooting

### "Class not found" errors persist

1. **Check includePaths in workspace**
2. **Check if core path is correct**
3. **Clear Intelephense cache:**
   ```bash
   rm -rf ~/.cache/intelephense/
   ```
4. **Restart VSCode completely**

### Auto-completion doesn't work

1. Check if Intelephense is installed
2. Check VSCode console for errors: `Help → Toggle Developer Tools`
3. Restart Intelephense: `Ctrl+Shift+P` → "Intelephense: Restart"

### Too many errors in IDE

The workspace configuration already disables many undefined diagnostics. If too many errors are still shown, adjust the settings.

## Best Practices

1. **Use workspace:** Always open the workspace file
2. **Keep extensions updated:** Regularly update
3. **Clear cache:** Clear Intelephense cache when having issues
4. **Separate workspaces:** Different projects in different workspaces

## Further Information

- **[WORKSPACE-SETUP_EN.md](WORKSPACE-SETUP_EN.md)** - Workspace configuration
- **[INSTALLATION_EN.md](INSTALLATION_EN.md)** - Installation
- [VSCode Documentation](https://code.visualstudio.com/docs)

