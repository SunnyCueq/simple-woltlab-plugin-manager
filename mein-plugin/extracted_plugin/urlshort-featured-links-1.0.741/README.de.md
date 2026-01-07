# URL Shortener: Affiliate-System

> 🇩🇪 Deutsche Version | **[🇬🇧 English Version](README.md)**

**Paket:** `info.benjaro.urlshort.affiliate`  
**Version:** 2.1.5  
**Autor:** Benjaro  
**Website:** https://benjaro.info

---

## 📋 Übersicht

**URL Shortener: Affiliate-System** ist eine umfassende Erweiterung für das [URL Shortener](https://www.woltlab.com/pluginstore/de/p/url-shortener/) Plugin von dev.tkirch. Es verwandelt deine einfachen Weiterleitungsseiten in leistungsstarke Marketing-Tools, perfekt für Affiliate-Marketing, Werbekampagnen und saisonale Aktionen.

Statt Besucher einfach nur weiterzuleiten, kannst du jetzt Rabattcodes, personalisierte Nachrichten, empfohlene Links und beeindruckende visuelle Effekte anzeigen - alles bei vollständiger Kontrolle über Timing, Aussehen und Zielgruppenausrichtung.

---

## ✨ Hauptfunktionen

### 🎟️ Rabattcode-Verwaltung

Zeige mehrere Rabattcodes direkt auf deinen Weiterleitungsseiten mit vollständiger Anpassung:

- **Host-basierte Zuordnung:** Weise Rabattcodes bestimmten Websites zu (z.B. `amazon.de`, `ebay.com`)
- **Mehrere Codes:** Unterstützung für mehrere Rabattcodes pro Rabatt-Eintrag
- **Ein-Klick-Kopieren:** Nutzer können Codes mit einem Klick kopieren
- **Countdown-Timer:** Erzeuge Dringlichkeit mit Live-Countdown-Timern, die die verbleibende Zeit anzeigen
- **Eigene Farben:** Vollständig anpassbare Farben (Primär-, Sekundär-, Textfarben) für Brand-Matching
- **Zeitlich begrenzte Angebote:** Setze Start- und Enddaten für automatische Aktivierung/Deaktivierung
- **Favicon-Unterstützung:** Automatische Favicon-Anzeige für bessere visuelle Erkennung

**Beispiel:** Wenn jemand auf deinen Amazon-Link klickt, sieht er deine exklusiven Rabattcodes (z.B. "SAVE20", "BLACKFRIDAY") mit einem Countdown-Timer, bevor er weitergeleitet wird.

### 📝 Eigene Beschreibungen

Füge personalisierte, dynamische Textinhalte zu deinen Weiterleitungsseiten hinzu:

- **Host-basierte Anzeige:** Zeige verschiedene Beschreibungen für verschiedene Websites
- **Dynamische Platzhalter:** Verwende `{$extractedTitle}`, um automatisch Seitentitel einzufügen
- **Prioritätssystem:** Steuere, welche Beschreibung zuerst angezeigt wird, wenn mehrere übereinstimmen
- **Rich-Text-Unterstützung:** Vollständige HTML-Unterstützung für Formatierung
- **Ein/Aus-Schalter:** Beschreibungen pro Eintrag oder global ein-/ausschalten
- **Mehrere Beschreibungen:** Erstelle unbegrenzt viele Beschreibungen mit verschiedenen Prioritäten

**Beispiel:** "Schau dir dieses tolle Angebot auf {$extractedTitle} an! Vergiss nicht, den Rabattcode unten zu nutzen."

### 🔗 Featured Links (Empfehlungen)

Zeige verwandte Links und Empfehlungen auf deinen Weiterleitungsseiten:

- **Intelligente Empfehlungen:** Zeige relevante Links basierend auf der Ziel-Website
- **Eigene Titel & Beschreibungen:** Füge eigene Titel und Beschreibungen für jede Empfehlung hinzu
- **Prioritätskontrolle:** Setze die Anzeigereihenfolge mit Prioritätswerten
- **Host-basierte Filterung:** Zeige Empfehlungen nur auf bestimmten Websites
- **Reaktions-Unterstützung:** Nutzer können auf Empfehlungen reagieren
- **Gäste-Reaktionen:** Optionale Unterstützung für Gäste-Reaktionen (synchronisiert bei Registrierung)
- **Visuelle Badges:** Zeige Host-Badges für bessere visuelle Erkennung

**Beispiel:** Bei einer Weiterleitung zu einem Amazon-Produkt zeigst du Links zu ähnlichen Produkten, besseren Deals oder verwandten Artikeln.

### 🎨 Saisonale Themes & Visuelle Effekte

Erstelle beeindruckende Theme-Weiterleitungsseiten für spezielle Kampagnen:

- **Vorgefertigte Themes:** Halloween, Weihnachten, Black Friday und mehr
- **Eigene Themes:** Erstelle eigene Themes mit benutzerdefinierten Farben und Effekten
- **Visuelle Effekte:**
  - **Halloween-Geister:** Animierter schwebender Geister-Effekt
  - **Schnee-Effekt:** Fallende Schneeflocken-Animation
  - **Herbstblätter:** Fallende Blätter-Animation
- **Theme-Farben:** Automatische Farbanwendung von Themes auf Special-Promotions
- **URL-basierte Aktivierung:** Aktiviere Themes über URL-Parameter (z.B. `?special=halloween`)
- **Zeitlich begrenzt:** Setze Start- und Enddaten für automatische Theme-Aktivierung

**Beispiel:** Füge `?special=halloween` zu jedem Kurz-Link hinzu für eine Halloween-Weiterleitungsseite mit gruseligen Farben und animierten Geistern.

### ⏰ Special-Promotions

Erstelle zeitlich begrenzte Special-Promotions mit vollständiger Anpassung:

- **Identifier-basiert:** Verwende einfache Identifier (z.B. `halloween`, `blackfriday`) für einfache URL-Aktivierung
- **Theme-Integration:** Verknüpfe Specials mit Themes für automatische Farb- und Effekt-Anwendung
- **Rabatt-Überschreibung:** Überschreibe temporär reguläre Rabatte mit Special-Rabattwerten
- **Countdown-Timer:** Automatische Countdown-Anzeige für aktive Specials
- **Eigene Farben:** Überschreibe Theme-Farben mit Special-spezifischen Farben bei Bedarf
- **Mehrere Codes:** Unterstützung für mehrere Rabattcodes pro Special
- **Automatische Aktivierung:** Specials aktivieren/deaktivieren basierend auf Zeit-Einstellungen

**Beispiel:** Erstelle ein "Black Friday 2024" Special, das automatisch am 29. November aktiviert wird und spezielle Rabattcodes mit Countdown-Timer anzeigt.

### 👍 Reaktions-System

Lass Besucher mit deinen Links über Reaktionen interagieren:

- **Mehrere Reaktionstypen:** Like, Love, Danke, Haha, Verwirrend, Traurig, OK
- **Gäste-Unterstützung:** Optionale Gäste-Reaktionen (gespeichert und bei Registrierung synchronisiert)
- **Reaktions-Anzeige:** Zeige Reaktionszahlen auf Weiterleitungsseiten
- **Admin-Übersicht:** Sieh alle Reaktionen im Admin-Panel
- **Analysen:** Verstehe, welche Links beliebt und ansprechend sind
- **WoltLab-Integration:** Vollständige Integration mit WoltLab's Reaktions-System

**Beispiel:** Besucher können auf deine Links reagieren und dir helfen zu verstehen, welche Promotions am effektivsten sind.

### 📤 Teilen-Funktionalität

Mach es Besuchern einfach, deine Links zu teilen:

- **Ein-Klick-Teilen:** Teilen auf Social-Media-Plattformen
- **Teilen-Dialog:** Professioneller Teilen-Dialog mit Link-Titel
- **Erhöhte Reichweite:** Organisches Wachstum durch Social Sharing
- **WoltLab-Integration:** Nutzt WoltLab's eingebaute Teilen-Funktionalität

---

## 📦 Voraussetzungen

- **WoltLab Suite:** Version 6.0.16 oder höher
- **Basis-Plugin:** [URL Shortener](https://www.woltlab.com/pluginstore/de/p/url-shortener/) v6.0.0 oder höher
- **PHP:** Version 8.0 oder höher

---

## 🚀 Installation

1. Lade die neueste Version von [GitHub Releases](https://github.com/benjarogit/urlshort-featured-links/releases) herunter
2. Melde dich in deinem WoltLab Admin-Panel (ACP) an
3. Gehe zu **System → Pakete → Paket installieren**
4. Lade die heruntergeladene `.tar.gz`-Datei hoch
5. Folge dem Installationsassistenten
6. Fertig! Das Plugin ist jetzt aktiv

---

## 📖 Anleitung

### Rabattcodes einrichten

1. **Gehe zum Admin-Panel:** URL Shortener → Rabatte → Neu hinzufügen
2. **Titel eingeben:** Interner Name (z.B. "Amazon Black Friday Sale")
3. **Rabattwert eingeben:** Der anzuzeigende Rabatt (z.B. "30%", "50€", "SAVE20")
4. **Website-Hosts hinzufügen:** Gib kommagetrennte Hosts ein (z.B. `amazon.de, amazon.com, ebay.com`)
5. **Rabattcodes hinzufügen:** Gib Codes getrennt durch Kommas ein (z.B. `SAVE20, BLACKFRIDAY, DEAL2024`)
6. **Farben anpassen (Optional):**
   - Primäre Hintergrundfarbe
   - Sekundäre Hintergrundfarbe
   - Primäre Textfarbe
   - Sekundäre Textfarbe
7. **Countdown setzen (Optional):** Setze Start- und Enddaten für automatische Countdown-Anzeige
8. **Speichern:** Dein Rabatt erscheint auf passenden Weiterleitungsseiten

**Ergebnis:** Wenn jemand auf einen Link zu `amazon.de` klickt, sieht er deine Rabattcodes mit Countdown-Timer, bevor er weitergeleitet wird!

### Eigene Beschreibungen erstellen

1. **Gehe zum Admin-Panel:** URL Shortener → Beschreibungen → Neu hinzufügen
2. **Titel eingeben:** Interner Name (z.B. "Amazon Produktbeschreibung")
3. **Website-Hosts hinzufügen:** Wo soll diese Beschreibung erscheinen? (z.B. `amazon.de, amazon.com`)
4. **Text schreiben:** Deine individuelle Nachricht mit optionalen Platzhaltern:
   - `{$extractedTitle}` - Automatisch ersetzt durch den Seitentitel
   - Vollständige HTML-Unterstützung für Formatierung
5. **Priorität setzen:** Höhere Zahlen werden zuerst angezeigt (nützlich für mehrere Beschreibungen)
6. **Aktivieren:** Beschreibung ein-/ausschalten
7. **Speichern:** Deine Beschreibung erscheint auf passenden Weiterleitungsseiten

### Featured Links hinzufügen

1. **Gehe zum Admin-Panel:** URL Shortener → Featured Links → Neu hinzufügen
2. **Titel eingeben:** Was Besucher sehen werden (z.B. "Ähnliche Produkte", "Bessere Deals")
3. **URL hinzufügen:** Die Ziel-URL für die Empfehlung
4. **Website-Hosts hinzufügen:** Auf welchen Websites soll dies erscheinen? (z.B. `amazon.de`)
5. **Beschreibung hinzufügen (Optional):** Zusätzlicher Text zur Erklärung der Empfehlung
6. **Priorität setzen:** Steuere die Anzeigereihenfolge (höher = zuerst)
7. **Reaktionen aktivieren (Optional):** Erlaube Nutzern, auf diese Empfehlung zu reagieren
8. **Speichern:** Deine Empfehlung erscheint auf passenden Weiterleitungsseiten

### Themes erstellen

1. **Gehe zum Admin-Panel:** URL Shortener → Themes → Neu hinzufügen
2. **Titel eingeben:** Anzeigename (z.B. "Halloween 2024")
3. **Identifier eingeben:** URL-Identifier (z.B. `halloween`, `christmas`, `blackfriday`)
4. **Effekt auswählen:** Wähle aus verfügbaren Effekten:
   - Keiner
   - Halloween-Geister
   - Schnee
   - Herbstblätter
5. **Farben setzen:**
   - Primärfarbe (RGBA-Format)
   - Sekundärfarbe (RGBA-Format)
   - Primäre Textfarbe
   - Sekundäre Textfarbe
6. **Aktivieren:** Theme ein-/ausschalten
7. **Speichern:** Dein Theme ist einsatzbereit

### Special-Promotions erstellen

1. **Gehe zum Admin-Panel:** URL Shortener → Specials → Neu hinzufügen
2. **Titel eingeben:** Anzeigename (z.B. "Halloween Sale 2024")
3. **URL auswählen:** Wähle, auf welche Kurz-URL dieses Special angewendet werden soll
4. **Identifier eingeben:** URL-Parameter (z.B. `halloween`)
5. **Theme auswählen (Optional):** Verknüpfe mit einem Theme für automatische Farben und Effekte
6. **Rabattwert eingeben:** Überschreibe regulären Rabatt (z.B. "50%")
7. **Rabattcodes hinzufügen (Optional):** Spezielle Rabattcodes für diese Promotion
8. **Zeitraum setzen:** Start- und Enddaten für automatische Aktivierung
9. **Farben anpassen (Optional):** Überschreibe Theme-Farben bei Bedarf
10. **Speichern:** Dein Special ist einsatzbereit!

**Verwendung:** Füge `?special=halloween` zu jedem Kurz-Link hinzu:
```
https://deinedomain.com/r/abc123?special=halloween
```

Die Weiterleitungsseite wird mit deinem Halloween-Theme, Farben und Effekten angezeigt!

---

## ⚙️ Konfiguration

Alle Einstellungen findest du im Admin-Panel unter:
**Konfiguration → Optionen → URL Shortener → Featured Links**

### 📱 Reaktionen

- **Reaktionen aktivieren** (Standard: ✅ Aktiviert)
  - Aktiviert das Reaktionssystem für Kurz-URLs auf Weiterleitungsseiten
  - Nutzer können mit Emojis reagieren (👍 Like, ❤️ Love, etc.)

- **Gäste-Reaktionen aktivieren** (Standard: ❌ Deaktiviert)
  - Ermöglicht nicht angemeldeten Besuchern zu reagieren
  - Gäste-Reaktionen werden gespeichert und bei Registrierung mit Benutzerkonten synchronisiert

### 📝 Beschreibungen

- **Beschreibungen aktivieren** (Standard: ✅ Aktiviert)
  - Globaler Schalter für Beschreibungsanzeige
  - Überschreibt individuelle Beschreibungs-Aktivierung/Deaktivierung

### ⏱️ Countdown

- **Countdown aktivieren** (Standard: ✅ Aktiviert)
  - Aktiviert Countdown-Timer für Rabatte und Specials
  - Zeigt verbleibende Zeit bis zum Ablauf an

### 🎨 Visuelle Effekte

- **Halloween-Effekt aktivieren** (Standard: ✅ Aktiviert)
  - Aktiviert animierte Effekte, wenn Halloween-Themed Specials aktiv sind
  - Enthält Geister-, Schnee- und Herbstblätter-Animationen

---

## 💡 Best Practices

### Für Affiliate-Marketing

**Empfohlene Konfiguration:**
- ✅ Reaktionen aktivieren → Besucher-Feedback sammeln
- ✅ Beschreibungen aktivieren → Personalisierte Nachrichten zeigen
- ✅ Countdown aktivieren → Dringlichkeit erzeugen
- ✅ Visuelle Effekte aktivieren → Engagement steigern
- Verwende Host-basierte Zielgruppenausrichtung für maximale Relevanz

### Für Saisonale Kampagnen

- Plane im Voraus für große Shopping-Events (Black Friday, Weihnachten, etc.)
- Erstelle Theme-Specials mit passenden visuellen Effekten
- Nutze Countdown-Timer für Spannung
- Teile Theme-Links auf Social Media für besseres Engagement

### Für Rabattcodes

- Halte Rabattwerte klar und prägnant
- Nutze Countdown-Timer für Dringlichkeit
- Teste Codes vor der Veröffentlichung
- Aktualisiere abgelaufene Codes regelmäßig
- Verwende mehrere Codes für A/B-Tests

---

## 🔗 Links

- **Download:** [GitHub Releases](https://github.com/benjarogit/urlshort-featured-links/releases)
- **Basis-Plugin:** [URL Shortener im WoltLab Plugin Store](https://www.woltlab.com/pluginstore/de/p/url-shortener/)
- **Support:** [GitHub Issues](https://github.com/benjarogit/urlshort-featured-links/issues)
- **Website:** https://benjaro.info

---

## 📄 Lizenz

Dieses Plugin erweitert den URL Shortener von dev.tkirch und folgt den WoltLab Plugin Store Richtlinien. Es werden keine externen Services verwendet - alles läuft auf deiner WoltLab-Installation.

---

## 👤 Autor

**Benjaro**  
Website: https://benjaro.info

---

## 💬 Support

- **Bug gefunden?** [Melde ihn auf GitHub](https://github.com/benjarogit/urlshort-featured-links/issues)
- **Hast du eine Frage?** Besuche https://benjaro.info
- **Wünschst du dir ein neues Feature?** Schlage es auf GitHub vor!

---

**Letzte Aktualisierung:** 30. November 2025  
**Status:** Produktionsbereit ✅
