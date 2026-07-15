# Package update via ACP (local)

**[Deutsche Version](ACP-PACKAGE-INSTALL.de.md)**

How to test built plugins in your local WoltLab installation via the ACP (Admin Control Panel).

## ACP URL

```
https://wsc.local/acp/index.php?package-start-install/&action=install
```

Alternatively: **Configuration → Packages → Install package**

## Workflow

1. Build the plugin: `./tools/build.sh basis-plugin patch` (or `same` without a version bump)
2. Optional — copy the package into the web container:
   ```bash
   ./tools/prepare-acp-install.sh basis-plugin
   ```
3. Open the ACP → **Upload package** (dialog)
4. **Choose file** → select `.tar.gz` → **Submit**
5. Complete the installation wizard (confirm → start installation)
6. After an update: **Maintenance → Clear cache** (if needed)

## Permissions (Docker — required)

The ACP installer runs as **`www-data`**. Files from `docker cp` often belong to the host user (e.g. UID 1000) — the update then fails with *Permission denied*.

**Check before ACP upload:**

```bash
./tools/check-woltlab-app-permissions.sh
```

**After every `docker cp` into the container:**

```bash
./tools/fix-woltlab-app-permissions.sh
```

`prepare-acp-install.sh` runs the fix automatically after copying the package.  
Details: [`DOCKER-APP-PERMISSIONS.en.md`](DOCKER-APP-PERMISSIONS.en.md)

## Manual ACP upload

The file dialog (**“Choose file”**) in the ACP is used **manually**:

1. Run build + `prepare-acp-install.sh`,
2. navigate to package installation in the ACP,
3. select the prepared `.tar.gz`,
4. confirm installation and test.

## Package path (after `prepare-acp-install.sh`)

In the container: `/var/www/html/de.vendor.myapp_vX.Y.Z.tar.gz`  
Locally: `basis-plugin/de.vendor.myapp_v*.tar.gz` (or your plugin directory)

## Dev fallback (only if ACP upload is not possible)

`tools/install-package-once.php` — non-interactive CLI update with an admin session.
