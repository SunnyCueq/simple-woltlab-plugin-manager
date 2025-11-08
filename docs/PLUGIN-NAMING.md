# Plugin-Namenskonventionen - WoltLab Suite

**Copyright (c) 2025 SunnyCueq**  
**License:** MIT (Open Source)  
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

> **⚠️ WICHTIG:** Dieser Copyright-Hinweis darf nicht entfernt werden.

---

## Übersicht

WoltLab Suite verwendet ein spezielles Namensschema für Plugins (Packages). Dieser Leitfaden erklärt, wie du dein Plugin korrekt benennen solltest.

---

## Plugin-Identifier Format

**Format:** `com.[domain].[pluginname]`

**Beispiele:**
- `com.example.test` - Beispiel-Plugin
- `com.woltlab.wcf` - WoltLab Core Framework
- `com.woltlab.blog` - WoltLab Blog
- `com.yourcompany.myplugin` - Ihr Plugin

---

## Namenskonventionen

### 1. Domain-Teil (`com`)

**Verwende:**
- `com` - Für kommerzielle/öffentliche Plugins (Standard)
- `de` - Für deutsche Plugins (optional)
- `org` - Für Open-Source-Organisationen (optional)

**Beispiele:**
- `com.example.test` ✅
- `de.example.test` ✅
- `org.example.test` ✅

### 2. Domain/Organisation (`example`)

**Verwende:**
- Deine Domain (ohne TLD)
- Dein Firmenname
- Dein GitHub-Username
- Ein eindeutiger Bezeichner

**Gute Beispiele:**
- `com.mycompany` - Ihre Firma
- `com.githubuser` - Ihr GitHub-Username
- `com.example` - Beispiel/Test

**Schlechte Beispiele:**
- `com.test` - Zu generisch
- `com.plugin` - Zu generisch
- `com.123` - Zahlen allein sind nicht ideal

### 3. Plugin-Name (`test`)

**Verwende:**
- Kleine Buchstaben
- Keine Leerzeichen
- Keine Sonderzeichen (außer Bindestrich)
- Beschreibende Namen

**Gute Beispiele:**
- `myplugin` ✅
- `my-plugin` ✅
- `blog` ✅
- `forum` ✅
- `user-management` ✅

**Schlechte Beispiele:**
- `MyPlugin` ❌ (Großbuchstaben)
- `my plugin` ❌ (Leerzeichen)
- `my_plugin` ❌ (Unterstrich - vermeiden)
- `my.plugin` ❌ (Punkt im Namen)

---

## Vollständige Beispiele

### Beispiel 1: Persönliches Plugin

**Ihr Name:** Max Mustermann  
**GitHub:** maxmustermann  
**Plugin:** Ein Forum-Plugin

**Identifier:** `com.maxmustermann.forum`

**Struktur:**
```
com.maxmustermann.forum/
├── package.xml          (name="com.maxmustermann.forum")
├── page.xml
├── files/
└── templates/
```

### Beispiel 2: Firmen-Plugin

**Firma:** Beispiel GmbH  
**Domain:** beispiel-gmbh.de  
**Plugin:** Ein Blog-Plugin

**Identifier:** `com.beispielgmbh.blog`

**Struktur:**
```
com.beispielgmbh.blog/
├── package.xml          (name="com.beispielgmbh.blog")
├── page.xml
├── files/
└── templates/
```

### Beispiel 3: Open-Source-Plugin

**Organisation:** OpenSourceOrg  
**Plugin:** Ein User-Management-Plugin

**Identifier:** `org.opensourceorg.user-management`

**Struktur:**
```
org.opensourceorg.user-management/
├── package.xml          (name="org.opensourceorg.user-management")
├── page.xml
├── files/
└── templates/
```

---

## In package.xml verwenden

Der Plugin-Identifier wird im `package.xml` verwendet:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.woltlab.com"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.woltlab.com http://www.woltlab.com/XSD/2019/package.xsd"
         name="com.example.myplugin">
    <!-- ... -->
</package>
```

**Wichtig:** Der `name`-Attribut muss exakt dem Plugin-Identifier entsprechen!

---

## Dateinamen

### TAR-Archiv

Das finale Package-Archiv sollte den Plugin-Identifier als Namen haben:

**Format:** `{plugin-identifier}.tar`

**Beispiele:**
- `com.example.test.tar`
- `com.maxmustermann.forum.tar`
- `com.beispielgmbh.blog.tar`

### Verzeichnisstruktur

Dein Plugin-Verzeichnis kann beliebig benannt werden, aber für Klarheit verwende den Identifier:

```
~/Dokumente/
└── com.example.myplugin/    ← Plugin-Verzeichnis (kann beliebig sein)
    ├── package.xml          ← name="com.example.myplugin"
    ├── page.xml
    ├── files/
    └── templates/
```

---

## Wichtige Regeln

### ✅ DO's

- Verwende Kleinbuchstaben
- Verwende Bindestriche statt Unterstriche
- Verwende beschreibende Namen
- Verwende deine Domain oder einen eindeutigen Bezeichner
- Halte es einfach und klar

### ❌ DON'Ts

- Verwende keine Großbuchstaben im Identifier
- Verwende keine Leerzeichen
- Verwende keine Sonderzeichen (außer Bindestrich)
- Verwende keine generischen Namen wie "test" oder "plugin"
- Verwende keine Zahlen allein

---

## Checkliste

Bevor du dein Plugin benennst, prüfe:

- [ ] Format: `com.[domain].[pluginname]`
- [ ] Nur Kleinbuchstaben
- [ ] Keine Leerzeichen
- [ ] Keine Sonderzeichen (außer Bindestrich)
- [ ] Eindeutiger Bezeichner
- [ ] Beschreibender Name
- [ ] Konsistent in `package.xml` verwendet

---

## Beispiel aus dem Repository

Das Beispiel-Plugin in diesem Repository verwendet:

**Identifier:** `com.example.test`

**package.xml:**
```xml
<package name="com.example.test">
    <packageinformation>
        <packagename>Simple Package</packagename>
        <!-- ... -->
    </packageinformation>
</package>
```

**page.xml:**
```xml
<page identifier="com.example.test.Test">
    <!-- ... -->
</page>
```

Siehe [example-plugin/](../example-plugin/) für das vollständige Beispiel.

---

## Hilfe

Bei Fragen zur Namenskonvention:
- Siehe [WoltLab Getting Started](https://docs.woltlab.com/6.0/getting-started/)
- Siehe [example-plugin/](../example-plugin/) für ein Beispiel
- Öffnen Sie ein Issue auf GitHub

---

**Letzte Aktualisierung:** 2025-01-08

