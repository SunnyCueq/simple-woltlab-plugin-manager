# Docker: App-Berechtigungen nach `docker cp`

**[English version](DOCKER-APP-PERMISSIONS.en.md)**

## Problem

Nach `docker cp` eines Plugin-Pakets oder manuellen Kopieren von App-Dateien gehören Dateien oft **root**, der ACP-Installer läuft als **www-data**:

```
fopen(/var/www/html/myapp/lib/...): Failed to open stream: Permission denied
```

## Lösung

```bash
./tools/fix-woltlab-app-permissions.sh [plugin-dir]
./tools/check-woltlab-app-permissions.sh [plugin-dir]
```

Pfade werden aus **`package.xml`** des Plugin-Ordners abgeleitet (`application`-Attribut, Bootstrap-Datei, `js/`).

Optional in `tools/.env`:

```bash
WOLTLAB_PLUGIN_DIR=basis-plugin
WOLTLAB_PACKAGE_ID=com.vendor.myapp
WOLTLAB_APP_ABBREV=myapp
WOLTLAB_EXTRA_CONTAINER_PATHS=/var/www/html/my-dev-script.php
```

## Halbfertige Installation bereinigen

```sql
SELECT * FROM wcf1_package_installation_queue
WHERE done = 0 AND package = 'com.vendor.myapp';
```

## ACP: „Das angegebene Verzeichnis enthält bereits eine App.“

**Ursache:** WoltLab-Deinstallation löscht nur Dateien aus `wcf1_package_installation_file_log`.  
Dateien aus **`docker cp`** bleiben im App-Ordner — inkl. `global.php`.

**Reset:**

```bash
./tools/reset-app-for-acp-install.sh basis-plugin
./tools/prepare-acp-install.sh basis-plugin
```

Optional weitere Pfade: `--remove-path /var/www/html/extra.php`

## Integration

- `prepare-acp-install.sh` — ruft Fix + Check nach `docker cp` auf
