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

## Wichtig für Cursor-Agent / Browser-Automation

Der Datei-Dialog (**„Datei auswählen“**) kann **nicht** automatisiert werden
(CDP `DOM.setFileInputFiles` ist blockiert). Der Agent:

- bereitet Build + Kopie vor,
- navigiert im ACP weiter,
- **stoppt** vor dem Datei-Upload und bittet Benny, die Datei manuell zu wählen,
- setzt danach mit Bestätigung und Tests fort.

## Paket-Pfad (nach `prepare-acp-install.sh`)

Im Container: `/var/www/html/de.sunnyc.wsc.shrinkr_vX.Y.Z.tar.gz`  
Lokal zum Auswählen: `plugin-manager/basis-plugin/de.sunnyc.wsc.shrinkr_v*.tar.gz`

## Dev-Fallback (nur wenn ACP-Upload nicht möglich)

`tools/install-package-once.php` – non-interaktives CLI-Update mit Admin-Session.
Ersetzt **nicht** den ACP-Test; nur für Notfälle / CI.
