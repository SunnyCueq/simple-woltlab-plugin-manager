# GitHub Workflow Testen - Anleitung

**Copyright (c) 2025 SunnyCueq**

---

## Übersicht

Der GitHub Workflow wird automatisch ausgelöst, wenn du einen Git-Tag mit dem Format `v*` (z.B. `v1.1.1`) pusht. Diese Anleitung erklärt, wie du den Workflow manuell testest.

---

## Methode 1: Tag erstellen und pushen (empfohlen)

### Schritt 1: Tag erstellen

**Im Terminal (im Projekt-Verzeichnis):**

```bash
# Wechsle ins Projekt-Verzeichnis
cd /home/benny/Dokumente/simple-woltlab-plugin-manager

# Erstelle einen neuen Tag (z.B. v1.1.1)
git tag -a v1.1.1 -m "test: GitHub Workflow Test - v1.1.1"
```

**Was passiert?**
- Ein neuer Git-Tag wird lokal erstellt
- `-a` erstellt einen "annotated tag" (mit Nachricht)
- `-m` fügt eine Nachricht hinzu

**Alternative (ohne Nachricht):**
```bash
git tag v1.1.1
```

### Schritt 2: Tag pushen

**Im Terminal:**

```bash
# Pushe den Tag zu GitHub
git push origin v1.1.1
```

**Was passiert?**
- Der Tag wird zu GitHub gepusht
- GitHub Actions erkennt den neuen Tag
- Der Workflow startet automatisch

### Schritt 3: Workflow prüfen

**Im Browser:**

1. Öffne: https://github.com/SunnyCueq/simple-woltlab-plugin-manager/actions
2. Du siehst den laufenden Workflow "chore: Version auf 1.1.1 erhöht"
3. Klicke darauf, um Details zu sehen
4. Warte, bis der Workflow fertig ist (ca. 1-2 Minuten)

**Was der Workflow macht:**
- ✅ Checkt den Code aus
- ✅ Setzt PHP 8.1 auf
- ✅ Extrahiert Version und Package-Name aus package.xml
- ✅ Parst package.xml dynamisch
- ✅ Erstellt das Plugin-Package
- ✅ Erstellt ein GitHub Release mit dem Package

---

## Methode 2: Bestehenden Tag pushen

Falls du bereits einen Tag lokal hast:

```bash
# Zeige alle lokalen Tags
git tag -l

# Pushe einen bestimmten Tag
git push origin v1.1.0

# Oder pushe alle Tags
git push origin --tags
```

---

## Workflow-Status prüfen

### Im Terminal:

```bash
# Prüfe ob der Tag auf GitHub ist
git ls-remote --tags origin | grep v1.1.1
```

### Im Browser:

1. **Actions-Seite:**
   - https://github.com/SunnyCueq/simple-woltlab-plugin-manager/actions
   - Zeigt alle Workflow-Runs

2. **Releases-Seite:**
   - https://github.com/SunnyCueq/simple-woltlab-plugin-manager/releases
   - Zeigt alle erstellten Releases

3. **Tags-Seite:**
   - https://github.com/SunnyCueq/simple-woltlab-plugin-manager/tags
   - Zeigt alle Tags

---

## Troubleshooting

### "Tag existiert bereits"

**Problem:**
```
error: tag 'v1.1.1' already exists
```

**Lösung:**
```bash
# Lösche den lokalen Tag
git tag -d v1.1.1

# Oder verwende eine andere Version
git tag -a v1.1.2 -m "test: GitHub Workflow Test"
```

### "Workflow startet nicht"

**Mögliche Ursachen:**
1. Tag-Format falsch (muss `v*` sein, z.B. `v1.1.1`)
2. Workflow-Datei hat Syntax-Fehler
3. GitHub Actions ist deaktiviert

**Prüfen:**
```bash
# Prüfe ob der Tag gepusht wurde
git ls-remote --tags origin | grep v1.1.1

# Prüfe die Workflow-Datei
cat .github/workflows/release.yml
```

### "Workflow schlägt fehl"

**Prüfe die Logs:**
1. Gehe zu: https://github.com/SunnyCueq/simple-woltlab-plugin-manager/actions
2. Klicke auf den fehlgeschlagenen Run
3. Klicke auf "build" Job
4. Prüfe die Fehlermeldungen

**Häufige Fehler:**
- `package.xml` nicht gefunden → Prüfe ob package.xml im Repository ist
- Scripts nicht gefunden → Prüfe ob `scripts/parse-package-xml.sh` existiert
- PHP-Version → Workflow verwendet PHP 8.1

---

## Beispiel: Kompletter Test-Ablauf

```bash
# 1. Ins Projekt-Verzeichnis wechseln
cd /home/benny/Dokumente/simple-woltlab-plugin-manager

# 2. Aktuellen Stand prüfen
git status

# 3. Tag erstellen
git tag -a v1.1.1 -m "test: GitHub Workflow Test - v1.1.1"

# 4. Tag pushen
git push origin v1.1.1

# 5. Workflow prüfen (im Browser)
# https://github.com/SunnyCueq/simple-woltlab-plugin-manager/actions
```

---

## Wichtige Hinweise

### Tag-Namen

- ✅ **Richtig:** `v1.1.1`, `v2.0.0`, `v1.2.3-beta`
- ❌ **Falsch:** `1.1.1`, `version-1.1.1`, `test`

Der Workflow erwartet Tags, die mit `v` beginnen!

### Tag löschen (falls nötig)

**Lokal:**
```bash
git tag -d v1.1.1
```

**Auf GitHub:**
```bash
git push origin :refs/tags/v1.1.1
```

**⚠️ Vorsicht:** Gelöschte Tags können Probleme verursachen, wenn sie bereits verwendet wurden!

---

## Nächste Schritte

Nach erfolgreichem Workflow-Test:

1. ✅ Workflow funktioniert → Du kannst echte Releases erstellen
2. ❌ Workflow schlägt fehl → Prüfe die Fehlermeldungen und korrigiere sie

**Für echte Releases:**
- Verwende `scripts/version.sh` für Repository-Versionen
- Verwende `scripts/plugin-version.sh` für Plugin-Versionen
- Oder erstelle Tags manuell wie oben beschrieben

---

**Letzte Aktualisierung:** 2025-11-08

