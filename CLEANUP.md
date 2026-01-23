# Datei-Bereinigung - WoltLab Development

## Dateien die LÖSCHBAR sind (nicht benötigt)

### 1. Alte Plugin-Archive (können neu gebaut werden)
```
/home/benny/Dokumente/woltlab-development/
├── de.sunnyc.wsc.shrinkr_v1.0.42.tar.gz  ❌ LÖSCHBAR (alte Version)
└── basis-plugin/
    ├── de.sunnyc.wsc.shrinkr_v1.1.67.tar.gz  ❌ LÖSCHBAR
    ├── de.sunnyc.wsc.shrinkr_v1.1.68.tar.gz  ❌ LÖSCHBAR
    ├── de.sunnyc.wsc.shrinkr_v1.1.69.tar.gz  ❌ LÖSCHBAR
    ├── de.sunnyc.wsc.shrinkr_v1.1.70.tar.gz  ❌ LÖSCHBAR
    └── de.sunnyc.wsc.shrinkr_v1.1.71.tar.gz  ⚠️ BEHALTEN (aktuellste Version)
```

### 2. Temporäre Verzeichnisse
```
basis-plugin/
└── temp_edit/  ❌ LÖSCHBAR (temporär)
```

### 3. Plugin-Archive in plugins-integrieren (können neu extrahiert werden)
```
plugins-integrieren/
├── com.kittmedia.wcf.visitstatistics.tar.gz  ⚠️ OPTIONAL (kann neu extrahiert werden)
├── de.softcreatr.wsc.geolite2_v1.1.2.tar.gz  ⚠️ OPTIONAL
└── de.sunnyc.wsc.shrinkr_v1.0.42.tar.gz      ⚠️ OPTIONAL
```

## Dateien die BEHALTEN werden müssen

### Essentiell für Entwicklung
```
tools/                    ✅ BEHALTEN (alle Tools)
tools.sh                  ✅ BEHALTEN (Hauptmenü)
basis-plugin/             ✅ BEHALTEN (aktuelles Plugin)
mein-plugin/              ✅ BEHALTEN (eigene Plugins)
plugins-integrieren/      ✅ BEHALTEN (zu integrierende Plugins)
woltlab-core/             ✅ BEHALTEN (WoltLab Core)
woltlab-dev/              ✅ BEHALTEN (DDEV-Umgebung)
```

### Dokumentation (optional, aber nützlich)
```
woltlab-docs/             ⚠️ OPTIONAL (Dokumentation)
woltlab-github/           ⚠️ OPTIONAL (Referenz)
README.md                 ✅ BEHALTEN
CHANGELOG.md              ⚠️ OPTIONAL
```

## Empfohlene Bereinigung

### Schritt 1: Alte Plugin-Archive löschen
```bash
cd /home/benny/Dokumente/woltlab-development
rm -f de.sunnyc.wsc.shrinkr_v1.0.42.tar.gz
rm -f basis-plugin/de.sunnyc.wsc.shrinkr_v1.1.67.tar.gz
rm -f basis-plugin/de.sunnyc.wsc.shrinkr_v1.1.68.tar.gz
rm -f basis-plugin/de.sunnyc.wsc.shrinkr_v1.1.69.tar.gz
rm -f basis-plugin/de.sunnyc.wsc.shrinkr_v1.1.70.tar.gz
# basis-plugin/de.sunnyc.wsc.shrinkr_v1.1.71.tar.gz BEHALTEN (aktuellste)
```

### Schritt 2: Temporäre Verzeichnisse löschen
```bash
rm -rf basis-plugin/temp_edit/
```

### Schritt 3: Optional - Plugin-Archive in plugins-integrieren
```bash
# Nur wenn die extrahierten Versionen ausreichen:
rm -f plugins-integrieren/*.tar.gz
rm -f plugins-integrieren/*.zip
```

## Gesparte Größe
- Alte Plugin-Archive: ~50-200 MB (je nach Größe)
- Temporäre Dateien: ~1-10 MB

## Wichtige Hinweise
- **NICHT löschen**: `tools/`, `tools.sh`, aktuelle Plugin-Verzeichnisse
- **Backup erstellen** vor dem Löschen (falls nötig)
- Plugin-Archive können jederzeit neu gebaut werden mit `tools.sh` → Option 1 (Build)
