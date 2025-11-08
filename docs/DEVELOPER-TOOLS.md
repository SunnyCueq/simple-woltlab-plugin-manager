# Entwickler-Werkzeuge - WoltLab Suite

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

## Übersicht

WoltLab Suite bietet spezielle Entwickler-Werkzeuge, die dir bei der Plugin-Entwicklung helfen. Diese Werkzeuge sind **ausschließlich für die Entwicklung und Fehlersuche** gedacht und **nicht für den produktiven Einsatz** geeignet.

**Referenz:** [WoltLab Developer Tools Dokumentation](https://docs.woltlab.com/6.0/getting-started/#developer-tools)

---

## Entwickler-Optionen aktivieren

### Wo finde ich die Optionen?

1. Logge dich in das **Administration Control Panel (ACP)** ein
2. Navigiere zu: **Konfiguration → Optionen → Entwicklung**
3. Aktiviere die gewünschten Optionen

### Verfügbare Optionen

#### 1. Debug-Modus aktivieren

**Was macht es?**
- Aktiviert ausführliche Fehlerberichte
- Zeigt detaillierte Stack-Traces
- Zeigt PHP-Fehler direkt im Browser
- Hilft bei der Fehlersuche

**Wann verwenden?**
- ✅ Während der Plugin-Entwicklung
- ✅ Bei der Fehlersuche
- ❌ **NIEMALS im Live-Betrieb!**

**Warum deaktivieren im Live-Betrieb?**
- Sicherheitsrisiko (zeigt interne Informationen)
- Performance-Impact
- Kann sensible Daten preisgeben

#### 2. Problemanalyse im Live-Betrieb

**Was macht es?**
- Hängt die aktuelle URL an Datenbankabfragen an
- Macht Abfragen im Datenbank-Log identifizierbar
- Hilft bei Performance-Analysen

**Wann verwenden?**
- ✅ Bei Performance-Problemen
- ✅ Bei der Analyse von Datenbankabfragen
- ⚠️ Kann im Live-Betrieb verwendet werden (mit Vorsicht)

**Hinweis:**
- Erzeugt zusätzliche Log-Einträge
- Kann Datenbank-Logs vergrößern

#### 3. Benchmark aktivieren

**Was macht es?**
- Erfasst zusätzliche Daten zur Ressourcennutzung
- Misst Ausführungszeiten von Komponenten
- Hilft bei Performance-Optimierung

**Wann verwenden?**
- ✅ Bei Performance-Analysen
- ✅ Bei der Optimierung von Plugins
- ❌ **Nicht im Live-Betrieb!**

**Warum deaktivieren im Live-Betrieb?**
- Performance-Impact
- Erzeugt zusätzliche Overhead
- Nicht für produktive Umgebungen gedacht

#### 4. Entwickler-Werkzeuge aktivieren

**Was macht es?**
- Aktiviert spezielle Werkzeuge für die Plugin-Entwicklung
- Ermöglicht Projekt-Registrierung
- Ermöglicht Synchronisierung von Plugins
- Zeigt Entwickler-Menüs im ACP

**Wann verwenden?**
- ✅ Während der Plugin-Entwicklung
- ✅ Bei der Synchronisierung von Plugin-Daten
- ❌ **NIEMALS im Live-Betrieb!**

**Warum deaktivieren im Live-Betrieb?**
- Sicherheitsrisiko
- Zeigt interne Entwickler-Funktionen
- Kann zu unbeabsichtigten Änderungen führen

#### 5. Fehlende Sprachvariablen protokollieren

**Was macht es?**
- Protokolliert fehlende Sprachvariablen
- Zeigt fehlende Texte unter "Fehlende Texte" im ACP
- Hilft bei der Lokalisierung

**Wann verwenden?**
- ✅ Bei der Entwicklung mehrsprachiger Plugins
- ✅ Bei der Überprüfung der Lokalisierung
- ✅ Kann im Live-Betrieb verwendet werden (optional)

**Wo finde ich die Protokolle?**
- ACP → Konfiguration → Fehlende Texte

---

## Entwickler-Werkzeuge im Detail

### Projekt-Registrierung

**Was ist das?**
- Registriert ein Plugin-Verzeichnis als Entwickler-Projekt
- Ermöglicht Synchronisierung zwischen Dateisystem und Datenbank
- Vereinfacht die Entwicklung erheblich

**Wie verwenden?**

1. **Entwickler-Werkzeuge aktivieren** (siehe oben)
2. Navigiere zu: **Entwicklung → Projekte**
3. Klicke auf **"Projekt registrieren"**
4. Gib den **absoluten Pfad** zu deinem Plugin-Verzeichnis an
   - Beispiel: `/home/benny/Dokumente/com.example.myplugin`
   - **Wichtig:** Der Pfad muss zu dem Verzeichnis zeigen, das `package.xml` enthält

**Mass-Import:**
- Verwende den Button **"Mass-Import"**
- Gib einen Suchpfad an (z.B. `/home/benny/Dokumente/plugins/`)
- Alle direkten Unterverzeichnisse werden automatisch als Projekte registriert

### Synchronisierung

**Was ist das?**
- Synchronisiert Plugin-Daten zwischen Dateisystem und Datenbank
- Ermöglicht Re-Import von PIPs (Package Installation Plugins)
- Änderungen werden ohne manuelles Update angewendet

**Wie verwenden?**

1. **Projekt registrieren** (siehe oben)
2. Navigiere zu: **Entwicklung → Projekte**
3. Wähle dein Projekt aus
4. Klicke auf **"Synchronisieren"**
5. Wähle die PIPs aus, die synchronisiert werden sollen

**Welche PIPs können synchronisiert werden?**

Nur PIPs, die das Interface `wcf\system\devtools\pip\IIdempotentPackageInstallationPlugin` implementieren:

✅ **Kann synchronisiert werden:**
- `file` - Dateien
- `template` - Templates
- `page` - Seiten
- `language` - Sprachvariablen
- `acpMenu` - ACP-Menüs
- `menu` - Frontend-Menüs
- `eventListener` - Event-Listener
- `templateListener` - Template-Listener
- Und viele mehr...

❌ **Kann NICHT synchronisiert werden:**
- `sql` - SQL-Abfragen
- `script` - Installations-Scripts
- `database` - Datenbank-Strukturen

**Für diese PIPs musst du ein manuelles Package-Update durchführen.**

---

## Workflow für Plugin-Entwicklung

### 1. Entwicklungsumgebung einrichten

```bash
# 1. Plugin-Verzeichnis erstellen
mkdir -p ~/Dokumente/com.example.myplugin
cd ~/Dokumente/com.example.myplugin

# 2. Plugin-Struktur erstellen
# (siehe example-plugin/ für Beispiel)

# 3. Plugin installieren (einmalig)
# ACP → Pakete → Paket installieren
```

### 2. Entwickler-Werkzeuge aktivieren

1. ACP → Konfiguration → Optionen → Entwicklung
2. Aktiviere:
   - ✅ Debug-Modus
   - ✅ Entwickler-Werkzeuge
   - ✅ Fehlende Sprachvariablen protokollieren
3. Optional:
   - ✅ Benchmark (bei Performance-Analysen)
   - ✅ Problemanalyse (bei DB-Problemen)

### 3. Projekt registrieren

1. ACP → Entwicklung → Projekte
2. Klicke auf "Projekt registrieren"
3. Gib den Pfad an: `/home/benny/Dokumente/com.example.myplugin`

### 4. Entwickeln und synchronisieren

```bash
# 1. Änderungen in Dateien vornehmen
nano files/lib/page/MyPage.class.php

# 2. Template ändern
nano templates/mypage.tpl

# 3. In WoltLab synchronisieren
# ACP → Entwicklung → Projekte → [Dein Projekt] → Synchronisieren
# Wähle: file, template
# Klicke auf "Synchronisieren"
```

### 5. Testen

- Öffne die Seite im Browser
- Prüfe die Fehler (Debug-Modus zeigt Details)
- Prüfe fehlende Sprachvariablen (falls aktiviert)

### 6. Package erstellen

```bash
# 1. TAR-Archive aktualisieren
./scripts/update-tars.sh

# 2. Package erstellen (analysiert automatisch package.xml)
./scripts/create-release.sh 1.0.0
```

**Was passiert beim Package-Erstellen?**

Das Toolkit analysiert automatisch deine `package.xml`:
- ✅ Findet alle `<instruction>` Tags
- ✅ Bestimmt Standard-Dateinamen für jeden PIP-Typ
- ✅ Sucht Dateien case-insensitive
- ✅ Zeigt Package-Struktur vor dem Packen
- ✅ Packt alle gefundenen Dateien zusammen

**Siehe auch:**
- [PACKAGING.md](../docs/PACKAGING.md) - Kompletter Packaging-Workflow
- [PIP-TYPES.md](../docs/PIP-TYPES.md) - Unterstützte PIP-Typen

---

## Best Practices

### ✅ DO's

- Aktiviere Entwickler-Werkzeuge nur in Entwicklungsumgebungen
- Verwende Projekt-Registrierung für aktive Entwicklung
- Synchronisiere regelmäßig während der Entwicklung
- Prüfe fehlende Sprachvariablen regelmäßig
- Deaktiviere alle Entwickler-Optionen vor dem Live-Betrieb

### ❌ DON'Ts

- **NIEMALS** Entwickler-Werkzeuge im Live-Betrieb aktivieren
- **NIEMALS** Debug-Modus im Live-Betrieb aktivieren
- **NIEMALS** Benchmark im Live-Betrieb aktivieren
- Verwende keine Entwickler-Werkzeuge auf produktiven Servern

---

## Sicherheitshinweise

### ⚠️ WICHTIG: Sicherheit

**Entwickler-Werkzeuge sind ein Sicherheitsrisiko im Live-Betrieb!**

**Risiken:**
- Debug-Modus zeigt interne Informationen
- Entwickler-Werkzeuge ermöglichen direkten Datenbankzugriff
- Kann zu unbeabsichtigten Änderungen führen
- Kann sensible Daten preisgeben

**Schutzmaßnahmen:**
1. Entwickler-Werkzeuge nur in Entwicklungsumgebungen verwenden
2. Separate Entwicklungsumgebung einrichten
3. Vor dem Live-Betrieb alle Optionen deaktivieren
4. Regelmäßig prüfen, ob Optionen aktiviert sind

---

## Troubleshooting

### Projekt wird nicht gefunden

**Problem:** "Projekt nicht gefunden" beim Synchronisieren

**Lösung:**
1. Prüfe den Pfad (muss absolut sein)
2. Prüfe, ob `package.xml` im Verzeichnis existiert
3. Prüfe Dateiberechtigungen
4. Registriere das Projekt erneut

### Synchronisierung funktioniert nicht

**Problem:** PIPs werden nicht synchronisiert

**Lösung:**
1. Prüfe, ob der PIP synchronisierbar ist (siehe Liste oben)
2. Für `sql` und `script` musst du ein manuelles Update durchführen
3. Prüfe die Fehler im Debug-Modus

### Debug-Modus zeigt keine Fehler

**Problem:** Fehler werden nicht angezeigt

**Lösung:**
1. Prüfe, ob Debug-Modus aktiviert ist
2. Prüfe PHP-Fehlerprotokoll
3. Prüfe Browser-Konsole
4. Prüfe WoltLab-Logs

---

## Weitere Ressourcen

- [WoltLab Getting Started - Developer Tools](https://docs.woltlab.com/6.0/getting-started/#developer-tools)
- [WoltLab PHP API Dokumentation](https://docs.woltlab.com/6.0/api/)
- [WoltLab Package Components](https://docs.woltlab.com/6.0/package-components/)

---

## Hilfe

Bei Fragen zu Entwickler-Werkzeugen:
- Siehe [WoltLab Dokumentation](https://docs.woltlab.com/6.0/)
- Siehe [README_ADVANCED.md](README_ADVANCED.md) für technische Details
- Öffne ein Issue auf GitHub

---

**Letzte Aktualisierung:** 2025-01-08

