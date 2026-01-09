# PIP-Typen - Standard-Dateinamen Mapping

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

**Letzte Aktualisierung:** 2025-11-08  
**Status:** Aktuell

---

Diese Dokumentation erklärt, welche Standard-Dateinamen das Toolkit für die verschiedenen PIP-Typen verwendet. Das Toolkit analysiert automatisch deine `package.xml` und findet die benötigten Dateien basierend auf den `<instruction>` Tags.

**💡 Wichtig:** Für detaillierte Informationen zu PIP-Typen siehe die [offizielle WoltLab-Dokumentation](https://docs.woltlab.com/6.0/package-components/).

---

## Wie funktioniert es?

Das Toolkit (`create-release.sh`) analysiert deine `package.xml` automatisch:

1. **Liest alle `<instruction>` Tags** aus `package.xml`
2. **Bestimmt den Standard-Dateinamen** für jeden PIP-Typ
3. **Findet die Dateien** (case-insensitive)
4. **Zeigt die Package-Struktur** vor dem Packen
5. **Packt alle gefundenen Dateien** zusammen

**Beispiel:**
```xml
<instructions type="install">
    <instruction type="file" />        <!-- Findet automatisch "files.tar" -->
    <instruction type="template" />    <!-- Findet automatisch "templates.tar" -->
    <instruction type="page" />        <!-- Findet automatisch "page.xml" -->
</instructions>
```

---

## Standard-Dateinamen Mapping

| PIP-Typ | Standard-Dateiname | Typ |
|---------|-------------------|-----|
| `file` | `files.tar` | TAR |
| `template` | `templates.tar` | TAR |
| `acpTemplate` | `acptemplates.tar` | TAR |
| `style` | `style.tar` | TAR |
| `page` | `page.xml` | XML |
| `acpmenu` | `acpmenu.xml` | XML |
| `menu` | `menu.xml` | XML |
| `eventListener` | `eventListener.xml` | XML |
| `templateListener` | `templateListener.xml` | XML |
| `box` | `box.xml` | XML |
| `userOption` | `userOption.xml` | XML |
| `userGroupOption` | `userGroupOption.xml` | XML |
| `cronjob` | `cronjob.xml` | XML |
| `objectType` | `objectType.xml` | XML |
| `objectTypeDefinition` | `objectTypeDefinition.xml` | XML |
| `packageUpdateServer` | `packageUpdateServer.xml` | XML |
| `packageUpdate` | `packageUpdate.xml` | XML |
| `language` | `language/` | Verzeichnis |
| `sql` | `install.sql` | SQL |
| `script` | *(kein Standard)* | PHP |

---

## Explizite Dateinamen

Du kannst auch explizite Dateinamen angeben, wenn du von den Standard-Dateinamen abweichen möchtest:

```xml
<instructions type="install">
    <instruction type="file">custom-files.tar</instruction>
    <instruction type="template">my-templates.tar</instruction>
</instructions>
```

Das Toolkit findet diese Dateien automatisch (case-insensitive).

---

## Unbekannte PIP-Typen

Für unbekannte PIP-Typen (z.B. 3rd-Party-PIPs) versucht das Toolkit automatisch:
- `{pip-type}.xml` zu finden

Falls das nicht funktioniert, musst du den Dateinamen explizit in der `package.xml` angeben.

---

## Beispiel-Output

```
📦 Package-Struktur:

  ✅ package.xml (4,0K)
  ✅ page.xml (4,0K)
  ✅ files.tar (12K)
  ✅ templates.tar (8,0K)
  📁 language/ (3 Dateien)

✅ Gefundene Dateien: 5
```

---

## Weitere Informationen

- **[PACKAGING.md](PACKAGING.md)** - Kompletter Packaging-Workflow
- **[DEVELOPER-TOOLS.md](DEVELOPER-TOOLS.md)** - Entwickler-Werkzeuge
- [WoltLab Package Components Dokumentation](https://docs.woltlab.com/6.0/package-components/) - Offizielle WoltLab-Dokumentation

---

**Letzte Aktualisierung:** 2025-11-08
