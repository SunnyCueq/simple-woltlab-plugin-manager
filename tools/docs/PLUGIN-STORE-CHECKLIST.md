# Plugin Store Submission Checkliste

**Letzte Aktualisierung:** 2025-01-18  
**Version:** 1.0.0

---

## Vor der Submission: Automatische Validierung

Führe zuerst die automatische Validierung durch:

```bash
./tools/validate-plugin.sh [PLUGIN_DIR]
```

Oder über das Hauptmenü: Option **12) Plugin Validierung**

### ✅ Automatisch geprüfte Kriterien

Die folgenden Kriterien werden vom `validate-plugin.sh` Script automatisch geprüft:

- [ ] **PHP-Syntax:** Alle PHP-Dateien syntaktisch korrekt
- [ ] **XML-Syntax:** package.xml und alle PIP-XMLs fehlerfrei
- [ ] **Datei-Vollständigkeit:** Alle in package.xml deklarierten Dateien vorhanden
- [ ] **Übersetzungen:** Deutsch (de.xml) UND Englisch (en.xml) vorhanden
- [ ] **Minversion:** Unterstützte WoltLab Core Version (6.0+)
- [ ] **Keine Package-Server:** Kein packageUpdateServer PIP
- [ ] **SQL-Injection:** Keine gefährlichen Query-Patterns
- [ ] **XSS-Risiken:** Templates nutzen Escaping (|escape, |encodeJS)
- [ ] **Debug-Code:** Keine var_dump(), print_r(), console.log()
- [ ] **Test-Credentials:** Keine hardcoded Passwörter
- [ ] **API-Nutzung:** HTTPRequest/Guzzle statt file_get_contents/curl

---

## Manuelle Prüfungen (vor Submission)

### 📝 Dokumentation & Beschreibung

- [ ] **Deutsche Beschreibung:** Vollständig und aussagekräftig
- [ ] **Englische Beschreibung:** Identische Informationen wie DE
- [ ] **Screenshots:** Aussagekräftig und aktuell (Pflicht für Styles)
- [ ] **Versionshinweise:** Changelog dokumentiert Änderungen
- [ ] **Externe Links:** Nur am Ende, nur für relevante Zusatzinfos

### 🛡️ Sicherheit & Autorisierung

- [ ] **Berechtigungsprüfungen:** Alle Admin-Funktionen prüfen Permissions
- [ ] **User-Input Validierung:** Alle Eingaben werden validiert
- [ ] **SQL-Queries:** Nur Prepared Statements mit Parameter-Binding
- [ ] **Template-Output:** User-Daten werden escaped
- [ ] **File-Uploads:** Validierung von Typ, Größe, Name

### ⚡ Performance & Code-Qualität

- [ ] **DB-Queries:** Effizient (keine N+1 Problems)
- [ ] **Caching:** Teure Operationen werden gecached
- [ ] **Lazy Loading:** Große Datenmengen werden paginiert
- [ ] **Code-Duplikation:** Wiederverwendbare Funktionen ausgelagert

### 🌐 WoltLab Cloud Kompatibilität

- [ ] **HTTP-Requests:** Verwende HTTPRequest/Guzzle (Proxy-Support)
- [ ] **Keine Custom Ports:** Nur Standard HTTP/HTTPS (80/443)
- [ ] **Kein Bulk-Email:** Keine Massen-Email-Versände
- [ ] **Keine System-Befehle:** Keine exec(), shell_exec(), system()

### 📦 Package-Qualität

- [ ] **Package-Name:** Format com.domain.pluginname korrekt
- [ ] **Version:** Semantic Versioning (MAJOR.MINOR.PATCH)
- [ ] **Datum:** Aktuelles Release-Datum
- [ ] **Abhängigkeiten:** Alle requiredpackages korrekt
- [ ] **Excludedpackages:** WoltLab 7.0 Alpha ausgeschlossen (empfohlen)

---

## Workflow: Von Entwicklung zu Plugin Store

### 1. Entwicklung abgeschlossen

```bash
# Navigiere zum Plugin-Verzeichnis
cd /path/to/mein-plugin

# Baue Plugin (erstellt TAR-Archive)
./tools/build.sh mein-plugin
```

### 2. Validierung durchführen

```bash
# Automatische Validierung
./tools/validate-plugin.sh mein-plugin

# Erwartetes Ergebnis:
# ✅ Validierung erfolgreich! Keine Fehler oder Warnungen gefunden.
```

**Falls Fehler/Warnungen:** Behebe diese vor dem nächsten Schritt!

### 3. Package erstellen

```bash
# Erstelle Release-Package (wird automatisch beim Build erstellt)
# Die .tar Datei befindet sich im Plugin-Verzeichnis
```

### 4. Manuelle Tests

- [ ] Plugin auf echter WoltLab-Installation testen
- [ ] Alle Funktionen durchklicken
- [ ] Permissions testen (als User + Admin)
- [ ] Deinstallation/Neuinstallation testen
- [ ] Verschiedene Browser testen (Chrome, Firefox, Safari)

### 5. Plugin Store Submission

1. Gehe zu: https://www.woltlab.com/pluginstore/
2. Klicke "Neues Plugin hochladen"
3. Lade TAR.GZ hoch (com.example.myplugin-1.0.0.tar.gz)
4. Fülle Beschreibung aus (DE + EN identisch)
5. Lade Screenshots hoch
6. Wähle Kategorie
7. Reiche zur Prüfung ein

### 6. Warten auf Review

- **Durchschnitt:** Jede dritte Submission wird im ersten Versuch abgelehnt
- **Typische Gründe:** Sicherheit, fehlende Übersetzungen, API-Nutzung
- **Review-Zeit:** Wenige Tage bis 1 Woche

---

## Häufige Ablehnungsgründe

### 🔴 Kritisch (FEHLER)

1. **Fehlende EN-Übersetzung** → Füge language/en.xml hinzu
2. **SQL-Injection Risiken** → Verwende Prepared Statements
3. **XSS in Templates** → Verwende {|escape} für User-Daten
4. **Test-Credentials** → Entferne alle Dummy-Passwörter
5. **Package-Server Installation** → Entferne packageUpdateServer PIP

### 🟡 Wichtig (WARNUNGEN)

1. **file_get_contents() für HTTP** → Verwende HTTPRequest/Guzzle
2. **Fehlende Berechtigungsprüfungen** → Prüfe Permissions explizit
3. **Ineffiziente DB-Queries** → Optimiere N+1 Problems
4. **Debug-Code** → Entferne var_dump(), console.log()
5. **Veraltete Minversion** → Upgrade auf 6.0.0+

---

## Hilfreiche Ressourcen

- **Plugin Store Richtlinien:** https://www.woltlab.com/pluginstore/de/richtlinien/
- **WoltLab Docs 6.0:** https://docs.woltlab.com/6.0/
- **WoltLab Docs 6.1:** https://docs.woltlab.com/6.1/
- **WoltLab Docs 6.2:** https://docs.woltlab.com/6.2/
- **Security Best Practices:** https://docs.woltlab.com/6.0/php/database-access/
- **API Reference:** https://docs.woltlab.com/6.0/php/api/
- **Template Security:** https://docs.woltlab.com/6.0/view/templates/

---

**Hinweis:** Diese Checkliste basiert auf den offiziellen Plugin Store Richtlinien und Best Practices der WoltLab Community.
