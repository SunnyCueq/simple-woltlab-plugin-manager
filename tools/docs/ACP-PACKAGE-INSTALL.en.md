# Package update via ACP (local)

How to test built plugins in your **local** WoltLab installation via the ACP (Admin Control Panel).

!!! note "Optional — only with a local test instance"

    This guide applies when you use Docker (or a similar local suite). **Build and validate do not need ACP.** You can set the container name and ACP URL in `tools/.env` (`WOLTLAB_DOCKER_CONTAINER`, `WOLTLAB_ACP_INSTALL_URL`).

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
Details: [`DOCKER-APP-PERMISSIONS.en.md`](DOCKER-APP-PERMISSIONS.md)

## Manual ACP upload

The file dialog (**“Choose file”**) in the ACP is used **manually**:

1. Run build + `prepare-acp-install.sh`,
2. navigate to package installation in the ACP,
3. select the prepared `.tar.gz`,
4. confirm installation and test.

## Package path (after `prepare-acp-install.sh`)

In the container: `/var/www/html/de.vendor.myapp_vX.Y.Z.tar.gz`
Locally: `releases/<plugin-folder>/de.vendor.myapp_v*.tar.gz` (e.g. `releases/basis-plugin/…`)

## Dev fallback (only if ACP upload is not possible)

`tools/install-package-once.php` — non-interactive CLI update with an admin session.

## Project sync (DevTools) — not the same as package install

| Path | When | SWPM |
|------|------|------|
| **Project sync** | Day-to-day development: run PIPs from a DevTools project into the running instance (files, templates, …) | **Not included** — use ACP → Development → Projects |
| **Hotfix** (`docker cp` + permission fix) | Quickly test PHP/JS/templates in Docker without a PIP run | Optional — see [Docker permissions](DOCKER-APP-PERMISSIONS.md) |
| **Package install (ACP)** | Full install/update like the store (PIPs, DB, …) | [prepare-acp-install.sh](ACP-PACKAGE-INSTALL.md) + manual upload |

WoltLab provides **no official CLI** for this. Alexander Ebert in the community thread [“Projekt schneller abgleichen”](https://www.woltlab.com/community/thread/305735-projekt-schneller-abgleichen/): package installation does not support CLI calls — a “hard no”. WoltLab’s own practice: usually **sync individual PIPs** (often &lt; 0.1 s), rarely “sync all”.

!!! tip "ACP tip (WoltLab)"

    On the project list, focus is in the filter field — arrow keys + **Enter** open the sync view directly.

A community workaround (AJAX/cURL after ACP login) is described in that thread, but it is **fragile** (session, XSRF, WSC version) and not part of SWPM. For store plugins: **no custom package server** — updates go through the Plugin Store ([guidelines](https://www.woltlab.com/pluginstore/en/guidelines/)).
