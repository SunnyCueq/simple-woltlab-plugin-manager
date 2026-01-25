# Changelog - Shr1nkr Plugin

**Aktuelle Version:** 2.0.23
**Status:** 🚀 Major Release - Komplette Entfernung aller Statistik-Features

## Version 2.0.23 (2026-01-25)
### Fixed
- **Password-Icon Active Status**: Password-Filter-Icon im ACP-Menü wird jetzt korrekt als "active" markiert, wenn `passwordProtected=1` gesetzt ist
  - `activeMenuItem` wird dynamisch auf `shrinkr.acp.menu.link.link.password` gesetzt

## Version 2.0.22 (2026-01-25)
### Fixed
- **Password-Icon CSS**: Password-Icon verwendet jetzt `var(--preset-orange)` mit korrekter Spezifität
  - Höhere Spezifität als `.table td.columnIcon fa-icon` um Überschreibung zu verhindern
  - Badge verwendet WoltLab ACP-Variablen (`--wcfStatusWarningText` etc.) für Theme-Kompatibilität

## Version 2.0.21 (2026-01-25)
### Fixed
- **Password-Icon CSS**: Password-Icon CSS-Regel wiederhergestellt (wurde versehentlich entfernt)

## Version 2.0.20 (2026-01-25)
### Fixed
- **Badge CSS**: Badge verwendet jetzt WoltLab ACP-Variablen statt `--preset-orange`
  - `--preset-orange` ist nur im `fa-icon` Scope verfügbar
  - Badge verwendet jetzt `--wcfStatusWarningText`, `--wcfStatusWarningBackground`, `--wcfStatusWarningBorder`
  - Automatische Anpassung an Light/Dark Mode

## Version 2.0.19 (2026-01-25)
### Fixed
- **Badge Flexbox**: Badge passt sich jetzt korrekt dem Text-Inhalt an
  - `flex-shrink: 0` und `min-width: fit-content` hinzugefügt
  - Linie passt sich automatisch an Badge-Länge an

## Version 2.0.18 (2026-01-25)
### Fixed
- **Badge Layout**: Badge wird jetzt korrekt zwischen Text und Linie eingefügt
  - Linie wird vom `shrinkr-menu-header` Container erzeugt, nicht vom Badge selbst
  - Keine doppelte Linie mehr

## Version 2.0.17 (2026-01-25)
### Fixed
- **Badge CSS**: Abstände korrigiert (Text → Badge: 6px, Badge → Linie: 4px)
- **CSS-Farbe**: `var(--preset-yellow, #e67e22)` zu `#e67e22` korrigiert

## Version 2.0.16 (2026-01-25)
### Fixed
- **Badge-Anzeige**: Badge wird jetzt korrekt zwischen Text und Linie eingefügt
  - JavaScript verwendet `insertBefore()` statt `appendChild()`
  - CSS-Linie wird vom Header-Container erzeugt

## Version 2.0.15 (2026-01-25)
### Fixed
- **Parse-Error**: Fehlende schließende Klammer in `ShrinkrLinkListPage.class.php` behoben
  - `if (!empty($linkIDs))` Block wurde nicht geschlossen
  - Fehler "Unclosed '{' on line 29" behoben

## Version 2.0.14 (2026-01-25)
### Fixed
- **Badge CSS**: Badge passt sich jetzt dem Text-Inhalt an (`min-width: fit-content`)

## Version 2.0.13 (2026-01-25)
### Fixed
- **Badge CSS**: Badge verwendet `flex-shrink: 0` und `width: auto` für korrekte Größenanpassung

## Version 2.0.12 (2026-01-25)
### Fixed
- **Badge CSS**: Abstände optimiert (Text → Badge: 6px, Badge → Linie: 4px)

## Version 2.0.11 (2026-01-25)
### Fixed
- **Badge CSS**: Linie wird jetzt vom Header-Container erzeugt, nicht vom Badge

## Version 2.0.10 (2026-01-25)
### Fixed
- **Badge CSS**: Linie wird jetzt vom Header-Container erzeugt, nicht vom Badge

## Version 2.0.9 (2026-01-25)
### Fixed
- **Badge-Anzeige**: JavaScript-Code korrigiert (Badge wird zwischen Text und Linie eingefügt)

## Version 2.0.8 (2026-01-25)
### Fixed
- **Badge-Anzeige**: Layout angepasst (`Shr1nkr DEMO, EXPERTE ─────────`)

## Version 2.0.7 (2026-01-25)
### Fixed
- **Badge-Anzeige**: Abstände korrigiert

## Version 2.0.6 (2026-01-25)
### Fixed
- **Badge-Anzeige**: CSS-Linie korrigiert

## Version 2.0.5 (2026-01-25)
### Fixed
- **Badge-Anzeige**: JavaScript-Logik korrigiert

## Version 2.0.4 (2026-01-25)
### Fixed
- **Badge-Anzeige**: Template Listener Code korrigiert

## Version 2.0.3 (2026-01-25)
### Fixed
- **Badge-Anzeige**: Smarty-Syntax korrigiert

## Version 2.0.2 (2026-01-25)
### Fixed
- **Badge-Anzeige**: PHP-Tag entfernt (WoltLab 6.1.15 erlaubt keine `{php}` Tags)

## Version 2.0.1 (2026-01-25)
### Fixed
- **Badge-Anzeige**: Template Listener für DEMO/EXPERTE Badge im ACP-Menü implementiert

## Version 2.0.0 (2026-01-25)
### ⚠️ BREAKING CHANGES
- **Komplette Statistik-Entfernung**: Alle Statistik-Features wurden vollständig entfernt
  - Button-Click-Tracking entfernt (Datenbank-Tabelle `shrinkr1_button_click`)
  - Visit-Tracking entfernt (Datenbank-Tabelle `shrinkr1_visit`)
  - Analytics-Utilities entfernt (`AnalyticsUtil`, `UserAgentUtil`)
  - Statistik-ACP-Seiten entfernt (`ButtonClickListPage`)
  - Alle TypeScript/JavaScript-Module für Statistiken entfernt
  - Alle Statistik-Templates entfernt
  - Alle Statistik-Sprachvariablen entfernt (deutsch + englisch)
  - Statistik-Menu-Eintrag und Permissions entfernt
  - Alle `.statistics*` CSS-Regeln entfernt
  - D3.js und Topojson RequireJS-Pfade entfernt
  - Demo-Statistik-Daten-Generierung entfernt

### ✅ Retained (NOT Statistics)
- **Reactions-System**: GuestReaction-Feature wurde beibehalten
  - `shrinkr1_guest_reaction` Tabelle bleibt bestehen
  - Like-Buttons auf Redirect-Seiten funktionieren weiterhin
  - Alle GuestReaction-Klassen und JavaScript-Module bleiben erhalten

### 📦 Impact
- **Datenbank**: `shrinkr1_button_click` und `shrinkr1_visit` Tabellen werden nicht mehr erstellt
- **Bestehende Installationen**: Bei Update werden diese Tabellen NICHT automatisch gelöscht
  - Manuelle Löschung erforderlich falls gewünscht
- **Code-Größe**: Plugin ist deutlich schlanker und fokussierter
- **Performance**: Kein Overhead durch Visit-/Click-Tracking mehr

### 🎯 Plugin Focus
Das Plugin konzentriert sich jetzt ausschließlich auf:
- URL-Shortening
- Redirect-Seiten mit Countdown
- Discount-Codes
- Featured Links
- Custom Buttons
- Special Events & Themes
- Reactions (Like-Buttons)
- Passwort-Schutz

---

## Version 1.1.323 (2026-01-25)
### Fixed
- **Menu Text Extension**: Template Listener Code bereinigt - `{json}` Tag entfernt
  - Das `{json var=$shrinkrDebugInfo}` Tag verursachte Template-Kompilierungsfehler
  - Alle Debug-Variablen werden jetzt als individuelle Smarty-Variablen übergeben
  - Verbesserte Console-Logs für besseres Debugging

## Version 1.1.321 (2026-01-25)
### Fixed
- **Menu Text Extension**: Template Listener von `pageMenu` mit Event `menuAfter` auf `header` mit Event `javascriptInclude` migriert
  - Das `pageMenu` Template Event wurde nicht ausgeführt, daher war der Menu-Text nicht sichtbar
  - Der neue Event in `header` ist deutlich zuverlässiger und lädt bei jedem ACP-Request
  - Debug-Logs bleiben aktiv: Zeigt konstanten-Status und Suffix-Text in der Browser-Konsole

---

## Version 1.1.320 (2026-01-24)

### 🐛 Aggressive Debug-Version

#### Was ist neu
- **FORCE TEST MODE:** Script zeigt **immer** "TEST" Text an (unabhängig von Optionen)
- **Umfassendes Console-Logging:**
  - Zeigt, ob Template Listener überhaupt geladen wird
  - Prüft, ob Konstanten `SHRINKR_INSTALL_DEMO_DATA` und `SHRINKR_EXPERTMODE_ACTIVE` definiert sind
  - Zeigt deren Werte an
  - Listet alle gefundenen Menu-Spans auf
  - Alternative Selektoren wenn nötig

#### Was du sehen solltest

**In der Console (F12):**
```
======================================
[Shrinkr Menu] TEMPLATE LISTENER LOADED!
[Shrinkr Menu] Suffix text: TEST
[Shrinkr Menu] Debug Info: [...]
======================================
[Shrinkr Menu] Found menu categories: X
[Shrinkr Menu] Checking span: ...
[Shrinkr Menu] ✓ Found Shr1nkr menu, adding suffix AFTER span
[Shrinkr Menu] ✓ Suffix added successfully!
```

**Im ACP Menu:**
```
Shr1nkr ▼ TEST
```
(in fettgelb)

#### Wichtig
**Bitte kopiere ALLE Console-Ausgaben mit `[Shrinkr Menu]` und sende sie mir!**

Das hilft zu verstehen:
1. Wird der Template Listener überhaupt geladen?
2. Existieren die Konstanten?
3. Werden Menu-Elemente gefunden?
4. Wo genau schlägt es fehl?

---

## Version 1.1.319 (2026-01-24)

### 🎯 Korrekte Menu-Positionierung: NACH dem ::after Pfeil

#### Problem verstanden
- Der Pfeil `▼` wird durch CSS `::after` erzeugt
- Text als Child des `<span>` erscheint **vor** dem `::after`
- User wollte: `Shr1nkr ▼ DEMO` (Pfeil zwischen Text)

#### Lösung
Statt den Text als Child hinzuzufügen, wird er jetzt als **Sibling** nach dem `<span>` eingefügt:

```javascript
// ALT (falsch): span.appendChild(wrapper) → "Shr1nkr DEMO ▼"
// NEU (korrekt): span.parentNode.insertBefore(wrapper, span.nextSibling) → "Shr1nkr ▼ DEMO"
```

#### Ergebnis
```
Shr1nkr ▼ DEMO
```
- "Shr1nkr" im `<span>`
- "▼" durch CSS `::after`
- "DEMO" als neues `<span>` danach (in gelb, mit 10px Abstand)

---

## Version 1.1.318 (2026-01-24)

### 🐛 Bugfix: Menu Text wird nicht angezeigt

#### Problem
- DEMO/EXPERTE Text erscheint nicht im ACP-Menu
- Smarty-Konstanten `SHRINKR_INSTALL_DEMO_DATA` und `SHRINKR_EXPERTMODE_ACTIVE` waren möglicherweise nicht verfügbar

#### Lösung
- Verwendet jetzt `{php}` Block mit `defined()` Check
- Überprüft explizit, ob Konstanten existieren
- Console-Logging hinzugefügt für Debugging:
  - Zeigt, ob Suffix-Text gesetzt wurde
  - Zeigt, wie viele Menu-Kategorien gefunden wurden
  - Zeigt, welche Spans überprüft werden
- DOM-Ready-Check hinzugefügt für besseres Timing

#### Debugging
Bitte in der Browser-Konsole nach `[Shrinkr Menu]` suchen:
- `Suffix text:` zeigt den konfigurierten Text (DEMO/EXPERTE)
- `Found menu categories:` zeigt Anzahl gefundener Menü-Elemente
- `Checking span:` zeigt alle geprüften Texte
- `Found Shr1nkr menu` erscheint, wenn das Menu gefunden wurde

---

## Version 1.1.317 (2026-01-24)

### 🐛 Bugfixes

#### JavaScript Syntax-Fehler behoben
**Problem:** `Uncaught SyntaxError: expected expression, got '&'`
**Ursache:** `http_build_query()` verwendete HTML-Entity-Encoding (`&amp;`)
**Fix:** Verwendet jetzt `PHP_QUERY_RFC3986` für korrekte URL-Encoding ohne HTML-Entities

#### Menu Text Erweiterung korrigiert
**Problem:** DEMO/EXPERTE Text wurde nicht angezeigt
**Ursache:** Falsche Smarty-Syntax im Template Listener - `{unsafe:...}` innerhalb von JavaScript String
**Fix:** Variable wird jetzt vor dem JavaScript-Block zugewiesen mit `{$shrinkrMenuSuffix|encodeJS}`

### 📝 Technische Änderungen
- **ButtonClickListPage.class.php:** `http_build_query` verwendet jetzt `PHP_QUERY_RFC3986`
- **templateListener.xml:** JavaScript verwendet jetzt korrekte Smarty-Variable mit `encodeJS` Filter
- Template Listener mit `data-relocate="true"` für besseres Timing

---

## Version 1.1.316 (2026-01-24)

### 🎯 Menu Text Erweiterung - WoltLab 6.1 konform

#### Implementierung komplett überarbeitet

**Vorher (falsch):**
- JavaScript in `buttonClickList.tpl` (nur auf einer Seite sichtbar)
- Separate Badge-Element mit absoluter Positionierung
- Hardcoded "TEST" Text
- Benötigte `MenuBadge.ts` + `MenuBadge.js` (130+ Zeilen Code)

**Nachher (WoltLab 6.1 Standard):**
- Template Listener in `templateListener.xml` (global auf allen ACP-Seiten)
- Einfacher Text-Anhang an `<span>Shr1nkr</span>`
- Dynamisch basierend auf Optionen: `SHRINKR_INSTALL_DEMO_DATA` und `SHRINKR_EXPERTMODE_ACTIVE`
- Keine TypeScript-Dateien mehr nötig

#### Ergebnis

Menu zeigt jetzt:
- `Shr1nkr ▼ DEMO` (wenn Demo-Daten installiert)
- `Shr1nkr ▼ EXPERTE` (wenn Expertenmodus aktiv)
- `Shr1nkr ▼ DEMO, EXPERTE` (wenn beide aktiv)
- Text in gelb: `var(--preset-yellow, #fbbf24)`

#### Template Listener

**Datei:** `templateListener.xml`
- **Name:** `shrinkrMenuText`
- **Template:** `pageMenu`
- **Event:** `menuAfter`
- **Logic:** Smarty prüft Optionen, JavaScript fügt Text an

### 🧹 Aufräumarbeiten

**Gelöscht:**
- `temp_edit/ts/Shrinkr/Acp/Ui/MenuBadge.ts` (nicht mehr benötigt)
- `temp_edit/js/Shrinkr/Acp/Ui/MenuBadge.js` (nicht mehr benötigt)
- CSS: Alte Badge-Styles (Zeilen 517-542) entfernt

**Entfernt aus `buttonClickList.tpl`:**
- TEST-Code (Zeilen 742-759)
- RequireJS config für MenuBadge

### 📝 Technische Details
- `templateListener.xml`: Neuer Listener `shrinkrMenuText` hinzugefügt
- `shrinkr.css`: Alte Badge-CSS komplett entfernt
- Code-Reduktion: ~130 Zeilen TypeScript/JavaScript entfernt

---

## Version 1.1.315 (2026-01-24)

### 🎯 Chart Tooltips - 100% WoltLab-native

#### Änderungen basierend auf WoltLab's `/acp/index.php?stat/` Implementierung

**Tooltip-Element:**
```html
<!-- VORHER -->
<div id="chartTooltip" class="balloonTooltip" style="display: none"></div>

<!-- NACHHER (wie WoltLab) -->
<div id="chartTooltip" class="balloonTooltip active" style="display: none"></div>
```

**Zahlenformatierung:**
```typescript
// VORHER
const valueStr = Math.round(item.datapoint[1]).toLocaleString("de-DE");

// NACHHER (wie WoltLab in WCF.ACP.js)
const valueStr = WCF.String.formatNumeric(Math.round(item.datapoint[1]));
```

**CSS:**
- ❌ Custom CSS entfernt (`#chartTooltip { ... }`)
- ✅ Nutzt WoltLab's native `.balloonTooltip` Styling aus `tooltip.scss`

**Implementierung jetzt identisch mit:**
- `/var/www/html/acp/js/WCF.ACP.js` (Zeilen 1644-1662)
- `/var/www/html/acp/templates/stat.tpl` (Zeile 79)
- `/var/www/html/style/ui/tooltip.scss`

### 📝 Geänderte Dateien
- `buttonClickList.tpl`: `class="balloonTooltip active"` hinzugefügt
- `TimeSeriesChart.ts`: `WCF.String.formatNumeric()` statt `toLocaleString()`
- `shrinkr.css`: Custom Tooltip-CSS entfernt

---

## Version 1.1.314 (2026-01-24)

### 🐛 **KRITISCHER BUGFIX**: Globale CSS-Regel entfernt

#### Problem
- **Symptom**: Abstände auf ALLEN ACP-Seiten (nicht nur Shr1nkr) waren inkonsistent
- **Ursache**: Globale CSS-Regel `section#main.main { margin-top: 0; padding-top: 0; }`
- **Auswirkung**: Überschrieb WoltLab's Standard-Abstände für **alle** Seiten im ACP

#### Lösung
- ✅ **Globale Regel entfernt**: `section#main.main` wird nicht mehr manipuliert
- ✅ **Spezifische Regel entfernt**: `.tabMenuContent .section:first-child` ohne Präfix entfernt
- ✅ **Nur noch Shr1nkr-spezifisch**: Alle verbleibenden Regeln haben `#tplButtonClickList` Präfix

#### Betroffene Zeilen (shrinkr.css)
```css
/* VORHER (FALSCH - zu global!) */
section#main.main {
    margin-top: 0;
    padding-top: 0;
}

.tabMenuContent .section:first-child {
    margin-top: 10px;
}

/* NACHHER (KORREKT - nur Shr1nkr) */
/* Regeln entfernt - WoltLab-Standard bleibt intakt */
#tplButtonClickList .tabMenuContainer .tabMenuContent .section:first-child {
    margin-top: 10px;
}
```

### 📝 Technische Details
- `shrinkr.css`: Zeile 446-449 (section#main.main) entfernt
- `shrinkr.css`: Zeile 441 (.tabMenuContent ohne Präfix) korrigiert

---

## Version 1.1.313 (2026-01-24)

### 🎨 UI-Fixes

#### Progress Bar Spalten konsistent gemacht
- **Problem**: Analytics-Tabellen (Geräte, Browser) hatten inkonsistente Spaltenbreiten
- **Ursache**: Prozentspalte hatte `columnTitle` ohne feste Breite
- **Lösung**: Feste Breite `250px` für Spalten mit Progress Bars über CSS `:has()` Selektor
- **CSS**: `#statisticsAnalytics .table td.columnTitle:has(.statisticsProgressBar)`

#### Chart Tooltips CSS verbessert
- **Hinzugefügt**: Explizites CSS-Styling für `#chartTooltip`
- **Eigenschaften**: 
  - `z-index: 10000` für sichere Anzeige über anderen Elementen
  - WoltLab-native Farben `var(--wcfTooltipBackground)` und `var(--wcfTooltipText)`
  - Box-Shadow und Border-Radius für moderne Optik
- **TypeScript**: Tooltip-Code bereits vorhanden (Flot.js `plothover` Event + `Ui/Alignment`)

### 📝 Technische Details
- `shrinkr.css`: Progress Bar Spaltenbreite + Tooltip-Styling
- `TimeSeriesChart.ts`: Tooltip-Logik bereits implementiert (keine Änderungen nötig)

---

## Version 1.1.312 (2026-01-24)

### 🎨 UI-Fix: Menü Badge Positionierung

#### Problem
- Badge wurde direkt nach `<span>Shr1nkr</span>` eingefügt
- Das `::after` Pseudo-Element (Pfeil) kam **nach** dem Badge
- Erwartung: Badge soll **rechts**, nach dem Pfeil stehen

#### Lösung
- **Absolute Positionierung**: Badge wird absolut rechts im `li` Element positioniert
- **Visueller Stil**: Badge hat jetzt gelben Hintergrund mit Padding
- **DOM-Struktur**: Badge wird direkt ans `li` Element angehängt (nicht mehr nach `span`)

#### Visuelles Ergebnis
```
Shr1nkr ▼                    [TEST]
```
(Pfeil kommt zuerst, dann Badge rechts)

### 📝 Technische Details
- `shrinkr.css`: Badge absolute Positionierung `right: 10px; top: 50%; transform: translateY(-50%)`
- `MenuBadge.ts`: `menuItem.appendChild(badge)` statt `insertBefore(badge, span.nextSibling)`

---

## Version 1.1.311 (2026-01-24)

### 🐛 Kritische Bugfixes

#### RequireJS-Pfad für Shrinkr-Module korrigiert
- **Problem**: `Shrinkr/Acp/Ui/MenuBadge` wird unter `/shrinkr/js/` statt `/js/` gesucht (404-Fehler)
- **Ursache**: WoltLab's RequireJS sucht Module im Application-Verzeichnis, aber unsere JS-Dateien liegen in WCF (`application="wcf"`)
- **Lösung**: `require.config({ paths: { 'Shrinkr': '../js/Shrinkr' } })` im Template
- **Effekt**: Alle `Shrinkr/*` Module werden jetzt korrekt unter `/js/Shrinkr/` gefunden

#### Chart-Tooltips - WoltLab-native Implementierung
- **Vordefiniertes Element**: `<div id="chartTooltip" class="balloonTooltip">` im Template
- **Intelligente Positionierung**: `Ui/Alignment` Modul verhindert Abschneiden am Viewport-Rand
- **Konsistente Formatierung**: 
  - Flot's `tickFormatter()` für Datumsanzeige
  - `toLocaleString("de-DE")` für Zahlen mit Tausendertrennzeichen
- **WoltLab-Standard**: Gleicher Ansatz wie `/acp/index.php?stat/`

#### Zeitgranularität - Simplified (temporär)
- **AJAX deaktiviert**: Dropdown führt Page Reload durch
- **Grund**: Fokus auf RequireJS- und Badge-Probleme
- **Wird später reaktiviert**: Mit korrekter dboAction-Implementierung

#### Build-System - Dynamische TypeScript-Validierung
- **Statische Validierung entfernt**: Keine Hardcoding mehr für einzelne Dateien
- **Dynamisch**: Prüft automatisch alle `.ts` Dateien
- **Ignoriert `.d.ts`**: TypeScript Definition Files werden übersprungen
- **Zeigt fehlende Dateien**: Listet alle fehlenden `.js` und `.min.js` Dateien auf

#### Menü Badge - TEST-Modus aktiv
- **Hardcoded "TEST"**: Badge-Text fest auf "TEST" gesetzt für Debugging
- **Console-Logging**: Ausführliches Logging für Diagnose

### 📝 Technische Details
- `buttonClickList.tpl`: RequireJS config + chartTooltip Element
- `TimeSeriesChart.ts`: WoltLab-native Tooltip-Implementierung in `init()` und `update()`
- `ChartController.ts`: Simplified Dropdown (Page Reload)
- `build.sh`: Dynamische TypeScript-Validierung

---

## Version 1.1.310 (2026-01-24)

### Build-Fehler
- XML-Dateien fehlten (wurden aus Git wiederhergestellt)

---

## Version 1.1.309 (2026-01-24)

### Build-Fehler
- XML-Dateien fehlten im Paket

---

## Version 1.1.308 (2026-01-24)

### 🔧 RequireJS-Konfiguration Fix

#### Menü Badge - RequireJS Pfad korrigiert
- **Problem**: `Shrinkr/Acp/Ui/MenuBadge` wird unter `/shrinkr/js/` statt `/js/` gesucht
- **Ursache**: RequireJS sucht Module im Application-Verzeichnis (shrinkr), aber Dateien liegen in WCF
- **Lösung**: `require.config({ paths: { 'Shrinkr': '../js/Shrinkr' } })` im Template hinzugefügt
- **Effekt**: Alle `Shrinkr/*` Module werden jetzt unter `/js/Shrinkr/` gefunden

#### Zeitgranularität - Simplified mit Page Reload
- **AJAX temporär deaktiviert**: Dropdown macht jetzt einen einfachen Page Reload
- **Grund**: Fokus auf MenuBadge-Problem, AJAX wird danach repariert

---

## Version 1.1.307 (2026-01-24)

### 🔧 Build-System Verbesserung

#### Dynamische TypeScript-Validierung
- **Problem**: Build-Skript hatte statische Validierung nur für `TimeSeriesChart.js`
- **Lösung**: Dynamische Validierung für **alle** TypeScript-Dateien
- **Funktionsweise**:
  - Findet automatisch alle `.ts` Dateien in `temp_edit/ts/`
  - Prüft ob entsprechende `.js` und `.min.js` Dateien existieren
  - Ignoriert `.d.ts` Dateien (TypeScript Definition Files)
  - Zeigt fehlende Dateien an, falls vorhanden
- **Vorteil**: Neue TypeScript-Module wie `MenuBadge.ts` werden automatisch validiert

### 🐛 Bugfixes

#### Zeitgranularität-Dropdown - Vereinfacht
- **AJAX temporär deaktiviert**: Dropdown führt jetzt einen einfachen Page-Reload durch
- **Grund**: AJAX-Implementierung verursachte 400-Fehler
- **Wird später repariert**: Mit korrekter dboAction-Implementierung

#### Menü Badge - TEST-Modus aktiv
- **Hardcoded "TEST"**: Badge-Text fest auf "TEST" gesetzt für Debugging
- **Console-Logging**: Zeigt `[MenuBadge] v1.1.299 - Initializing...`

---

## Version 1.1.305 (2026-01-24)

### 🔧 Build-System - Erste dynamische Validierung
- Erste Version mit dynamischer TypeScript-Validierung

---

## Version 1.1.298 (2026-01-24)

### 🐛 Kritische Bugfixes

#### AJAX-API - Umstellung auf moderne WoltLab 6.1 dboAction
- **Problem**: className-Parameter wurde falsch escaped und führte zu `Der Parameter „className" fehlt oder ist ungültig`
- **Lösung**: Umstellung von `Ajax.api()` + `_ajaxSetup()` auf modernes `Ajax.dboAction()` API
- **Vorteile**:
  - Einfachere Syntax ohne komplexes Callback-Objekt
  - Promise-basiert (`.then()` / `.catch()`)
  - Keine Backslash-Escaping-Probleme mehr
  - Entspricht WoltLab 6.1 Best Practices
- **Code**: `Ajax.dboAction("getTimeSeriesData", "shrinkr\\acp\\action\\ButtonClickListAction").payload({...}).dispatch()`

#### Menü Badges - TEST-Modus für Debugging
- **Hardcoded TEST**: Badge-Text ist jetzt fest auf "TEST" gesetzt (unabhängig von Optionen)
- **Mehr Logging**: Console zeigt `[TEST] Setting badge text to TEST` und `[TEST] MenuBadge module loaded`
- **Ziel**: Verifizieren, ob der Badge-Mechanismus grundsätzlich funktioniert

### 📝 Technische Details
- `ChartController.ts`: Komplette AJAX-Logik umgeschrieben mit `dboAction`
- `buttonClickList.tpl`: Badge-Initialisierung mit Hardcoded-Test-Wert

---

## Version 1.1.297 (2026-01-24)

### 🐛 Kritische Bugfixes

#### Chart-Tooltips - WoltLab-native Implementierung
- **WoltLab-Pattern**: Tooltip-System auf WoltLab's native Ansatz umgestellt (wie `/acp/index.php?stat/`)
- **Vordefiniertes Element**: `<div id="chartTooltip" class="balloonTooltip">` im Template hinzugefügt
- **Intelligente Positionierung**: `Ui/Alignment` Modul für smarte Tooltip-Positionierung (bleibt im Viewport)
- **Konsistente Formatierung**: 
  - `item.series.xaxis.tickFormatter()` für Datumsformatierung (nutzt Flot's eigenen Formatter)
  - `toLocaleString("de-DE")` für Zahlen mit Tausendertrennzeichen
- **Cleanup**: Span-Element wird nur einmal erstellt, nicht bei jedem Hover
- **Dark Mode**: Automatischer Dark-Mode-Support via `balloonTooltip` CSS-Klasse

#### AJAX-Fehler behoben
- **className Parameter**: Korrektur von 4 Backslashes (`\\\\`) auf 2 Backslashes (`\\`) in `ChartController.ts`
- **TypeScript Escaping**: `shrinkr\\acp\\action\\ButtonClickListAction` kompiliert jetzt zu `shrinkr\acp\action\ButtonClickListAction`
- **Fehler behoben**: "Der Parameter „className" fehlt oder ist ungültig" beim Wechsel der Zeitgranularität

#### Menü Badges - Code-Struktur korrigiert
- **MenuBadge.ts**: `init()` Funktion exportiert Klasse korrekt
- **Template**: `MenuBadge.init()` wird korrekt aufgerufen
- **Retry-Mechanismus**: Robuste Implementierung mit MutationObserver für dynamisches Menu-Loading

#### URL-Parameter - filterLinkParameters
- **PHP-Code**: Vereinfachte Conditional-Logik für führendes `&`
- **Konsistenz**: Entspricht jetzt exakt dem WoltLab-Pattern aus `UserAuthenticationFailureListPage`

### 🎨 UI-Verbesserungen
- **Tooltip-Styling**: Nutzt WoltLab's `balloonTooltip` für konsistentes Aussehen
- **Viewport-Handling**: Tooltips bleiben immer sichtbar, werden nicht mehr am Rand abgeschnitten
- **Format**: Tooltip zeigt "24.01.2026, 123 Besuche" bzw. "24.01.2026 14:30, 123 Besuche" (bei Stunden-Granularität)

### 📝 Technische Details
- **Dateien geändert**:
  - `temp_edit/acptemplates/buttonClickList.tpl` - Tooltip-Element hinzugefügt
  - `temp_edit/ts/Shrinkr/Acp/Ui/Statistics/TimeSeriesChart.ts` - Tooltip-Logik umgestellt (init + update)
  - `temp_edit/ts/Shrinkr/Acp/Ui/Statistics/ChartController.ts` - className korrigiert
  - `temp_edit/lib/acp/page/ButtonClickListPage.class.php` - filterLinkParameters vereinfacht

---

## Version 1.1.294 (2026-01-24)

### 🎨 UI-Verbesserungen

#### Chart-Tooltips - WoltLab-native Implementierung (INITIAL)
- Erste Implementierung der WoltLab-nativen Tooltips
- Vorbereitung für `Ui/Alignment` Integration

---

## Version 1.1.245 (2026-01-24)

### 🎨 UI-Verbesserungen

#### Statistik-Seite - Layout-Optimierung
- **Section Header entfernt**: Unnötige `<header class="sectionHeader">` Elemente in `statisticsAnalytics` und `statisticsCountries` entfernt
- **TabularBox Abstände**: Abstand nach oben für alle `tabularBox` Elemente in Tab-Inhalten hinzugefügt (`margin-top: var(--wcfGapMedium)`)
- **Erste Box**: Erste `tabularBox` hat keinen Abstand nach oben (`:first-child`)
- **Mehrere Boxen**: Abstand zwischen mehreren `tabularBox` Elementen bleibt bei `var(--wcfGapLarge)`

#### AJAX-Implementierung korrigiert
- **ActionProxy Fehler behoben**: `WoltLabSuite/Core/Action/Proxy` → `WoltLabSuite/Core/Ajax` korrigiert
- AJAX-Callback-Objekt mit `_ajaxSetup()` und `_ajaxSuccess()` implementiert
- Zeitgranularität Dropdown funktioniert jetzt korrekt ohne Seitenreload

---

## Version 1.1.244 (2026-01-24)

### 🐛 Bugfixes & Verbesserungen

#### Statistik-Seite - Vollständige Überarbeitung

**Section Header Abstände**
- Struktur korrigiert: `sectionHeader` jetzt direkt in `section`, nicht mehr in `tabularBox`
- CSS angepasst für WoltLab-native Styling
- Abstände entsprechen jetzt dem WoltLab-Standard

**Sprachvariablen**
- `wcf.shrinkr.statistics.percentage` zu DE und EN Sprachdateien hinzugefügt
- Chart-Legende Labels werden jetzt korrekt übersetzt angezeigt (statt roher Sprachvariablen-Keys)
- Labels werden im Template übersetzt und an TypeScript übergeben

**Zeitgranularität Dropdown**
- AJAX-Update implementiert - Chart aktualisiert sich ohne Seitenreload
- Neue `ButtonClickListAction` Klasse für AJAX-Requests erstellt
- URL wird via History API aktualisiert (ohne Reload)
- `TimeSeriesChart.update()` Methode hinzugefügt für dynamische Chart-Updates

**Copyright-Anzeige**
- **ACP**: Option-Abruf validiert und korrigiert
- **Frontend**: Komplett überarbeitet - verwendet jetzt Option statt Konstante
- Link zu `https://sunnyc.de` hinzugefügt
- Template in `RedirectPage.class.php` eingebunden
- Struktur entspricht WoltLab-Standard (`pageFooterCopyright`)

**Progressbar-Funktionalität**
- WoltLab-native `inputAddon inputAddonPasswordStrength` Struktur verwendet
- JavaScript-Initialisierung entfernt - WoltLab handhabt das automatisch
- Custom CSS entfernt - keine Überschreibung von WoltLab-Styles

#### Technische Details
- Neue Action-Klasse: `shrinkr\acp\action\ButtonClickListAction` für AJAX-Requests
- TypeScript: `TimeSeriesChart.update()` Methode für dynamische Chart-Updates
- Template: Übersetzte Labels werden an Chart-Komponente übergeben

---

## Version 1.1.243 (2026-01-24)

### 🐛 Bugfixes

#### Copyright-Option Template-Fehler behoben
- **Problem**: `method 'getOption' does not exist in class WCF` Fehler beim Zugriff auf `$__wcf->option['shrinkr_copyright_enabled']` im Template
- **Lösung**: Option wird jetzt korrekt im PHP-Code (`ButtonClickListPage.class.php`) über `Option::getOptionByName()` abgerufen und als Template-Variable `$copyrightEnabled` übergeben
- **Technische Details**:
  - Import von `wcf\data\option\Option` hinzugefügt
  - Option wird in `assignVariables()` abgerufen und an Template übergeben
  - Template verwendet jetzt `{if $copyrightEnabled}` statt `{if $__wcf->option['shrinkr_copyright_enabled']}`
  - Standardwert `true` für Rückwärtskompatibilität

---

## Version 1.1.242 (2026-01-24)

### 🌍 Sprachsystem

#### Formelle/Informelle Anrede - Vollständige Implementierung
- **39 Sprachvariablen** mit informellen Varianten ergänzt
- Alle Variablen mit formeller Anrede ("Sie", "Ihre", "Ihnen", etc.) haben jetzt `variant="informal"` Varianten
- WoltLab-Standard für automatische Variantenauswahl implementiert
- Betroffene Bereiche:
  - Option-Beschreibungen (z.B. "können Sie" → "kannst du")
  - Passwort-Eingaben (z.B. "Bitte geben Sie" → "Bitte gib")
  - Bestätigungsmeldungen (z.B. "Wollen Sie" → "Willst du", "Möchten Sie" → "Möchtest du")
  - Verwaltungsanweisungen (z.B. "Verwalten Sie" → "Verwalte", "Bearbeiten Sie" → "Bearbeite")
  - CSS-Bearbeitungshinweise (z.B. "wenn Sie wissen" → "wenn du weißt")
  - URL-Verwaltungshinweise (z.B. "Klicken Sie" → "Klicke", "Ihre Webserver-Konfiguration" → "deine Webserver-Konfiguration")
  - Weiterleitungsseiten-Texte (z.B. "Sie sollen" → "Du sollst", "Sie werden" → "Du wirst")
  - Fehlermeldungen (z.B. "Bitte wählen Sie" → "Bitte wähle", "versuchen Sie" → "versuche")

#### Technische Details
- Alle Variablen folgen dem WoltLab-Standard: `<item name="..." variant="informal">`
- Die formelle Variante bleibt als Standard erhalten
- WoltLab wählt automatisch die passende Variante basierend auf der Benutzereinstellung "Informelle Anrede verwenden"

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
