# Workspace Setup - Simple WoltLab Plugin Manager

**Last Updated:** 2025-01-08  
**Status:** Current

**Last Change:** Initial version
- Reason: Creation of workspace setup guide

---

This guide explains the multi-root workspace concept and how to configure it for WoltLab plugin development.

## What is a Multi-Root Workspace?

A multi-root workspace allows you to open multiple directories simultaneously in one IDE. This is ideal for WoltLab plugin development because you can:

- Edit your plugin directory
- Access WoltLab Core for reference
- Include main plugins as reference

## Directory Structure

Typical workspace structure:

```
Workspace (woltlab-plugin-dev.code-workspace)
├── 🎯 My Plugin                    # Your plugin (editable)
├── 🔧 WoltLab Suite Core          # WoltLab Core (Read-Only)
└── 📦 Main Plugin 1               # Reference plugin (optional)
```

## Automatic Creation

The easiest setup is via the install script:

```bash
./install.sh
```

The script asks for all required paths and automatically creates the workspace file.

## Manual Creation

If you want to create the workspace manually:

```bash
# Workspace wird automatisch von install.sh erstellt
# Siehe INSTALLATION_EN.md für Details
```

The script will guide you interactively through the creation.

## Workspace File Structure

The workspace file is a JSON file with the following structure:

```json
{
  "folders": [
    {
      "name": "🎯 My Plugin",
      "path": "/path/to/plugin"
    },
    {
      "name": "🔧 WoltLab Suite Core",
      "path": "/path/to/core"
    }
  ],
  "settings": {
    "intelephense.environment.includePaths": [
      "/path/to/core/lib"
    ]
  }
}
```

## Intelephense Configuration

The Intelephense configuration is crucial for auto-completion:

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

### Why these settings?

- **includePaths:** Tells Intelephense where to find WoltLab classes
- **phpVersion:** PHP version for syntax checking
- **undefinedTypes/Methods:** Disabled because WoltLab uses dynamic properties

## Opening Workspace

### Cursor

```bash
cursor woltlab-plugin-dev.code-workspace
```

Or via menu:
1. `File → Open Workspace from File...`
2. Select `woltlab-plugin-dev.code-workspace`

### VSCode

```bash
code woltlab-plugin-dev.code-workspace
```

Or via menu:
1. `File → Open Workspace from File...`
2. Select `woltlab-plugin-dev.code-workspace`

## Adding Directories

To add more directories:

1. Open the workspace file in the IDE
2. Add a new entry to `folders`:

```json
{
  "name": "📦 New Plugin",
  "path": "/path/to/new/plugin"
}
```

3. Save the file
4. The IDE automatically reloads the workspace

## Troubleshooting

### "Path does not exist"

Check if the paths in the workspace are correct:
- Use absolute paths (not relative)
- `~` is expanded to `$HOME`
- Windows: Use `/` instead of `\` in paths

### "Intelephense can't find classes"

1. Check `includePaths` in workspace
2. Make sure the core path is correct
3. Restart IDE
4. Clear cache: `rm -rf ~/.cache/intelephense/`

### "Workspace won't load"

1. Check JSON syntax (no trailing commas)
2. Make sure all paths exist
3. Open IDE console for error messages

## Best Practices

1. **Read-Only for Core:** Mark WoltLab Core as read-only in IDE
2. **Separate Workspaces:** Create separate workspaces for different projects
3. **Backup:** Regularly backup your workspace file
4. **Version Control:** Don't commit workspace files (except templates)

## Further Information

- **[IDE-SETUP-CURSOR_EN.md](IDE-SETUP-CURSOR_EN.md)** - Cursor-specific guide
- **[IDE-SETUP-VSCODE_EN.md](IDE-SETUP-VSCODE_EN.md)** - VSCode-specific guide

