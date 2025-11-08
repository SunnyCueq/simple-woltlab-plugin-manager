# Analyse: wspackager - Konzepte für unser Projekt

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

**Referenz:** [wspackager auf GitHub](https://github.com/padarom/wspackager)  
**Status:** Archiviert (Oktober 2025), aber Konzepte sind noch relevant

---

## Übersicht

Das `wspackager` Projekt ist ein npm-Paket, das automatisch WoltLab Suite Plugins packt, indem es die `package.xml` analysiert. Es ist zwar in Node.js geschrieben und archiviert, aber die Konzepte sind sehr interessant für unser Bash-basiertes Projekt.

---

## Interessante Konzepte

### 1. ✅ Automatische `package.xml` Analyse

**Was macht es?**
- Liest die `package.xml` und analysiert alle `<instruction>` Tags
- Bestimmt automatisch, welche Dateien gepackt werden müssen
- Keine manuelle Konfiguration nötig

**Beispiel:**
```xml
<instructions type="install">
    <instruction type="file" />
    <instruction type="template" />
    <instruction type="page" />
</instructions>
```

Das Tool erkennt automatisch:
- `files.tar` (Standard für `type="file"`)
- `templates.tar` (Standard für `type="template"`)
- `page.xml` (Standard für `type="page"`)

**Für unser Projekt:**
- Unser `create-release.sh` ist aktuell statisch (sucht nach bekannten Dateien)
- Wir könnten die `package.xml` parsen und dynamisch die benötigten Dateien finden

---

### 2. ✅ PIP-Parser mit Default-Dateinamen

**Was macht es?**
- Kennt alle Standard-PIPs und ihre Default-Dateinamen
- Unterstützt auch 3rd-Party-PIPs über `--pip` Parameter

**Standard-PIPs:**
```javascript
{
    acpTemplate: 'acptemplates.tar',
    file: 'files.tar',
    language: 'language/*.xml',
    script: null,
    sql: 'install.sql',
    style: null,
    template: 'templates.tar'
}
```

**Für unser Projekt:**
- Wir könnten eine ähnliche Liste in Bash erstellen
- Automatisch die richtigen Dateien finden basierend auf den Instructions

---

### 3. ✅ Style.xml Support

**Was macht es?**
- Erkennt wenn ein `style` PIP verwendet wird
- Liest zusätzlich `style/style.xml`
- Packt zusätzliche Templates und Images basierend auf `style.xml`

**Beispiel `style.xml`:**
```xml
<style>
    <files>
        <templates>templates.tar</templates>
        <images>images.tar</images>
    </files>
</style>
```

**Für unser Projekt:**
- Aktuell nicht unterstützt
- Könnte als Feature hinzugefügt werden

---

### 4. ✅ Optional/Required Packages Support

**Was macht es?**
- Liest `<optionalpackage>` und `<requiredpackage>` Tags
- Packt diese Packages automatisch mit ein
- Sucht rekursiv nach Packages wenn nicht gefunden

**Für unser Projekt:**
- Aktuell nicht unterstützt
- Könnte als Feature hinzugefügt werden

---

### 5. ✅ Case-Insensitive Dateisuche

**Was macht es?**
- Findet Dateien auch wenn der Fall nicht stimmt
- `acpTemplates` wird genauso gefunden wie `acptemplates`

**Für unser Projekt:**
- Könnten wir mit `find -iname` implementieren

---

### 6. ✅ Tree-Struktur Output

**Was macht es?**
- Zeigt die Package-Struktur vor dem Packen an
- Hilft zu verifizieren, was gepackt wird

**Für unser Projekt:**
- Könnten wir mit `tree` oder einfachem Bash-Output implementieren

---

### 7. ✅ Intermediate Files Handling

**Was macht es?**
- Erkennt intermediate Files (z.B. `files.tar` wird zu `files.tar` im Package)
- Löscht temporäre Files nach dem Packen

**Für unser Projekt:**
- Könnten wir ähnlich implementieren

---

## Vergleich: Unser aktuelles System vs. wspackager

| Feature | Unser System | wspackager | Empfehlung |
|---------|--------------|------------|------------|
| `package.xml` Parsing | ❌ Statisch | ✅ Dynamisch | ✅ **Übernehmen** |
| PIP-Erkennung | ❌ Hardcoded | ✅ Automatisch | ✅ **Übernehmen** |
| Style.xml Support | ❌ Nicht unterstützt | ✅ Unterstützt | ⚠️ Optional |
| Optional Packages | ❌ Nicht unterstützt | ✅ Unterstützt | ⚠️ Optional |
| Case-Insensitive | ❌ Nicht unterstützt | ✅ Unterstützt | ✅ **Übernehmen** |
| Tree Output | ❌ Nicht vorhanden | ✅ Vorhanden | ✅ **Übernehmen** |
| Backup-System | ✅ Vorhanden | ❌ Nicht vorhanden | ✅ **Behalten** |
| Version-Management | ✅ Automatisch | ❌ Nicht vorhanden | ✅ **Behalten** |

---

## Konkrete Verbesserungsvorschläge

### 1. Dynamisches `package.xml` Parsing

**Aktuell:**
```bash
# Statische Liste
REQUIRED_TARS=("package.xml")
OPTIONAL_TARS=("files.tar" "templates.tar" ...)
```

**Verbesserung:**
```bash
# Parse package.xml und finde Instructions
INSTRUCTIONS=$(grep -oP '<instruction type="\K[^"]+' package.xml)

# Für jede Instruction die Standard-Datei finden
for instruction in $INSTRUCTIONS; do
    case $instruction in
        "file") FILES+=("files.tar") ;;
        "template") FILES+=("templates.tar") ;;
        "page") FILES+=("page.xml") ;;
        # ... etc
    esac
done
```

### 2. PIP-Default-Dateinamen Mapping

**Neue Datei:** `scripts/pip-defaults.sh`
```bash
#!/bin/bash
# Standard-Dateinamen für WoltLab PIPs

get_pip_default_file() {
    local pip_type="$1"
    
    case "$pip_type" in
        "file") echo "files.tar" ;;
        "template") echo "templates.tar" ;;
        "acpTemplate") echo "acptemplates.tar" ;;
        "page") echo "page.xml" ;;
        "language") echo "language/*.xml" ;;
        "sql") echo "install.sql" ;;
        *) echo "" ;;
    esac
}
```

### 3. Case-Insensitive Dateisuche

**Verbesserung:**
```bash
# Statt:
if [ -f "files.tar" ]; then

# Besser:
FOUND_FILE=$(find . -maxdepth 1 -iname "files.tar" | head -1)
if [ -n "$FOUND_FILE" ]; then
```

### 4. Tree-Struktur Output

**Neue Funktion:**
```bash
show_package_structure() {
    echo "📦 Package-Struktur:"
    echo ""
    for file in "${FILES_TO_PACKAGE[@]}"; do
        if [ -f "$file" ]; then
            echo "  ✅ $file"
        elif [ -d "$file" ]; then
            echo "  📁 $file/"
        else
            echo "  ⚠️  $file (nicht gefunden)"
        fi
    done
}
```

---

## Implementierungsplan

### Phase 1: Grundlegende Verbesserungen (Einfach)
1. ✅ Case-insensitive Dateisuche
2. ✅ Tree-Struktur Output
3. ✅ Dynamisches `package.xml` Parsing für Standard-PIPs

### Phase 2: Erweiterte Features (Mittel)
4. ⚠️ Style.xml Support
5. ⚠️ Optional/Required Packages Support

### Phase 3: Advanced Features (Schwer)
6. ⚠️ 3rd-Party-PIP Support
7. ⚠️ Rekursive Package-Suche

---

## Empfehlung

**Sofort umsetzen:**
- ✅ Dynamisches `package.xml` Parsing
- ✅ Case-insensitive Dateisuche
- ✅ Tree-Struktur Output

**Später überlegen:**
- ⚠️ Style.xml Support (nur wenn benötigt)
- ⚠️ Optional Packages (nur wenn benötigt)

**Nicht übernehmen:**
- ❌ Node.js Abhängigkeit (wir bleiben bei Bash)
- ❌ Komplexe rekursive Suche (kann zu komplex werden)

---

## Fazit

Das `wspackager` Projekt hat sehr clevere Konzepte, besonders:
1. **Automatische `package.xml` Analyse** - Das ist der größte Vorteil
2. **PIP-Default-Dateinamen** - Macht das System flexibler
3. **Tree-Output** - Gute UX

Wir sollten diese Konzepte in Bash umsetzen, um unser System intelligenter und flexibler zu machen, während wir unsere Vorteile (Backup, Version-Management) behalten.

---

**Letzte Aktualisierung:** 2025-01-08

