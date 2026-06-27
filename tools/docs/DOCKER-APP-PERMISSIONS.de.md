# Docker — App-Berechtigungen (ACP-Updates)

## Symptom

ACP-Paket-Update bricht ab:

```
error(s) during the installation of the files.
fopen(/var/www/html/shrinkr/lib/...): Failed to open stream: Permission denied
```

Der WoltLab-Installer läuft als **`www-data`**. Dateien mit anderem Besitzer (z. B. Host-UID `1000` nach `docker cp`) kann er nicht überschreiben.

## Ursache

| Aktion | Besitzer danach | ACP-Update |
|--------|-----------------|------------|
| ACP/CLI-Paket-Install | `www-data` | OK |
| `docker cp` vom Host | oft UID 1000 / root | **blockiert Updates** |
| Agent-Hotfix per `docker cp` | oft UID 1000 | **blockiert Updates** |

## Regel (Pflicht)

**Nach jedem `docker cp` in den Web-Container:**

```bash
./tools/fix-woltlab-app-permissions.sh
```

Optional vor ACP-Upload prüfen:

```bash
./tools/check-woltlab-app-permissions.sh
```

Umgebungsvariablen (optional):

- `WOLTLAB_DOCKER_CONTAINER` (Default: `woltlab-web`)
- `WOLTLAB_WEB_USER` / `WOLTLAB_WEB_GROUP` (Default: `www-data`)

## Standard-Pfade

Das Fix-Skript setzt Besitzer für:

- `/var/www/html/shrinkr`
- `/var/www/html/shrinkr-max-test.php`
- `/var/www/html/shrinkr-cron-run.php`

Weitere Pfade als Argument: `./tools/fix-woltlab-app-permissions.sh /var/www/html/andere-app`

## Hängende Installations-Queue

Nach fehlgeschlagenem Update ggf. Queue bereinigen (MySQL):

```sql
DELETE FROM wcf1_package_installation_queue
WHERE done = 0 AND package = 'de.sunnyc.wsc.shrinkr';
```

Danach ACP-Upload erneut starten.

## Integration

- `prepare-acp-install.sh` — ruft Fix nach `docker cp` auf
- `wsc-shr1nkr/tools/run-all-shrinkr-tests.sh` — ruft Fix nach Deploy der Test-Skripte auf

## Nicht tun

- Plugin-Dateien per `docker cp` hotfixen **ohne** anschließendes `fix-woltlab-app-permissions.sh`
- ACP-Update versuchen, während `check-woltlab-app-permissions.sh` fehlschlägt
