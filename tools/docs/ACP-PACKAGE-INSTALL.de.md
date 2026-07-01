# Paket-Update via ACP (lokal)

Standardweg zum Testen gebauter Plugins in der lokalen WoltLab-Installation.

## ACP-URL

```
https://wsc.local/acp/index.php?package-start-install/&action=install
```

Alternativ: **Konfiguration → Pakete → Paket installieren**

## Ablauf

1. Plugin bauen: `./tools/build.sh basis-plugin patch` (oder `same` ohne Versionsbump)
2. Optional – Paket in den Web-Container kopieren:
   ```bash
   ./tools/prepare-acp-install.sh basis-plugin
   ```
3. ACP öffnen → **Paket hochladen** (Dialog)
4. **Datei auswählen** → `.tar.gz` wählen → **Absenden**
5. Installationsassistent durchklicken (Bestätigung → Installation starten)
6. Nach Update: **Wartung → Cache leeren** (falls nötig)

## Berechtigungen (Docker — Pflicht)

Der ACP-Installer schreibt als **`www-data`**. Dateien, die per `docker cp` landen, gehören oft dem Host-User (UID 1000) → Update scheitert mit *Permission denied*.

**Vor ACP-Upload prüfen:**

```bash
./tools/check-woltlab-app-permissions.sh
```

**Nach jedem `docker cp` in den Container:**

```bash
./tools/fix-woltlab-app-permissions.sh
```

`prepare-acp-install.sh` führt den Fix nach dem Kopieren des Pakets automatisch aus.  
Details: [`DOCKER-APP-PERMISSIONS.de.md`](DOCKER-APP-PERMISSIONS.de.md)

## Manueller ACP-Upload

Der Datei-Dialog (**„Datei auswählen“**) im ACP wird **manuell** bedient:

1. Build + `prepare-acp-install.sh` ausführen,
2. im ACP zur Paketinstallation navigieren,
3. die vorbereitete `.tar.gz` auswählen,
4. Installation bestätigen und testen.

## Paket-Pfad (nach `prepare-acp-install.sh`)

Im Container: `/var/www/html/de.vendor.myapp_vX.Y.Z.tar.gz`  
Lokal: `basis-plugin/de.vendor.myapp_v*.tar.gz` (oder dein Plugin-Ordner)

## Dev-Fallback (nur wenn ACP-Upload nicht möglich)

`tools/install-package-once.php` – non-interaktives CLI-Update mit Admin-Session.
Ersetzt **nicht** den ACP-Test; nur für Notfälle / CI.
