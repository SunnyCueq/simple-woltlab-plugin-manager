# Plugin Store Submission Checkliste

**[English version](PLUGIN-STORE-CHECKLIST.en.md)**

**Letzte Aktualisierung:** 2026-06-26 (abgestimmt mit [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/))  
**Version:** 1.1.0

WoltLab prüft in **zwei Stufen**: automatische Vorabprüfung beim Upload, danach manuelle Sichtung. Unsere Tools decken Stufe 1 weitgehend ab; Stufe 2 erfordert zusätzliche manuelle Tests (Funktion, UX, Berechtigungen).

**Pflicht vor jedem Store-Upload:**

```bash
./tools/build.sh [PLUGIN_DIR]          # bricht bei Template-/Build-Fehlern ab
./tools/validate-plugin.sh [PLUGIN_DIR]
```

Oder im Hauptmenü: **Option 6) Plugin Validierung**

---

## 1. WoltLab Vorabprüfung (automatisch beim Upload)

| WoltLab-Kriterium | Unser Tool / Aktion |
|-------------------|---------------------|
| PHP- und XML-Dateien syntaktisch korrekt | `validate-plugin.sh` → PHP/XML-Syntax |
| Archiv enthält alle in `package.xml` deklarierten Dateien | `validate-plugin.sh` → Datei-Vollständigkeit |
| Keine überflüssigen Dateien (`.DS_Store`, `Thumbs.db`, …) | `validate-plugin.sh` + `build.sh` (Archiv-Check) |
| Kompatibilitätsangaben vollständig und formal korrekt | `validate-plugin.sh` → `requiredpackages`, Version |

**Zusatzpakete:** Im Store-Listing klar machen, dass die **Basis-App** Voraussetzung ist. Produktlinien-Build: [PRODUCT-LINE.de.md](PRODUCT-LINE.de.md).
| Mindestversion `com.woltlab.wcf` mit Sicherheits-Support | `validate-plugin.sh` → Minversion (aktuell 6.0+; bei Release aktuelle 6.2.x setzen) |

---

## 2. WoltLab manuelle Prüfung (Mitarbeiter)

Priorität laut WoltLab: **Sicherheit zuerst**, dann Lauffähigkeit und UX.

| WoltLab-Kriterium | Unser Tool / manuelle Prüfung |
|-------------------|------------------------------|
| **Sicherheit:** fehlerhafte Parameter, SQL, fehlende Berechtigungen, XSS | `validate-plugin.sh` (SQL-Heuristik, XSS, Permissions-Hinweise); manuell: Admin-/User-Flows, Uploads |
| **Lauffähigkeit:** Installation bis grober Funktionstest; UI an WoltLab angelehnt | `./tools/prepare-acp-install.sh` + ACP-Upload; E2E/Manual-QA-Checkliste im Plugin |
| **API-Nutzung:** z. B. Guzzle/HTTPRequest statt `file_get_contents`/`curl` | `validate-plugin.sh` → HTTP-API-Check |
| **Kein Test-/Debug-Code, keine fest eingebauten Test-API-Keys** | `validate-plugin.sh` → Debug-Code, Test-Credentials |
| **Effizienz:** keine offensichtlich teuren DB-Abfragen | Manuell: N+1, fehlende Pagination; Raw-SQL gegen echtes Schema prüfen (s. unten) |
| **Übersetzungen:** DE **und** EN vollständig, inhaltlich gleichwertig | `validate-plugin.sh` + optional `check-language-keys.py`; Store-Listing DE/EN |
| **Copyright:** nur auf App-eigenen Seiten (Apps) bzw. bei Stilen auf allen Seiten | Manuell: keine Footer-Banner fremder Seiten |
| **Keine implizite/explizite Paketserver-Installation** | `validate-plugin.sh` → kein `packageUpdateServer` PIP |

### Raw-SQL / Schema-Fallen (Plugin-Reviews)

Vor Release jede **eigene SQL-Abfrage** gegen `install.sql` / DBObject prüfen — nicht jede Tabelle hat `isDisabled`; Box-Limits liegen in WCF 6.2 in `additionalData`, nicht in `wcf*_box.limit`.

Beispiele (typische Fehler):

- App-Tabelle: Spalte nur nutzen, wenn sie im Schema existiert (z. B. kein `isDisabled`, wenn nur Delete vorgesehen ist)
- `wcf*_box`: **kein** SQL-Feld `limit` — Wert aus `unserialize(additionalData)['limit']`

---

## 3. Automatisch geprüfte Kriterien (`validate-plugin.sh` + `build.sh`)

- [ ] **PHP-Syntax:** alle `.php` fehlerfrei
- [ ] **XML-Syntax:** `package.xml` und PIP-XMLs fehlerfrei
- [ ] **Datei-Vollständigkeit:** deklarierte Dateien vorhanden
- [ ] **Übersetzungen:** `language/de.xml` **und** `language/en.xml`
- [ ] **Sprach-XML-Struktur:** Item-Name passt zur Kategorie (`check-language-categories.py`)
- [ ] **Minversion:** unterstützte WCF-Version (Sicherheits-Support)
- [ ] **Keine Package-Server:** kein `packageUpdateServer` PIP
- [ ] **SQL-Injection:** keine Request-Daten in SQL-String-Konkatenation
- [ ] **LIKE-Escaping:** `escapeLikeValue()` statt `addcslashes()` (WCF 6.2.5+)
- [ ] **XSS:** kein `|encodeHTML`/`|escape`; in `<script>` nur `{unsafe:$var|encodeJS}` (`check-template-xss.py`, **build-breaking** in `build.sh`)
- [ ] **Debug-Code:** kein `var_dump()`, `print_r()`, `console.log()` in Release
- [ ] **Test-Credentials:** keine hardcodierten Passwörter
- [ ] **HTTP:** HTTPRequest/Guzzle statt `file_get_contents`/`curl` für HTTP(S)
- [ ] **WoltLab Cloud:** kein `exec()`, `shell_exec()`, `system()`, `passthru()`
- [ ] **Event-Listener:** keine dynamischen Properties auf `$eventObj` (PHP 8.2+)
- [ ] **Archiv:** keine Müll-Dateien im Paket
- [ ] **RPC-Endpoints:** jeder `#[Get|Post|DeleteRequest]`-Controller im Bootstrap via `ControllerCollecting` registriert (`check-endpoint-registration.py`) — sonst 404 `unknown_endpoint` bei Grid-Aktionen
- [ ] **CSS-Assets:** alle `url(...)`-Referenzen in `style/` auflösbar (`check-style-assets.py`) — fehlende Fonts/Bilder können 500er auslösen
- [ ] **language/:** nur `*.xml` — Dev-Dateien (Skripte, Reports) nach `maintainer/`, sonst landen sie im Store-Paket
- [ ] **Sprach-XML-Integrität:** keine erfundenen Attribute (`variant` existiert nicht — Sie/du per `{if LANGUAGE_USE_INFORMAL_VARIANT}` im Wert), keine doppelten Keys (letzter gewinnt still beim Import), kein `{if}` in `wcf.global` (`check-language-integrity.py`)
- [ ] **Hinweis-Boxen:** `<woltlab-core-notice>` nur mit `type="error|info|success|warning"` (`danger` existiert nicht); kein Legacy-Markup `<p class="info">` (`check-template-notices.py`)
- [ ] **Template-Modifier:** Nur Whitelist-Funktionen und Modifier-Plugins verwenden — erfundene Modifier wie `|formatNumeric` schlagen erst beim ersten Rendern als Fatal Error fehl; Zahlformatierung ist `{#$var}` (`check-template-modifiers.py`)
- [ ] **Foreach in `<script>`:** Kein `{foreach name=fooLoop}` mit `$fooLoop.last` — WoltLab nutzt `$tpl.foreach`; für JS-Arrays `{implode from=$arr item=item}'{unsafe:$item|encodeJS}'{/implode}` wie `shared_itemListFormField.tpl` (`check-template-foreach.py`)
- [ ] **Box-additionalData:** In Box-Controllern nicht `$this->box->{$key} ?? …` lesen — `DatabaseObject::__isset()` kennt den additionalData-Fallback von `Box::__get()` nicht, `??` liefert dadurch immer den Default. Stattdessen `$box->additionalData[$key] ?? …`; typisierte Controller-Properties brauchen non-null-Defaults
- [ ] **XML-Kommentare:** keine Maintainer-Blöcke („Datei-Zweck“, @author-Header, Changelog-Notizen) in Paket-XMLs — WoltLab-Reviewer lesen die Dateien; nur knappe englische Kommentare für nicht-offensichtliche Entscheidungen (z. B. warum ein `<delete>`-Block nötig ist)
- [ ] **Cronjobs:** jede in `cronjob.xml` referenzierte Klasse existiert und läuft fehlerfrei durch (manuell via `(new Klasse())->execute($cronjob)` im Container testen); Laufzeit-Gating (`<options>`) gesetzt, wo der Job von Optionen abhängt

Weitere Details: [SECURITY-CHECKS.de.md](SECURITY-CHECKS.de.md), [WOLTLAB-TEMPLATE-RULES.de.md](WOLTLAB-TEMPLATE-RULES.de.md), [LANGUAGE-XML.de.md](LANGUAGE-XML.de.md)

---

## 4. KI-gestützte Werkzeuge (WoltLab-Richtlinie, neu 2026)

WoltLab erlaubt KI **nur unterstützend**. Der Anbieter muss Code **eigenständig** verstehen, warten und Prüfungs-Rückmeldungen **selbst** beheben können.

**Für unsere Entwicklung:**

- KI-Output immer gegen lokale Quellen (`woltlab-docs/`, `wcfsetup/`) und `validate-plugin.sh` verifizieren
- Keine ungeprüften SQL-Spalten, Template-Modifier oder API-Annahmen aus Modell-Antworten übernehmen
- Store-Upload nur nach menschlichem Review + grünem Validator

**Community-Thread 318738 (#48–#51, Alexander Ebert):** Lauffähig ≠ Store-Qualität. Reviewer erkennen „AI slop“ u. a. an falschen Option-Namen (`enable_seo_urls` statt `url_omit_index_php`), fehlenden Lang-Keys (`wcf.global.status`), Dialog-Templates als Vollseiten, Legacy-Notices (`<p class="success">`), leeren Grid-Menü-Einträgen (`ToggleInteraction` im Dropdown), Custom-Nav statt `tabMenuContainer`. Siehe auch `tools/docs/WOLTLAB-TEMPLATE-RULES.de.md` und `tools/docs/SECURITY-CHECKS.de.md`.

**Anti-Pattern-Check vor Upload:** Option-Namen + Lang-Keys gegen Core grep-en; ACP-Templates gegen `woltlab-docs/view/templates.md`; Notices nur `woltlab-core-notice`.

---

## 5. Store-Eintrag (Listing)

- [ ] **Deutsche Beschreibung:** vollständig, sachlich
- [ ] **Englische Beschreibung:** inhaltlich identisch zur deutschen Fassung
- [ ] **Screenshots:** aussagekräftig (bei **Stilen** Pflicht; bei Erweiterungen empfohlen)
- [ ] **Changelog / Versionshinweise:** dokumentiert
- [ ] **Externe Links:** nur am Ende, nur wenn für die Erweiterung relevant (Demo, Hilfe)
- [ ] **Keine Werbung** für Angebote außerhalb des Plugin-Stores / keine Drittshop-Bewerbung
- [ ] Verweise auf **eigene** Zusatzpakete im Plugin-Store sind erlaubt
- [ ] **Lizenz:** über Store-Felder oder externer Link → `text/plain`, bei HTTPS gültiges Zertifikat

Store-Listing und Screenshots im **eigenen Plugin-Repo** unter `docs/store/` pflegen.

---

## 6. WoltLab Cloud (technische Freischaltung)

- [ ] Kompatibel mit aktueller WSC-Version
- [ ] Ausgehende HTTP(S) über Guzzle / Proxy-Konfiguration
- [ ] Keine Verbindungen zu abweichenden TCP/UDP-Ports
- [ ] Kein Massenversand von E-Mails
- [ ] Keine Privilegien, die im Managed-Betrieb vorbehalten sind (z. B. direkte DB-Verwaltung)

---

## 7. Workflow: Entwicklung → Store

### 1. Entwicklung abschließen

```bash
cd /path/to/mein-plugin
./tools/build.sh mein-plugin
```

### 2. Validierung

```bash
./tools/validate-plugin.sh mein-plugin
# Erwartung: keine Fehler; Warnungen bewusst prüfen oder beheben
```

### 3. Manuelle Tests

- [ ] Installation / Update / Deinstallation auf echter WSC-Instanz
- [ ] Alle Hauptfunktionen (User + Admin)
- [ ] Berechtigungen und Sichtbarkeit
- [ ] E2E/Manuelle QA-Checkliste im Plugin-Repo (falls vorhanden)

### 4. Upload

1. https://www.woltlab.com/pluginstore/
2. TAR.GZ hochladen (`releases/*.tar.gz`)
3. Beschreibung DE + EN, Screenshots, Kategorie
4. Zur Prüfung einreichen

### 5. Review

- Ablehnung meist bei **schwerwiegenden** Problemen (Sicherheit, Nicht-Lauffähigkeit)
- Kleinere Mängel: oft Freischaltung + Hinweis-Mail
- Typische Gründe: fehlende EN-Übersetzung, XSS/SQL, falsche APIs, Test-Code

---

## 8. Häufige Ablehnungsgründe

### Kritisch (FEHLER)

1. **Fehlende EN-Übersetzung** → `language/en.xml`
2. **SQL-Injection / falsches Schema in Raw-SQL** → Prepared Statements; Spalten in `install.sql` prüfen
3. **XSS in Templates** → Plain `{$var}` (Auto-Escape); `{unsafe:…|encodeJS}` in Scripts — **kein** `|escape`/`|encodeHTML`
4. **Test-Credentials / Debug-Code** → entfernen
5. **Package-Server** → `packageUpdateServer` PIP entfernen
6. **Fehlende Berechtigungsprüfungen** → explizit in Form/Page/Action

### Wichtig (WARNUNGEN / Hinweise)

1. **`file_get_contents()` für HTTP** → HTTPRequest/Guzzle
2. **Ineffiziente DB-Abfragen** → Pagination, Caching, N+1 vermeiden
3. **Veraltete Minversion** → aktuelle 6.2.x mit Sicherheits-Support
4. **Listing DE ≠ EN** → Texte angleichen

---

## Hilfreiche Ressourcen

- **Plugin Store Richtlinien:** https://www.woltlab.com/pluginstore/de/richtlinien/
- **WoltLab Docs:** https://docs.woltlab.com/6.2/
- **Security (DB):** https://docs.woltlab.com/6.0/php/database-access/
- **Templates:** https://docs.woltlab.com/6.0/view/templates/
- **Security-Checks (dieses Repo):** [SECURITY-CHECKS.de.md](SECURITY-CHECKS.de.md)
- **Dokumentation:** `tools/docs/` (Template-Regeln, Sicherheits-Checks, Store-Checkliste)

---

**Hinweis:** Bei Änderungen der offiziellen Richtlinien diese Checkliste und `validate-plugin.sh` nachziehen — Quelle ist immer die WoltLab-Seite oben.
