# PIP-Typen und Standard-Dateinamen - WoltLab Suite

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

**Letzte Aktualisierung:** 2025-01-08  
**Status:** Aktuell

---

Diese Dokumentation erklärt die verschiedenen PIP-Typen (Package Installation Plugins) in WoltLab Suite und ihre Standard-Dateinamen. Das Toolkit analysiert automatisch deine `package.xml` und findet die benötigten Dateien basierend auf den `<instruction>` Tags.

---

## Was sind PIPs?

**PIP** steht für **Package Installation Plugin**. PIPs sind Komponenten, die während der Installation eines Plugins verarbeitet werden. Jeder PIP-Typ hat einen Standard-Dateinamen, der verwendet wird, wenn kein expliziter Dateiname in der `package.xml` angegeben ist.

**Beispiel:**
```xml
<instructions type="install">
    <instruction type="file" />  <!-- Verwendet automatisch "files.tar" -->
    <instruction type="template" />  <!-- Verwendet automatisch "templates.tar" -->
</instructions>
```

---

## Unterstützte PIP-Typen

### TAR-Archive (Verzeichnisse)

Diese PIPs erwarten TAR-Archive, die Verzeichnisse enthalten:

#### `file`
- **Standard-Dateiname:** `files.tar`
- **Inhalt:** PHP-Dateien, Klassen, Libraries
- **Verzeichnis:** `files/`
- **Beispiel:**
  ```xml
  <instruction type="file" />
  ```
- **Erstellt mit:**
  ```bash
  cd files && tar -cf ../files.tar * && cd ..
  ```

#### `template`
- **Standard-Dateiname:** `templates.tar`
- **Inhalt:** Frontend-Templates (Smarty)
- **Verzeichnis:** `templates/`
- **Beispiel:**
  ```xml
  <instruction type="template" />
  ```
- **Erstellt mit:**
  ```bash
  cd templates && tar -cf ../templates.tar * && cd ..
  ```

#### `acpTemplate`
- **Standard-Dateiname:** `acptemplates.tar`
- **Inhalt:** ACP (Admin Control Panel) Templates
- **Verzeichnis:** `acptemplates/`
- **Beispiel:**
  ```xml
  <instruction type="acpTemplate" />
  ```
- **Erstellt mit:**
  ```bash
  cd acptemplates && tar -cf ../acptemplates.tar * && cd ..
  ```

#### `style`
- **Standard-Dateiname:** `style.tar`
- **Inhalt:** Style-Definitionen
- **Verzeichnis:** `style/`
- **Beispiel:**
  ```xml
  <instruction type="style" />
  ```
- **Hinweis:** Styles können zusätzlich `style.xml` mit weiteren Templates/Images haben

---

### XML-Dateien

Diese PIPs erwarten XML-Dateien mit spezifischen Definitionen:

#### `page`
- **Standard-Dateiname:** `page.xml`
- **Inhalt:** Seiten-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="page" />
  ```

#### `acpmenu`
- **Standard-Dateiname:** `acpmenu.xml`
- **Inhalt:** ACP-Menü-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="acpmenu" />
  ```

#### `menu`
- **Standard-Dateiname:** `menu.xml`
- **Inhalt:** Frontend-Menü-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="menu" />
  ```

#### `eventListener`
- **Standard-Dateiname:** `eventListener.xml`
- **Inhalt:** Event-Listener-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="eventListener" />
  ```

#### `templateListener`
- **Standard-Dateiname:** `templateListener.xml`
- **Inhalt:** Template-Listener-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="templateListener" />
  ```

#### `box`
- **Standard-Dateiname:** `box.xml`
- **Inhalt:** Box-Definitionen (Sidebar-Boxen)
- **Beispiel:**
  ```xml
  <instruction type="box" />
  ```

#### `userOption`
- **Standard-Dateiname:** `userOption.xml`
- **Inhalt:** Benutzer-Optionen-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="userOption" />
  ```

#### `userGroupOption`
- **Standard-Dateiname:** `userGroupOption.xml`
- **Inhalt:** Benutzergruppen-Optionen-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="userGroupOption" />
  ```

#### `cronjob`
- **Standard-Dateiname:** `cronjob.xml`
- **Inhalt:** Cronjob-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="cronjob" />
  ```

#### `objectType`
- **Standard-Dateiname:** `objectType.xml`
- **Inhalt:** Objekttyp-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="objectType" />
  ```

#### `objectTypeDefinition`
- **Standard-Dateiname:** `objectTypeDefinition.xml`
- **Inhalt:** Objekttyp-Definitionen (erweitert)
- **Beispiel:**
  ```xml
  <instruction type="objectTypeDefinition" />
  ```

#### `packageUpdateServer`
- **Standard-Dateiname:** `packageUpdateServer.xml`
- **Inhalt:** Package-Update-Server-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="packageUpdateServer" />
  ```

#### `packageUpdate`
- **Standard-Dateiname:** `packageUpdate.xml`
- **Inhalt:** Package-Update-Definitionen
- **Beispiel:**
  ```xml
  <instruction type="packageUpdate" />
  ```

---

### Verzeichnisse

#### `language`
- **Standard-Dateiname:** `language/` (Verzeichnis)
- **Inhalt:** Sprachdateien (XML)
- **Struktur:**
  ```
  language/
  ├── de.xml
  ├── en.xml
  └── ...
  ```
- **Beispiel:**
  ```xml
  <instruction type="language" />
  ```

---

### Spezielle PIPs

#### `sql`
- **Standard-Dateiname:** `install.sql`
- **Inhalt:** SQL-Installations-Skripte
- **Beispiel:**
  ```xml
  <instruction type="sql" />
  ```
- **Hinweis:** Wird nur einmalig bei der Installation ausgeführt

#### `script`
- **Standard-Dateiname:** *(kein Standard)*
- **Inhalt:** PHP-Installations-Skripte
- **Beispiel:**
  ```xml
  <instruction type="script">install.php</instruction>
  ```
- **Hinweis:** Muss explizit angegeben werden, kein Standard-Dateiname

---

## Explizite Dateinamen

Du kannst auch explizite Dateinamen angeben, wenn du von den Standard-Dateinamen abweichen möchtest:

```xml
<instructions type="install">
    <instruction type="file">custom-files.tar</instruction>
    <instruction type="template">my-templates.tar</instruction>
    <instruction type="page">custom-page.xml</instruction>
</instructions>
```

Das Toolkit findet diese Dateien automatisch (case-insensitive).

---

## Automatische Erkennung

Das Toolkit (`create-release.sh`) analysiert automatisch deine `package.xml`:

1. **Liest alle `<instruction>` Tags**
2. **Bestimmt den Standard-Dateinamen** für jeden PIP-Typ
3. **Findet die Dateien** (case-insensitive)
4. **Zeigt die Package-Struktur** vor dem Packen
5. **Packt alle gefundenen Dateien** in das finale Package

**Beispiel-Output:**
```
📦 Package-Struktur:

  ✅ package.xml (4,0K)
  ✅ page.xml (4,0K)
  ✅ files.tar (12K)
  ✅ templates.tar (8,0K)

✅ Gefundene Dateien: 4
```

---

## Best Practices

### ✅ DO's

- Verwende Standard-Dateinamen wenn möglich
- Halte die Verzeichnisstruktur konsistent
- Erstelle TAR-Archive mit `update-tars.sh` oder manuell
- Prüfe die Package-Struktur vor dem Release

### ❌ DON'Ts

- Verwende keine Großbuchstaben in Dateinamen (außer bei XML-Dateien)
- Vermeide Sonderzeichen in Dateinamen
- Packe keine unnötigen Dateien (`.git`, `.DS_Store`, etc.)

---

## Vollständige Liste

| PIP-Typ | Standard-Dateiname | Typ | Erstellt mit |
|---------|-------------------|-----|--------------|
| `file` | `files.tar` | TAR | `tar -cf files.tar files/*` |
| `template` | `templates.tar` | TAR | `tar -cf templates.tar templates/*` |
| `acpTemplate` | `acptemplates.tar` | TAR | `tar -cf acptemplates.tar acptemplates/*` |
| `style` | `style.tar` | TAR | `tar -cf style.tar style/*` |
| `page` | `page.xml` | XML | - |
| `acpmenu` | `acpmenu.xml` | XML | - |
| `menu` | `menu.xml` | XML | - |
| `eventListener` | `eventListener.xml` | XML | - |
| `templateListener` | `templateListener.xml` | XML | - |
| `box` | `box.xml` | XML | - |
| `userOption` | `userOption.xml` | XML | - |
| `userGroupOption` | `userGroupOption.xml` | XML | - |
| `cronjob` | `cronjob.xml` | XML | - |
| `objectType` | `objectType.xml` | XML | - |
| `objectTypeDefinition` | `objectTypeDefinition.xml` | XML | - |
| `packageUpdateServer` | `packageUpdateServer.xml` | XML | - |
| `packageUpdate` | `packageUpdate.xml` | XML | - |
| `language` | `language/` | Verzeichnis | - |
| `sql` | `install.sql` | SQL | - |
| `script` | *(kein Standard)* | PHP | - |

---

## Weitere Informationen

- **[PACKAGING.md](PACKAGING.md)** - Kompletter Packaging-Workflow
- **[DEVELOPER-TOOLS.md](DEVELOPER-TOOLS.md)** - Entwickler-Werkzeuge
- **[PLUGIN-NAMING.md](PLUGIN-NAMING.md)** - Plugin-Namenskonventionen
- [WoltLab Package Components Dokumentation](https://docs.woltlab.com/6.0/package-components/)

---

**Letzte Aktualisierung:** 2025-01-08

