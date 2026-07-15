# Docker: app permissions after `docker cp`

**[Deutsche Version](DOCKER-APP-PERMISSIONS.de.md)**

## Problem

After `docker cp` (package or app files), files often belong to **root**, while the ACP installer runs as **www-data**:

```
fopen(/var/www/html/myapp/lib/...): Failed to open stream: Permission denied
```

## Solution

```bash
./tools/fix-woltlab-app-permissions.sh [plugin-dir]
./tools/check-woltlab-app-permissions.sh [plugin-dir]
```

Paths are derived from the plugin directory’s **`package.xml`** (`application` attribute, bootstrap file, `js/`).

Optional in `tools/.env`:

```bash
WOLTLAB_PLUGIN_DIR=basis-plugin
WOLTLAB_PACKAGE_ID=com.vendor.myapp
WOLTLAB_APP_ABBREV=myapp
WOLTLAB_EXTRA_CONTAINER_PATHS=/var/www/html/my-dev-script.php
```

## Clean up incomplete installations

```sql
SELECT * FROM wcf1_package_installation_queue
WHERE done = 0 AND package = 'com.vendor.myapp';
```

## ACP: “The specified directory already contains an app.”

**Cause:** WoltLab uninstallation only removes files listed in `wcf1_package_installation_file_log`.  
Files from **`docker cp`** remain in the app directory — including `global.php`.

**Reset:**

```bash
./tools/reset-app-for-acp-install.sh basis-plugin
./tools/prepare-acp-install.sh basis-plugin
```

Optional extra paths: `--remove-path /var/www/html/extra.php`

## Integration

- `prepare-acp-install.sh` — calls fix + check after `docker cp`
