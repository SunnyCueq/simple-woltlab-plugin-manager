# IDE Setup - Cursor

**Last Updated:** 2025-11-08  
**Status:** Current

**Last Change:** Initial version
- Reason: Creation of Cursor IDE setup guide

---

This guide explains how to set up Cursor IDE for WoltLab plugin development.

## Prerequisites

- Cursor IDE installed
- Workspace created (see [WORKSPACE-SETUP_EN.md](WORKSPACE-SETUP_EN.md))
- WoltLab Core available

## Step 1: Open Workspace

Open the workspace file in Cursor:

```bash
cursor woltlab-plugin-dev.code-workspace
```

Or via menu:
1. `File → Open Workspace from File...`
2. Select `woltlab-plugin-dev.code-workspace`

## Step 2: Install Extensions

Cursor will automatically suggest recommended extensions. Install:

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

The Intelephense configuration is already included in the workspace:

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

### Manual Adjustment

If you want to adjust the configuration:

1. Open `woltlab-plugin-dev.code-workspace`
2. Edit the `settings` section
3. Save the file
4. Cursor automatically reloads changes

## Step 4: Restart IDE

After installing extensions:

1. Close Cursor completely
2. Open Cursor again
3. Open the workspace

## Step 5: Test Auto-Completion

Create a test file:

```php
<?php
use wcf\system\WCF;
use wcf\data\DatabaseObject;

// Auto-completion should work here:
$db = WCF::getDB();  // <- Cursor + Space should show methods
```

### Expected Behavior

- No red squiggles on `use wcf\...` statements
- Auto-completion on `WCF::` shows methods
- Auto-completion on `FormContainer::create()` shows parameters
- No "Class not found" errors

## Troubleshooting

### "Class not found" errors persist

1. **Check includePaths:**
   ```bash
   cat woltlab-plugin-dev.code-workspace | grep includePaths
   ```

2. **Check if core path is correct:**
   ```bash
   ls -la "/path/to/woltlab/core/lib"
   ```

3. **Clear Intelephense cache:**
   ```bash
   rm -rf ~/.cache/intelephense/
   ```

4. **Restart Cursor completely**

### Auto-completion doesn't work

1. Check if Intelephense is installed: `Ctrl+Shift+X` → "Intelephense"
2. Check Cursor console for errors: `Help → Toggle Developer Tools`
3. Restart Intelephense: `Ctrl+Shift+P` → "Intelephense: Restart"

### Too many errors in IDE

The workspace configuration already disables many undefined diagnostics. If too many errors are still shown:

```json
{
  "intelephense.diagnostics.undefinedTypes": false,
  "intelephense.diagnostics.undefinedMethods": false,
  "intelephense.diagnostics.undefinedConstants": false,
  "intelephense.diagnostics.undefinedFunctions": false,
  "intelephense.diagnostics.undefinedClasses": false
}
```

## Best Practices

1. **Use workspace:** Always open the workspace file, not individual directories
2. **Keep extensions updated:** Regularly update extensions
3. **Clear cache:** Clear Intelephense cache when having issues
4. **Separate workspaces:** Different projects in different workspaces

## Further Information

- **[WORKSPACE-SETUP_EN.md](WORKSPACE-SETUP_EN.md)** - Workspace configuration
- **[INSTALLATION_EN.md](INSTALLATION_EN.md)** - Installation
- [Cursor Documentation](https://cursor.sh/docs)

