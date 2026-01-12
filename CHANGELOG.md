# Changelog - Shr1nkr Plugin

**Aktuelle Version:** 1.0.41
**Status:** Produktionsbereit ✅

---

## Version 1.0.41 (2026-01-08)

### 🔧 Änderungen

#### UI/UX Verbesserungen
- **QR-Code Spalte:** Button-Klicks-Spalte entfernt und durch QR-Code-Spalte ersetzt (nur Icon, kein Text)
- **QR-Code Download:** Download-Link als Button mit Font Awesome "download" Icon umgesetzt
- **Hash-Feld:** In Link-Add-Formular ganz nach unten verschoben, autofocus auf URL-Feld
- **Tabs implementiert:**
  - Link-Edit: "Grundeinstellungen" und "Aussehen" Tabs
  - Discount-Add: 5 Tabs (Allgemein, Special, Design, Zeitraum, Zusatztext)
  - Theme-Add: 2 Tabs (Basic Settings, Colors)
- **Featured Links & Custom Buttons:** In eigene Sections mit sichtbarem Rahmen gepackt
- **Content Footer Navigation:** Spiegelt jetzt die Header-Navigation (gleiche Buttons)
- **Filter-Section:** Entfernt aus Button-Click-Liste
- **"Keine Button-Klicks"-Meldung:** Entfernt

#### Technische Verbesserungen
- **Form Builder Migration:** `ShrinkrLinkAddForm` und `ShrinkrLinkEditForm` auf `AbstractFormBuilderForm` umgestellt
- **QR-Code:** Option entfernt, QR-Code-Generierung ist jetzt immer aktiv
- **Special messageObjectType:** `de.sunnyc.wsc.shrinkr.special.additionalText` in `objectType.xml` registriert
- **Qr.js:** Wiederhergestellt mit QR-Code-Generierung und Download-Funktionalität
- **acpPageSubMenuCategoryList:** Korrigiert für alle Edit-Forms (Discount, Description, Special, Theme)
- **CSS Dark Theme:** Reload-Button Hover-Farben für Dark Theme korrigiert (Farben umgekehrt)
- **Icon-Größe:** QR-Icon auf 18px skaliert (via CSS)

#### Sprachvariablen
- `wcf.shrinkr.url.linkTitle.placeholder` hinzugefügt
- `wcf.shrinkr.yes` / `wcf.shrinkr.no` für Dropdowns hinzugefügt
- `wcf.shrinkr.design` hinzugefügt
- `wcf.shrinkr.form.tab.*` Variablen für Tabs hinzugefügt
- `wcf.shrinkr.form.section.*` Variablen für Sections hinzugefügt
- Typo in EN behoben: "activateds" → "activated"

#### Bugfixes
- `Class "WCF" not found` Fehler behoben (Backslash entfernt)
- `Undefined constant` Fehler behoben (Konstanten korrigiert)
- `const linkID` Redeclaration-Fehler behoben
- `Unknown message object type` Fehler behoben
- `An unsupported size '18'` Fehler behoben (CSS-Skalierung)
- `Child has to be a form container` Fehler behoben (FormContainer-Wrapper)

---

## Version 1.0.30 - 1.0.40

### Bereinigungen
- Alle alten Referenzen entfernt (tkrich, darkwood, Julian, benjaro, URLSHORT)
- Alle PHPDoc-Header vereinheitlicht
- CodePen-Referenzen entfernt
- Konstanten korrigiert (SHRINKR_ENABLE_* Präfix)
- CSS-Margins bereinigt
- JavaScript-Header bereinigt

---

## Version 1.0.25 - 1.0.29

### Initiale WoltLab 6.1 Migration
- Migration zu WoltLab Suite 6.1
- Form Builder API Integration
- Moderne PHP 8.1+ Features
- Type-Safety verbessert
- Deprecated Code entfernt
