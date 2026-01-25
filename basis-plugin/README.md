# Shr1nkr

> **Beautiful short links for your community. No external services needed.**

[![WoltLab Suite](https://img.shields.io/badge/WoltLab%20Suite-6.1-blue.svg)](https://www.woltlab.com/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-Commercial-green.svg)](https://sunnyc.de)

---

## 🎯 Was ist Shr1nkr?

**Shr1nkr** ist die ultimative URL-Shortener-Lösung für WoltLab Suite 6.1. Erstelle professionelle Kurz-URLs direkt in deiner Community – ohne externe Dienste, mit voller Kontrolle und unbegrenzten Möglichkeiten.

### ✨ Key Features

- 🔗 **Eigene Kurz-URLs** – z.B. `deinecommunity.de/r/angebot`
- 🎨 **Vollständig anpassbare Redirect-Seite** – Design, Farben, Effekte
- 💰 **Discount-Codes & Countdown** – Perfekt für Promotions
- 🎯 **Custom Buttons & Featured Links** – Zusätzliche Call-to-Actions
- 🎃 **Saisonale Themes** – Schnee, Herbstblätter, Halloween-Geister
- 📊 **Klick-Tracking & Statistiken** – Detaillierte Auswertungen
- 💬 **Reaktionssystem** – Auch für Gäste
- 📱 **QR-Code-Generierung** – Für mobile Nutzung
- 🚀 **SEO-optimiert** – Saubere URLs mit `/r/{hash}/`
- 🔒 **Sicher & DSGVO-konform** – Alles auf deinem Server

---

## 📸 Screenshots

### Redirect-Seite mit Discount-Badge
![Screenshot Platzhalter](https://via.placeholder.com/800x450/667eea/ffffff?text=Redirect-Seite+mit+Discount)
*Professionelle Weiterleitungsseite mit Rabatt-Badge, Featured Links und Custom Buttons*

### ACP Link-Verwaltung
![Screenshot Platzhalter](https://via.placeholder.com/800x450/764ba2/ffffff?text=ACP+Link-Verwaltung)
*Übersichtliche Verwaltung aller Kurz-URLs mit Klick-Statistiken*

### Specials-Konfiguration
![Screenshot Platzhalter](https://via.placeholder.com/800x450/f093fb/ffffff?text=Specials-Konfiguration)
*Erstelle zeitgesteuerte Specials mit individuellen Themes und Rabatten*

### Discount-Code mit Countdown
![Screenshot Platzhalter](https://via.placeholder.com/800x450/4facfe/ffffff?text=Discount-Countdown)
*Automatischer Countdown für zeitlich begrenzte Angebote*

---

## 🚀 Installation

1. **Download** – Lade die neueste Version herunter
2. **Upload** – Gehe im ACP zu `System → Paket-Verwaltung → Paket installieren`
3. **Aktivieren** – Aktiviere Shr1nkr unter `Einstellungen → Shr1nkr`
4. **Fertig!** – Erstelle deine erste Kurz-URL

### Systemanforderungen

- WoltLab Suite 6.1.0 oder höher
- PHP 8.1 oder höher
- MySQL 8.0 / MariaDB 10.5 oder höher

---

## 🎨 Features im Detail

### 🔗 URL-Management

- **Automatische Hash-Generierung** – Einzigartige, kurze Hashes
- **Custom Hash-Prefix** – z.B. `link-`, `deal-`, `promo-`
- **Blacklist-Funktion** – Blockiere unerwünschte Ziel-URLs
- **HTTPS-Erzwingung** – Nur sichere Verbindungen
- **Weiterleitungsketten-Schutz** – Verhindere Redirect-Loops

### 💰 Discount-System

- **Rabatt-Badges** – Auffällige Discount-Anzeige
- **Countdown-Timer** – Zeitlich begrenzte Angebote
- **Rabatt-Codes** – Automatische Code-Generierung
- **Host-basiertes Matching** – Rabatte für bestimmte Domains
- **Specials** – Zeitgesteuerte Promo-Aktionen

### 🎯 Interaktion

- **Featured Links** – Zusätzliche Empfehlungen
- **Custom Buttons** – Eigene Call-to-Actions
- **Reaktionssystem** – Likes, Dislikes, etc.
- **Gäste-Reaktionen** – Auch ohne Registrierung
- **Share-Button** – Teilen in sozialen Netzwerken
- **Beschreibungen** – Zusätzliche Informationen

### 🎨 Design & Themes

- **Saisonale Effekte**
  - 🎃 Halloween – Geister-Animation
  - 🍂 Herbst – Fallende Blätter
  - ❄️ Winter – Schneefall
  - 🎄 Weihnachten – Festliche Farben
  - ☀️ Sommer – Sonnige Atmosphäre

- **Custom Themes** – Eigene Farbschemata
- **3D-Button-Effekte** – Moderne UI-Elemente
- **Responsive Design** – Perfekt auf allen Geräten

---

## 🛠️ Konfiguration

### Basis-Einstellungen

```
Einstellungen → Shr1nkr → Allgemein
```

- Aktiviere Shr1nkr
- Demo-Daten installieren (für Tests)
- Expertenmodus (für erweiterte Optionen)

### URL-Generierung

```
Einstellungen → Shr1nkr → Hash-Konfiguration
```

- **Hash-Länge**: 4-8 Zeichen empfohlen
- **Hash-Prefix**: Optional (z.B. `deal-`)
- **Pattern**: RegEx für Custom-Hashes

### Redirect-Verhalten

```
Einstellungen → Shr1nkr → Weiterleitung
```

- **Weiterleitung auf Knopfdruck**: Manuelle Bestätigung
- **Verzögerte Weiterleitung**: Countdown in Sekunden
- **Normal Page Mode**: Header/Footer anzeigen

---

## 💡 Use Cases

### 🛒 Affiliate-Marketing

Erstelle professionelle Affiliate-Links mit Rabatt-Codes und Featured Links zu ähnlichen Produkten.

```
https://community.de/r/amazon-deal/
→ Zeigt Rabatt-Code, Featured Links und Custom Buttons
```

### 🎉 Promotions & Events

Zeitlich begrenzte Angebote mit Countdown und saisonalen Effekten.

```
https://community.de/r/blackweek/
→ Black Week Theme mit Countdown und Rabatt-Badge
```

### 📢 Community-Engagement

Kurze, merkbare Links für wichtige Community-Inhalte.

```
https://community.de/r/regeln/
→ Saubere URL statt langer Pfade
```

### 📱 Social Media

QR-Codes für Offline-Marketing und mobile Nutzung.

```
QR-Code → https://community.de/r/event/
→ Direkt zur Event-Seite
```

---

## 🔧 Erweiterte Features

### URL-Rewriting

Entferne das `/shrinkr/` Prefix für noch kürzere URLs:

```
Standard: https://community.de/shrinkr/r/deal/
Optimiert: https://community.de/r/deal/
```

**Voraussetzung**: Link-Umschreibungen aktiviert + `.htaccess` Konfiguration

### Externe URL

Nutze eine eigene Subdomain für Kurz-URLs:

```
https://short.community.de/r/deal/
```

**Voraussetzung**: Subdomain-Weiterleitung + Expertenmodus

### Pattern-Anpassung

Definiere eigene Hash-Patterns (RegEx):

```
Standard: [0-9a-z\-]{1,64}
Custom: [A-Z0-9]{6}  → DEAL42, PROMO7
```

---

## 📝 Changelog

### Version 1.0.0 (2026-01-07)

🎉 **Initiales Release von Shr1nkr**

- Vollständiges Rebranding von URL-Shortener
- Neue Package-ID: `de.sunnyc.wsc.shrinkr`
- Optimierte Datenbank-Struktur (`shrinkr1_*` Tabellen)
- Verbesserte Code-Qualität (WoltLab 6.1 Best Practices)
- Englische PHPDoc-Kommentare
- DDEV Development Environment Support

**Technische Änderungen:**
- Namespace: `shrinkr\*` (App-eigener Namespace)
- Datenbank-Tabellen: `shrinkr1_*`
- JavaScript: Konsolidiert unter `Shrinkr/*`

---

## 🤝 Support & Lizenz

- **Website**: [sunnyc.de](https://sunnyc.de)
- **Lizenz**: Commercial Plugin License
- **Support**: Über sunnyc.de

---

## 👨‍💻 Entwickler

**Sunny C**  
[sunnyc.de](https://sunnyc.de)

---

<p align="center">
  <strong>Made with ❤️ for WoltLab Suite</strong><br>
  © 2026 Sunny C
</p>
