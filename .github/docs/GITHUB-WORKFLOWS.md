# GitHub Workflows - Erklärung

**Copyright (c) 2025 SunnyCueq**

---

## Was sind GitHub Workflows?

**GitHub Workflows** (auch GitHub Actions genannt) sind automatische Prozesse, die auf GitHub-Servern laufen, wenn bestimmte Ereignisse eintreten (z.B. ein Git-Tag gepusht wird).

**Warum sind sie wichtig?**
- ✅ **Automatisierung:** Erledigen wiederkehrende Aufgaben automatisch
- ✅ **Konsistenz:** Gleiche Schritte jedes Mal, keine Fehler durch manuelle Arbeit
- ✅ **Zeitersparnis:** Du musst nicht manuell Releases erstellen
- ✅ **Zuverlässigkeit:** Laufen auf GitHub-Servern, unabhängig von deinem Computer

---

## Unser GitHub Workflow: `release.yml`

### Was macht er?

**Wenn du einen Git-Tag pusht (z.B. `v1.1.1`):**

1. **Code auschecken**
   - Lädt den aktuellen Code vom Repository

2. **PHP einrichten**
   - Setzt PHP 8.1 auf (für WoltLab-Kompatibilität)

3. **Version extrahieren**
   - Liest die Version aus dem Git-Tag (z.B. `v1.1.1` → `1.1.1`)

4. **Package-Name extrahieren**
   - Liest den Package-Namen aus `package.xml`

5. **Plugin-Verzeichnis finden**
   - Sucht nach `package.xml` im Root oder in Unterverzeichnissen

6. **package.xml dynamisch parsen**
   - Verwendet unsere Scripts (`parse-package-xml.sh`, `pip-defaults.sh`)
   - Findet automatisch alle benötigten Dateien

7. **Plugin-Package erstellen**
   - Erstellt ein `.tar.gz` Package mit allen benötigten Dateien

8. **GitHub Release erstellen**
   - Erstellt automatisch ein Release auf GitHub
   - Lädt das Package als Asset hoch
   - Fügt Release-Notes hinzu

---

## Workflow-Datei: `.github/workflows/release.yml`

**Wo ist sie?**
- Im Repository unter `.github/workflows/release.yml`
- Wird automatisch von GitHub erkannt

**Wann läuft sie?**
- Automatisch, wenn du einen Git-Tag mit `v*` pusht
- Beispiel: `git push origin v1.1.1`

**Was passiert lokal?**
- **NICHTS!** Der Workflow läuft nur auf GitHub-Servern
- Deine lokalen Scripts funktionieren weiterhin normal
- Du kannst weiterhin lokal arbeiten, testen und Packages erstellen

---

## Beispiel: Workflow verwenden

### Schritt 1: Tag erstellen

```bash
# Im Projekt-Verzeichnis
cd /home/benny/Dokumente/simple-woltlab-plugin-manager

# Tag erstellen
git tag -a v1.1.1 -m "Version 1.1.1"
```

### Schritt 2: Tag pushen

```bash
# Tag zu GitHub pushen
git push origin v1.1.1
```

### Schritt 3: Workflow läuft automatisch

1. GitHub erkennt den neuen Tag
2. Workflow startet automatisch
3. Du kannst den Fortschritt hier sehen:
   - https://github.com/SunnyCueq/simple-woltlab-plugin-manager/actions

### Schritt 4: Release ist fertig

- Nach ca. 1-2 Minuten ist das Release fertig
- Du findest es hier:
  - https://github.com/SunnyCueq/simple-woltlab-plugin-manager/releases
- Das Package ist automatisch als Asset hochgeladen

---

## Vorteile

### Ohne Workflow (manuell):
1. Tag erstellen
2. Package manuell erstellen
3. GitHub Release manuell erstellen
4. Package manuell hochladen
5. Release-Notes manuell schreiben

**Zeit:** ~5-10 Minuten, fehleranfällig

### Mit Workflow (automatisch):
1. Tag pushen
2. Fertig!

**Zeit:** ~30 Sekunden, automatisch und zuverlässig

---

## Wichtige Hinweise

### Tag-Format
- ✅ **Richtig:** `v1.1.1`, `v2.0.0` (muss mit `v` beginnen)
- ❌ **Falsch:** `1.1.1`, `version-1.1.1`

### Workflow-Status prüfen
- **Actions-Seite:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager/actions
- Zeigt alle Workflow-Runs
- Du siehst, ob der Workflow erfolgreich war oder fehlgeschlagen ist

### Bei Fehlern
- Prüfe die Logs auf der Actions-Seite
- Häufige Fehler:
  - `package.xml` nicht gefunden → Prüfe ob package.xml im Repository ist
  - Scripts nicht gefunden → Prüfe ob Scripts existieren
  - PHP-Version → Workflow verwendet PHP 8.1

---

## Zusammenfassung

**GitHub Workflows sind wichtig, weil:**
- ✅ Sie automatisieren wiederkehrende Aufgaben
- ✅ Sie sparen Zeit und reduzieren Fehler
- ✅ Sie sorgen für Konsistenz
- ✅ Sie laufen zuverlässig auf GitHub-Servern

**Unser Workflow:**
- ✅ Erstellt automatisch Plugin-Packages
- ✅ Erstellt automatisch GitHub Releases
- ✅ Lädt Packages automatisch hoch
- ✅ Fügt Release-Notes hinzu

**Du musst nur:**
- ✅ Einen Tag pushen
- ✅ Der Rest passiert automatisch!

---

**Letzte Aktualisierung:** 2025-11-08

