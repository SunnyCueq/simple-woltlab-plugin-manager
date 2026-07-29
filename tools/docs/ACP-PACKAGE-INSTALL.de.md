# Paket-Update via ACP (lokal)

So testest du gebaute Plugins in deiner **lokalen** WoltLab-Installation über das ACP (Admin Control Panel).

!!! note "Optional — nur mit lokaler Testinstanz"

    Dieser Guide gilt, wenn du Docker (oder eine vergleichbare lokale Suite) nutzt. **Build und Validate brauchen kein ACP.** Container-Name und ACP-URL kannst du in `tools/.env` setzen (`WOLTLAB_DOCKER_CONTAINER`, `WOLTLAB_ACP_INSTALL_URL`).

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

Der ACP-Installer schreibt als **`www-data`**. Dateien von `docker cp` gehören oft dem Host-User (z. B. UID 1000) — das Update scheitert dann mit *Permission denied*.

**Vor ACP-Upload prüfen:**

```bash
./tools/check-woltlab-app-permissions.sh
```

**Nach jedem `docker cp` in den Container:**

```bash
./tools/fix-woltlab-app-permissions.sh
```

`prepare-acp-install.sh` führt den Fix nach dem Kopieren des Pakets automatisch aus.  
Details: [`DOCKER-APP-PERMISSIONS.de.md`](DOCKER-APP-PERMISSIONS.md)

## Manueller ACP-Upload

Der Datei-Dialog (**„Datei auswählen“**) im ACP wird **manuell** bedient:

1. Build + `prepare-acp-install.sh` ausführen,
2. im ACP zur Paketinstallation navigieren,
3. die vorbereitete `.tar.gz` auswählen,
4. Installation bestätigen und testen.

## Paket-Pfad (nach `prepare-acp-install.sh`)

Im Container: `/var/www/html/de.vendor.myapp_vX.Y.Z.tar.gz`
Lokal: `releases/<plugin-ordner>/de.vendor.myapp_v*.tar.gz` (z. B. `releases/basis-plugin/…`)

## Dev-Fallback (nur wenn ACP-Upload nicht möglich)

`tools/install-package-once.php` – non-interaktives CLI-Update mit Admin-Session.

## Projekt abgleichen (DevTools) — nicht dasselbe wie Paket-Install

| Weg | Wann | SWPM |
|-----|------|------|
| **Projekt abgleichen** | Tägliche Entwicklung: PIPs aus dem DevTools-Projekt in die laufende Instanz spielen (Dateien, Templates, …) | **Nicht enthalten** — läuft im ACP unter Entwicklung → Projekte |
| **Hotfix** (`docker cp` + Rechte-Fix) | Schnell PHP/JS/Templates in Docker testen, ohne PIP-Lauf | Optional, siehe [Docker-Rechte](DOCKER-APP-PERMISSIONS.md) |
| **Paket-Install (ACP)** | Volle Installation/Update wie im Store (PIPs, DB, …) | [prepare-acp-install.sh](ACP-PACKAGE-INSTALL.md) + manueller Upload |

WoltLab bietet dafür **kein offizielles CLI**. Alexander Ebert im Community-Thread [„Projekt schneller abgleichen“](https://www.woltlab.com/community/thread/305735-projekt-schneller-abgleichen/): Paketinstallation unterstützt keine CLI-Aufrufe — „hartes Nein“. Praxis bei WoltLab: meist **gezielter Abgleich einzelner PIPs** (oft &lt; 0,1 s), selten „Alles abgleichen“.

!!! tip "ACP-Tipp (WoltLab)"

    In der Projektliste liegt der Fokus im Filterfeld — mit Pfeiltasten + **Enter** kommst du direkt in die Sync-Ansicht.

Community-Workaround (AJAX/cURL nach ACP-Login) ist im Thread beschrieben, aber **fragil** (Session, XSRF, WSC-Version) und nicht Teil von SWPM. Für Store-Plugins gilt zusätzlich: **kein eigener Paketserver** — Updates über den Plugin-Store ([Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/)).
