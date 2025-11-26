# WoltLab Suite Versionen: 6.0, 6.1, 6.2 Kompatibilität

**Copyright (c) 2025 SunnyCueq**
**Lizenz:** MIT (Open Source)
**Repository:** https://github.com/SunnyCueq/simple-woltlab-plugin-manager

---

## Zusammenfassung: Keine Breaking Changes!

✅ **Gute Nachricht:** WoltLab 6.0, 6.1 und 6.2 haben **KEINE Breaking Changes** im Package-Format!

**Was das bedeutet:**
- Ein für 6.0 entwickeltes Plugin funktioniert auf 6.1 und 6.2
- Package.xml Format bleibt identisch
- PIP-Struktur bleibt unverändert
- API bleibt abwärtskompatibel

---

## Welche Version soll ich als Minversion wählen?

### ✅ Empfehlung: 6.0.0 (Maximale Kompatibilität)

```xml
<requiredpackages>
    <requiredpackage minversion="6.0.0">com.woltlab.wcf</requiredpackage>
</requiredpackages>
<excludedpackages>
    <excludedpackage version="7.0.0 Alpha 1">com.woltlab.wcf</excludedpackage>
</excludedpackages>
```

**Vorteile:**
- ✅ Maximale Nutzer-Reichweite (6.0, 6.1, 6.2)
- ✅ Plugin Store bevorzugt breite Kompatibilität
- ✅ Einfaches Testen (nur gegen 6.0 testen nötig)

### 🔸 Alternative: 6.1.0 (Falls neue Features benötigt)

Nur wenn du **spezifische 6.1 Features** verwendest:

```xml
<requiredpackage minversion="6.1.0">com.woltlab.wcf</requiredpackage>
```

**Wann nötig:**
- Verwendung neuer 6.1 APIs
- Abhängigkeit von 6.1 Bugfixes
- Neue Template-Features aus 6.1

### 🔹 Alternative: 6.2.0 (Selten nötig)

Nur wenn du **spezifische 6.2 Features** verwendest:

```xml
<requiredpackage minversion="6.2.0">com.woltlab.wcf</requiredpackage>
```

**Wann nötig:**
- FileProcessor mit Image-Cropping
- Neue 6.2 Editor-Features
- Neue Image-Viewer API

---

## Was ändert sich zwischen den Versionen?

### WoltLab 6.0 → 6.1

**User-Interface:**
- Kleinere UI-Verbesserungen
- Template-Updates (abwärtskompatibel)

**Entwickler:**
- Neue APIs (optional nutzbar)
- Bugfixes und Performance-Verbesserungen
- **Keine Breaking Changes**

**Für Plugins:** ✅ Keine Anpassungen nötig

---

### WoltLab 6.1 → 6.2

**User-Interface:**
- Komplett neuer Image-Viewer
- WYSIWYG-Editor erweitert
- User-Profile überarbeitet
- Neuer FileProcessor mit Cropping

**Entwickler:**
- **jQuery wird deprecated** (Removal in 6.3)
  - ⚠️ Wenn dein Plugin jQuery nutzt: Migriere zu Vanilla JS
  - jQuery wird opt-in in 6.3
- **Redis discontinued**
  - File-based Cache nutzt jetzt OPcache
  - ⚠️ Wenn du Redis nutzt: Migriere zu File-Cache
- Neue FileProcessor API (opt-in)
- Viele Template-Updates

**Für Plugins:**
- ✅ Package-Format: Keine Änderungen
- ⚠️ jQuery: Migriere zu Vanilla JS (Vorbereitung auf 6.3)
- ⚠️ Templates: Eigene Templates ggf. aktualisieren

---

## Migration Guide: jQuery Deprecation (6.2 → 6.3)

### Problem

jQuery wird in WoltLab 6.3 **opt-in** und später komplett entfernt.

### Lösung: Migriere zu Vanilla JavaScript

**Vorher (jQuery):**
```javascript
$('.my-element').on('click', function() {
    $(this).addClass('active');
});
```

**Nachher (Vanilla JS):**
```javascript
document.querySelectorAll('.my-element').forEach(element => {
    element.addEventListener('click', function() {
        this.classList.add('active');
    });
});
```

**Ressourcen:**
- [You Might Not Need jQuery](https://youmightnotneedjquery.com/)
- [WoltLab JavaScript API](https://docs.woltlab.com/6.2/javascript/)

---

## Testen gegen verschiedene Versionen

### Empfohlener Workflow

1. **Entwickle gegen 6.0** (maximale Kompatibilität)
2. **Teste auf 6.2** (neueste Features)
3. **Validiere mit validate-plugin.sh**

### Optional: Multi-Version Testing

Wenn du mehrere WoltLab-Installationen hast:

```bash
# Test gegen 6.0
./scripts/validate-plugin.sh /path/to/plugin-6.0

# Test gegen 6.2
./scripts/validate-plugin.sh /path/to/plugin-6.2
```

**Realität:** Nicht nötig, da keine Breaking Changes!

---

## WoltLab 7.0 Vorbereitung

### Excludedpackages hinzufügen

Schließe WoltLab 7.0 Alpha/Beta aus:

```xml
<excludedpackages>
    <excludedpackage version="7.0.0 Alpha 1">com.woltlab.wcf</excludedpackage>
</excludedpackages>
```

**Warum:**
- WoltLab 7 wird Breaking Changes haben
- Dein 6.x Plugin soll nicht auf 7.0 Alpha laufen
- Verhindert Fehlerberichte von Alpha-Testern

---

## Zusammenfassung & Best Practices

### ✅ Do's

- Verwende minversion="6.0.0" für maximale Kompatibilität
- Füge excludedpackages für 7.0 Alpha hinzu
- Migriere von jQuery zu Vanilla JS (Vorbereitung 6.3)
- Teste auf neuester stabiler Version (6.2)

### ❌ Don'ts

- Keine minversion < 6.0.0 (Plugin Store lehnt ab)
- Keine minversion 7.0+ (noch nicht released)
- Keine jQuery-Abhängigkeit für neue Features
- Keine Redis-Nutzung (discontinued)

---

## Hilfreiche Ressourcen

- **WoltLab Docs 6.0:** https://docs.woltlab.com/6.0/
- **WoltLab Docs 6.1:** https://docs.woltlab.com/6.1/
- **WoltLab Docs 6.2:** https://docs.woltlab.com/6.2/
- **jQuery Migration:** https://youmightnotneedjquery.com/
- **WoltLab JavaScript API:** https://docs.woltlab.com/6.2/javascript/

---

**Letzte Aktualisierung:** 2025-11-26
**Version:** 1.0.0
