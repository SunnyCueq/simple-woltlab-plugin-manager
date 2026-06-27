# WoltLab Plugin Recovery Tool

> Notfall-Wiederherstellung für WoltLab Suite 6.0+ — repariert das ACP, deinstalliert defekte Plugins, stellt fehlende Plugin-Dateien wieder her und löscht Caches, auch wenn das Admin-Panel nicht erreichbar ist.

**Sprache / Language:** [Deutsch](README.md) | [English](README.en.md)

[![Release](https://img.shields.io/github/v/release/benjarogit/sc-woltlab-plugin-recovery)](https://github.com/benjarogit/sc-woltlab-plugin-recovery/releases)
![WoltLab Suite](https://img.shields.io/badge/WoltLab%20Suite-6.0+-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

<p align="center">
  <img src="https://github.com/user-attachments/assets/387d02cb-4d84-47e9-8d2b-aac2cebccf8a" width="100%">
</p>

## Installation

Alles Nötige liegt unter **[Releases](https://github.com/benjarogit/sc-woltlab-plugin-recovery/releases)** — nicht „Code herunterladen“.

1. **[`plugin-recovery-tool.php` herunterladen](https://github.com/benjarogit/sc-woltlab-plugin-recovery/releases/latest/download/plugin-recovery-tool.php)** und ins **WoltLab-Hauptverzeichnis** legen (neben `global.php`).
2. Im Browser aufrufen: `https://ihre-domain.de/plugin-recovery-tool.php`
3. Auth durchführen (siehe unten) — danach lädt das Tool das Paket **`recovery-2.0.0.tar.gz`** von GitHub und entpackt es nach `recovery-tool/`.
4. Schlägt der automatische Download fehl: auf der Seite im Tool den Link zum Archiv nutzen und manuell nach `recovery-tool/` entpacken.

```
ihr-woltlab-root/
├── global.php
├── plugin-recovery-tool.php
├── plugin-recovery-auth.php      ← nach Auth-Upload
└── recovery-tool/                ← wird vom Tool angelegt
```

**Upgrade von 1.x:** Alte `plugin-recovery-tool.php` durch die neue Version aus dem Release ersetzen. Auth-Ablauf bleibt gleich.

### Anmeldung (Auth-Datei)

Beim ersten Aufruf erzeugt das Tool auf **Ihrem Server** die Datei `plugin-recovery-auth.php` (wie bei WoltLabs [Recovery-Tool](https://manual.woltlab.com/de/recovery-tool/)):

1. Tool-URL im Browser öffnen  
2. `plugin-recovery-auth.php` vom Server herunterladen und wieder ins gleiche Verzeichnis hochladen  
3. **„Recovery Tool starten“** — gültig für **24 Stunden**

### Nach der Nutzung entfernen

Button **„Recovery Tool vollständig entfernen“** im Tool verwenden. Es löscht sich selbst, die Auth-Datei, den Ordner `recovery-tool/`, `uploads/` und Recovery-Logs unter `log/`. **Nicht dauerhaft auf dem Server lassen.**

---

## Wann hilft das Tool?

- ACP zeigt Fehler wie „Call to a member function toHtml() on null“
- Plugin-Installation ist fehlgeschlagen, Admin-Panel defekt
- `ClassNotFoundException` nach partiellem Löschen von Plugin-Dateien
- Plugin soll entfernt werden, ACP ist nicht erreichbar
- Caches müssen geleert werden, nichts anderes funktioniert
- Admin-Passwort ohne ACP zurücksetzen

## Modi

| Modus | Funktion |
|--------|----------|
| **ACP Repair** | Defekte ACP-Menüeinträge per Package-ID oder Archiv entfernen |
| **Plugin Uninstall** | Plugin vollständig aus Datenbank und Dateisystem entfernen (Assistent mit Backup) |
| **User Management** | Benutzer suchen, Passwort, Gruppen, E-Mail, 2FA |
| **Cache Clear** | Templates und Cache-Verzeichnisse leeren |
| **Package List Repair** | Verwaiste Paket-Einträge in der Datenbank bereinigen |
| **Plugin-Dateien reparieren** | Fehlende `.class.php` aus Paket-Archiv wiederherstellen |
| **Recovery-Wizard** | Geführte Reparatur: Diagnose → Auswahl → Ausführung |

<p align="center">
  <img src="https://github.com/user-attachments/assets/0278a36b-419b-45be-8b60-971c1ff1d3d0" width="100%">
</p>

---

## Funktionen im Überblick

### ACP Repair

Defekte ACP-Menüeinträge entfernen (per Identifier oder hochgeladenem `.tar`/`.tar.gz`), mit Vorschau vor dem Löschen. Cache wird danach geleert.

### Plugin Uninstall

3-Schritt-Assistent: Analyse aller PIP-Ressourcen → optionales SQL-Backup → Ausführung. Dry-Run möglich. **Unwiderruflich** — vorher Datenbank sichern.

### User Management

Benutzer suchen, Passwort setzen, Gruppen, E-Mail, Konto aktivieren, 2FA deaktivieren.

### Cache Clear

Löscht `tmp/`, `cache/`, `templates/compiled/`, `acp/templates/compiled/` direkt auf dem Dateisystem.

### Package List Repair

Bereinigt verwaiste `wcf_package_installation_queue`- und `wcf_application`-Einträge.

### Plugin-Dateien reparieren & Recovery-Wizard

Paket-Archiv hochladen; das Tool erkennt fehlende Bootstrap-Klassen und kopiert Dateien aus `files.tar` / `files_wcf.tar`. Der Wizard führt Sie Schritt für Schritt durch Diagnose und Reparatur.

---

## Sicherheit

- **Sofort nach Gebrauch löschen** — voller Datenbankzugriff
- Auth-Token läuft nach 24 Stunden ab
- Geschützte Systemverzeichnisse können nicht versehentlich gelöscht werden
- Für normale Deinstallationen das WoltLab-ACP verwenden

## Voraussetzungen

- WoltLab Suite 6.0+
- PHP 8.1+
- Schreibrechte im WoltLab-Verzeichnis (inkl. `log/` für Debug bei Fehlern)

## Version & Änderungen

Aktuelle Version und Release Notes: [Releases](https://github.com/benjarogit/sc-woltlab-plugin-recovery/releases)

## Lizenz

MIT — Copyright (c) 2025 Sunny C.
