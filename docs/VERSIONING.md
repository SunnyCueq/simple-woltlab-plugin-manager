# Versionsverwaltung - Simple WoltLab Plugin Manager

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

## Übersicht

Dieses Projekt verwendet **Semantic Versioning** (SemVer) nach dem Schema `MAJOR.MINOR.PATCH`, genau wie WoltLab Suite selbst.

**Format:** `X.Y.Z`
- **X (Major):** Breaking Changes, API-Änderungen
- **Y (Minor):** Neue Features, rückwärtskompatibel
- **Z (Patch):** Bugfixes, rückwärtskompatibel

**Beispiele:**
- `1.0.0` → `1.0.1` (Patch: Bugfix)
- `1.0.1` → `1.1.0` (Minor: Neues Feature)
- `1.1.0` → `2.0.0` (Major: Breaking Change)

---

## Automatische Versionsverwaltung

### Version-Script verwenden

Das Script `scripts/version.sh` hilft Ihnen, die Version automatisch zu erhöhen:

```bash
# Patch-Version erhöhen (Bugfix)
./scripts/version.sh patch

# Minor-Version erhöhen (Neues Feature)
./scripts/version.sh minor

# Major-Version erhöhen (Breaking Change)
./scripts/version.sh major

# Dry-Run (nur anzeigen, keine Änderungen)
./scripts/version.sh patch --dry-run
```

### Was macht das Script?

1. ✅ Liest die aktuelle Version aus `README.md`
2. ✅ Berechnet die neue Version basierend auf dem Inkrement-Typ
3. ✅ Aktualisiert die Version in allen README-Dateien
4. ✅ Erstellt einen Git-Tag `vX.Y.Z`
5. ✅ Zeigt Ihnen die nächsten Schritte

### Beispiel-Workflow

```bash
# 1. Version erhöhen
./scripts/version.sh patch

# 2. Änderungen prüfen
git diff

# 3. Committen
git add README.md README_EN.md README_ADVANCED.md
git commit -m "chore: Version auf 1.0.1 erhöht"

# 4. Tag pushen
git push origin v1.0.1

# 5. Änderungen pushen
git push origin main
```

---

## Wann welche Version erhöhen?

### Patch (1.0.0 → 1.0.1)

**Verwenden Sie Patch für:**
- Bugfixes
- Kleine Korrekturen
- Dokumentations-Updates
- Performance-Verbesserungen
- Sicherheits-Updates

**Beispiele:**
- Tippfehler in der Dokumentation korrigiert
- Install-Script-Fehler behoben
- README aktualisiert

### Minor (1.0.0 → 1.1.0)

**Verwenden Sie Minor für:**
- Neue Features
- Neue Scripts
- Neue Dokumentation
- Erweiterte Funktionalität
- Rückwärtskompatible Änderungen

**Beispiele:**
- Neues Script hinzugefügt
- Neue Dokumentations-Sektion
- Erweiterte install.sh-Funktionen
- Neue Templates

### Major (1.0.0 → 2.0.0)

**Verwenden Sie Major für:**
- Breaking Changes
- API-Änderungen
- Strukturelle Änderungen
- Nicht-rückwärtskompatible Änderungen

**Beispiele:**
- Script-Parameter geändert
- Konfigurationsformat geändert
- Erforderliche Voraussetzungen geändert

---

## Manuelle Versionsverwaltung

Falls Sie die Version manuell ändern möchten:

### 1. Version in README-Dateien aktualisieren

Suchen Sie nach:
```markdown
**Version:** 1.0.0
```

Und ändern Sie zu:
```markdown
**Version:** 1.0.1
```

**Dateien, die aktualisiert werden müssen:**
- `README.md`
- `README_EN.md`
- `README_ADVANCED.md` (falls Version erwähnt)

### 2. Git-Tag erstellen

```bash
git tag -a v1.0.1 -m "Version 1.0.1 - Bugfixes"
```

### 3. Tag pushen

```bash
git push origin v1.0.1
```

---

## Best Practices

1. **Immer Version erhöhen bei Änderungen**
   - Jede Änderung sollte eine neue Version bekommen
   - Auch kleine Dokumentations-Updates

2. **Version vor dem Push erhöhen**
   - Erhöhen Sie die Version, bevor Sie auf GitHub pushen
   - Erstellen Sie den Tag zusammen mit dem Commit

3. **Klare Commit-Messages**
   - Verwenden Sie `chore: Version auf X.Y.Z erhöht`
   - Oder beschreiben Sie die Änderungen

4. **Release-Notes**
   - Dokumentieren Sie wichtige Änderungen
   - Erstellen Sie GitHub Releases mit Changelog

---

## GitHub Releases

Nach dem Erstellen eines Tags können Sie auf GitHub ein Release erstellen:

1. Gehen Sie zu: https://github.com/SunnyCueq/simple-woltlab-plugin-manager/releases
2. Klicken Sie auf "Draft a new release"
3. Wählen Sie den Tag (z.B. `v1.0.1`)
4. Titel: `Version 1.0.1`
5. Beschreibung: Changelog mit Änderungen
6. Klicken Sie auf "Publish release"

---

## WoltLab Versionsschema

Dieses Projekt folgt dem gleichen Versionsschema wie WoltLab Suite:

- **WoltLab Suite 6.0.0** → Major Version
- **WoltLab Suite 6.1.0** → Minor Version
- **WoltLab Suite 6.1.14** → Patch Version

Wir verwenden dasselbe Schema für Konsistenz und Vertrautheit.

---

## Hilfe

Bei Fragen zur Versionsverwaltung:
- Siehe [README_ADVANCED.md](../README_ADVANCED.md) für technische Details
- Öffnen Sie ein Issue auf GitHub
- Kontaktieren Sie die Community

---

**Letzte Aktualisierung:** 2025-01-08

