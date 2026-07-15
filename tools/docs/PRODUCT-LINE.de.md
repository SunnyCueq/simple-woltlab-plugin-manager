# Produktlinie: Basis + Zusatzpakete

**[English version](PRODUCT-LINE.en.md)**

Diese Anleitung erklärt, **wie du mehrere zusammengehörige WoltLab-Pakete** (eine Basis-App und optionale Zusatzpakete) mit SWPM ablegst, prüfst und baust — so, dass die Reihenfolge stimmt und Fehler früh auffallen.

Du brauchst kein Vorwissen über „Graphen“. Du brauchst nur: Ordner, eine kleine JSON-Datei und klare Abhängigkeiten in der `package.xml`.

---

## Was ist eine Produktlinie?

Eine **Produktlinie** (in SWPM auch **Familie**) ist eine Gruppe von Paketen, die zusammengehören:

| Rolle | Bedeutung | Beispiel |
|-------|-----------|----------|
| **Basis** | Das Hauptpaket. Ohne Basis machen die Zusatzpakete keinen Sinn. | `com.vendor.myapp` |
| **Zusatzpaket (Add-on)** | Optionales Feature-Paket. Setzt die Basis voraus. | `com.vendor.myapp.specials` |

**Wichtig:** Die Basis und jedes Zusatzpaket sind **eigene installierbare WoltLab-Pakete** (eigene `.tar.gz`, eigener Ordner). SWPM baut sie nacheinander — zuerst die Basis, dann die Add-ons.

Das ist **kein** fest verdrahtetes Einzelprodukt. Die Beispiele nutzen Platzhalternamen (`com.vendor.myapp`). Du ersetzt sie durch deine IDs.

---

## Drei Befehle — bitte nicht vermischen

| Was du willst | Befehl | Was passiert |
|---------------|--------|--------------|
| **Ein** Paket bauen | `./tools/build.sh mein-plugin patch` | Nur dieser Ordner |
| Die **Produktlinie** bauen | `./tools.sh family:build patch` | Nur Pakete aus dem Manifest, **Basis zuerst** |
| Alles im Workspace finden und bauen | Menü „alle“ / `build:all` | **Nicht** die Familie — kein Manifest |

Wenn du eine Linie meinst, immer `family:*` verwenden. Sonst baust du versehentlich fremde Ordner mit oder in der falschen Reihenfolge.

---

## Wo die Ordner liegen dürfen

Zwei übliche Varianten — beide sind korrekt.

### Variante A — Geschwisterordner (empfohlen für echte Apps)

SWPM und deine Pakete liegen **nebeneinander**. Das Manifest liegt z. B. im gemeinsamen Elternordner oder zeigt mit `../…` auf die Pakete.

```
Dokumente/
├── plugin-manager/          ← SWPM (tools/, tools.sh)
├── swpm-family.json         ← Manifest (hier oder wo du es hinlegst)
├── myapp/                   ← Basis
│   └── package.xml          ← oder temp_edit/package.xml (siehe unten)
├── myapp-specials/          ← Zusatzpaket
│   └── package.xml
└── myapp-vouchers/          ← weiteres Zusatzpaket
    └── package.xml
```

Dann im Manifest z. B.:

```json
"paths": [
  "myapp",
  "myapp-specials",
  "myapp-vouchers"
]
```

wenn das Manifest **im gleichen Ordner** wie `myapp` liegt — oder:

```json
"paths": [
  "../myapp",
  "../myapp-specials",
  "../myapp-vouchers"
]
```

wenn das Manifest **im SWPM-Root** (`plugin-manager/`) liegt.

### Variante B — Pakete im SWPM-Root

```
plugin-manager/
├── tools/
├── swpm-family.json
├── myapp/
│   └── temp_edit/package.xml
├── myapp-specials/
│   └── temp_edit/package.xml
└── myapp-vouchers/
    └── temp_edit/package.xml
```

```json
"paths": ["myapp", "myapp-specials", "myapp-vouchers"]
```

---

## Wo die `package.xml` liegt (Scaffold vs. echtes Plugin)

SWPM findet die `package.xml` an diesen Stellen (pro Paket-Root):

1. `temp_edit/package.xml` — typisch für **Scaffold** und viele SWPM-Workflows  
2. `package.xml` im Paket-Root — typisch für **ausgewachsene** Plugins/Apps  
3. `_extracted/package.xml` — nach dem Entpacken eines Archivs  

**Regel:** Pro Paket genau **ein** Root-Ordner. Darin muss SWPM eine gültige `package.xml` finden. Ob Root oder `temp_edit/` — beides ist erlaubt; beim Scaffold entsteht `temp_edit/`.

Einzelnes Paket bauen (ohne Familie):

```bash
./tools/build.sh myapp patch
```

Details zum Innenleben eines Pakets (lib/, templates/, …): [PACKAGE-LAYOUT.de.md](PACKAGE-LAYOUT.de.md).

---

## Pflicht: Abhängigkeit in der `package.xml`

Jedes **Zusatzpaket** muss die Basis als erforderliches Paket nennen:

```xml
<requiredpackages>
	<requiredpackage minversion="6.2.0">com.woltlab.wcf</requiredpackage>
	<requiredpackage minversion="1.0.0">com.vendor.myapp</requiredpackage>
</requiredpackages>
```

| Begriff | Einfach gesagt |
|---------|----------------|
| `requiredpackage` | „Dieses Paket darf nur installiert werden, wenn jenes schon da ist.“ |
| `minversion` | „Mindestens diese Versionsnummer der Basis.“ |
| `com.woltlab.wcf` | WoltLab Suite Core — zählt **nicht** als Familien-Kante (immer erlaubt). |

**Ohne** `requiredpackage` auf die Basis sieht SWPM zwei getrennte Inseln → **Abbruch**. Das ist Absicht: so merkst du fehlende Abhängigkeiten sofort.

Die Basis braucht nur WCF (und ggf. andere echte Abhängigkeiten), **nicht** die Add-ons.

---

## Das Manifest `swpm-family.json`

Kleine Landkarte: **wo** SWPM suchen soll. Die **Wahrheit** über IDs und Abhängigkeiten kommt aus den `package.xml`-Dateien.

### Alle Felder

| Feld | Pflicht | Bedeutung |
|------|---------|-----------|
| `schemaVersion` | ja | Formatversion, aktuell `1` |
| `id` | empfohlen | Name der **Linie** (frei), z. B. `com.vendor.myapp.line` — muss **nicht** einem Paket-`name` entsprechen |
| `versionStrategy` | nein | `independent` (Default) oder `lockstep` (alle SKUs gleiche `<version>` — sonst `family:check` Fehler) |
| `paths` | ja | Liste der Suchorte (Ordner). Relativ zum Manifest oder absolut |
| `packages` | nein | Leer `[]` = alle gefundenen Pakete unter `paths`. Nicht leer = **Whitelist** nur dieser IDs |

### Beispiel (vollständig)

```json
{
  "schemaVersion": 1,
  "id": "com.vendor.myapp.line",
  "versionStrategy": "independent",
  "paths": [
    "myapp",
    "myapp-specials",
    "myapp-vouchers"
  ],
  "packages": []
}
```

### Whitelist (wenn unter `paths` noch Fremdes liegt)

```json
"packages": [
  { "id": "com.vendor.myapp", "path": "myapp" },
  { "id": "com.vendor.myapp.specials", "path": "myapp-specials" }
]
```

Hier muss `id` exakt dem Attribut `name="…"` in der jeweiligen `package.xml` entsprechen. Die Manifest-`id` der Linie (`com.vendor.myapp.line`) ist davon unabhängig.

### Wo liegt die Datei?

1. Explizit: `./tools/swpm-family.sh --manifest /pfad/swpm-family.json …`  
2. Oder in `tools/.env`: `WOLTLAB_FAMILY_MANIFEST=swpm-family.json` (relativ zum SWPM-Root oder absolut)  
3. Oder: `swpm-family.json` direkt im SWPM-Root  

---

## Schritt für Schritt (von null bis zum ersten Check)

### Option 1 — Scaffold (Starthilfe)

Im SWPM-Verzeichnis:

```bash
./tools.sh family:init --scaffold \
  --base-id com.vendor.myapp \
  --base-dir myapp \
  --addons myapp-specials,myapp-vouchers \
  --wcf-min 6.2.0
```

Das legt an:

- Manifest `swpm-family.json`
- Ordner `myapp/`, `myapp-specials/`, … mit `temp_edit/package.xml` (+ minimale `lib/`-Platzhalter)
- Add-ons mit `requiredpackage` auf die Basis

Danach:

```bash
./tools.sh family:check
./tools.sh family:list
./tools.sh family:order
```

**Scaffold ≠ fertiges Store-Plugin.** Du füllst danach echte Dateien (lib, Templates, …) nach [PACKAGE-LAYOUT.de.md](PACKAGE-LAYOUT.de.md). Graph-Checks (`family:check`) prüfen nur Abhängigkeiten, nicht die Store-Qualität.

Weiteres Add-on:

```bash
./tools.sh family:add-addon myapp-messages --base-id com.vendor.myapp
```

Ohne `--base-id` versucht SWPM die Basis-ID aus dem gültigen Familien-Graph abzuleiten (über die JSON-Ausgabe des Dep-Checks).

### Option 2 — Bestehende Ordner verdrahten

1. Ordner pro Paket anlegen (Variante A oder B).  
2. In jedem Add-on `requiredpackage` auf die Basis setzen.  
3. `swpm-family.json` mit engen `paths` schreiben.  
4. `./tools.sh family:check` — muss **OK** sagen.  
5. `./tools.sh family:build patch` bzw. `family:validate`.

Nur Manifest ohne Ordner:

```bash
./tools.sh family:init
```

(dann musst du die Ordner selbst haben; sonst scheitert `family:check` an fehlenden Pfaden.)

---

## Was SWPM in der Familie prüft

Einfach gesagt: **Alles, was du als Familie meinst, muss über `requiredpackage` an einem Stück hängen.**

| Prüfung | Wenn es schiefgeht |
|---------|-------------------|
| Mindestens ein Paket gefunden | `paths` falsch oder leer |
| Genau **eine** zusammenhängende Gruppe | Add-on ohne Dep zur Basis, oder zu breite `paths` |
| Jede Nicht-WoltLab-Abhängigkeit ist in der Familie | Tippfehler in der Package-ID |
| Kein Zyklus (A braucht B, B braucht A) | Abhängigkeiten korrigieren |
| `minversion` der Basis ≤ Version der Basis | Version in Basis-`package.xml` anheben oder minversion senken |
| Keine doppelte Package-ID | Zwei Ordner mit gleichem `name` |
| Whitelist-`id` = `package.xml`-`name` | IDs angleichen |

`com.woltlab.*` erzeugt **keine** Familien-Kante und darf fehlen.

Mehrere Basen in **einer** Gruppe (Add-on braucht A und B) sind erlaubt, aber unüblich — SWPM warnt.

---

## Befehle der Produktlinie

| Befehl | Zweck |
|--------|--------|
| `family:list` | Pakete und Abhängigkeiten anzeigen |
| `family:order` | Bau-Reihenfolge (Basis zuerst) |
| `family:check` | Nur Abhängigkeitsprüfung |
| `family:build [patch\|minor\|major\|same]` | Prüfen, dann jedes Paket bauen |
| `family:validate [--strict]` | Prüfen, dann jedes Paket validieren |
| `family:init` | Manifest anlegen |
| `family:init --scaffold` | Manifest + Stub-Ordner |
| `family:add-addon <slug>` | Weiteres Add-on-Stub + `paths` ergänzen |

Direkt:

```bash
./tools/swpm-family.sh --manifest /pfad/swpm-family.json check
./tools/swpm-family.sh --manifest /pfad/swpm-family.json build patch
```

### Hinweise zu `.env`

Bei `family:*` werden `WOLTLAB_PACKAGE_ID` / `WOLTLAB_APP_ABBREV` **ignoriert** (mit Warnung). Diese Werte gelten nur für ein Einzelpaket — sonst würde die ganze Linie die falsche ID bekommen.

Standard: bei Fehler abbrechen. Mit `--continue` (nur lokal) nach einem fehlgeschlagenen Paket weitermachen.

---

## Anti-Patterns (bitte vermeiden)

| Fehler | Folge |
|--------|--------|
| `paths: [".."]` oder ein viel zu weiter Ordner | Viele unverbundene Plugins → Abbruch |
| Add-on ohne `requiredpackage` auf die Basis | Zweite Insel → Abbruch |
| Fremde Plugins im gleichen `paths` ohne Whitelist | Abbruch oder falsche Linie |
| `family:build` und `build:all` verwechseln | Falsche Menge / Reihenfolge |
| Scaffold für Store-Release halten | Validate/Build scheitern an fehlenden Quellen |

Unter einem App-Root überspringt die Suche bewusst u. a. `examples/`, `fixtures/`, `node_modules/`, `.git/` — damit Demo-Add-ons **nicht** still in die Familie rutschen. Demo bewusst mitbauen: eigenen `paths`-Eintrag oder Whitelist.

---

## Checkliste vor dem ersten `family:build`

- [ ] Jedes Paket hat einen eigenen Ordner und eine auffindbare `package.xml`
- [ ] Jedes Add-on hat `<requiredpackage …>deine.basis.id</requiredpackage>`
- [ ] Basis und Add-ons haben eine `<version>`
- [ ] `swpm-family.json` existiert; `paths` zeigen genau auf diese Ordner
- [ ] `family:check` endet mit OK
- [ ] `family:order` zeigt die Basis **vor** den Add-ons
- [ ] Du weißt, welches Manifest du meinst (`--manifest` oder `.env`)

---

## First Release (SemVer 1.0.0) — Paket-Hygiene

Solange die Linie **noch nicht öffentlich** im Store/als Release liegt, gilt Entwicklung = First Release:

1. **Version:** alle SKUs der Linie auf `1.0.0` (oder bewusst gleiches SemVer), Datum aktuell. Mit `versionStrategy: "lockstep"` erzwingt `family:check` gleiche Versionsnummern.
2. **`package.xml`:** nur `<instructions type="install">` — **keine** historischen `fromversion`-Update-Blöcke und keine alten `post_update_*` / `database/update_*`-Skripte.
3. **Add-on-`minversion`:** zeigt auf die Basis-`1.0.0` (nicht auf interne Dev-Nummern).
4. **Beschreibungen:** Basis und jedes Zusatzpaket haben **eigene** `packagedescription` (Default + `language="de"`). Die Basis darf Add-on-Features nicht als fest enthalten beschreiben — nur klar als optional/Zusatz. `family:check` warnt bei fehlenden Texten und bei Add-on-Begriffen in der Basis-Beschreibung ohne Optional-Kontext.
5. **Runtime (Produktlinien mit optionalen Features):** UI/SQL/Klassen nur hinter Capability-Checks (`class_exists` / Feature-Flags); Soft-Deps zwischen Add-ons, kein `requiredpackage` Add-on→Add-on.
6. **Nach erstem öffentlichem Release:** Update-Blöcke und Migrationsskripte erst ab `1.0.1` / Folgereleases — keine Schein-Abwärtskompatibilität für nie ausgelieferte Dev-Versionen.
7. **Lokale Test-Instanz:** von Dev-Versionen auf `1.0.0` nicht „downgraden“ — Pakete **deinstallieren** und frisch installieren.

SWPM-Checks:

- pro Paket: `first-release-hygiene`, `package-descriptions` (Build-Registry, Warn)
- Familie: `family:check` — Lockstep, Description-Hygiene Basis↔Add-on

Weitere Produktlinien-Praxis (layout-unabhängig):

- Root-Layout-Pakete (kein `temp_edit/`): Family-Build staged über `_family-stage/` + Symlink auf den Quellbaum.
- Datei-Hotfix in einer laufenden Instanz ≠ ACP-Paket-Update — Releases immer aus der Quelle bauen.
- WCF: optionale Add-on-Templates, die der Core per `{include}` einbindet, dürfen **nicht** als gleichnamige Core-Stubs liegen (Ownership-Konflikt bei Add-on-Install). Include nur hinter Feature-Gates (Add-on vorhanden) oder mit anderem Core-Fallback-Namen.

---

## Mitgeliefertes Mini-Beispiel (Fixture)

Unter `tools/fixtures/family-demo/`:

- gültige Linie (Core + Add-on mit `requiredpackage`)
- absichtlich kaputte Manifeste (fehlende Abhängigkeit, zwei Inseln)

```bash
python3 tools/check-family-deps.py --manifest tools/fixtures/family-demo/swpm-family.json --mode order
./tools/swpm-family.sh --manifest tools/fixtures/family-demo/swpm-family.json check
```

---

## Kurzes Wörterbuch

| Begriff | Bedeutung |
|---------|-----------|
| Produktlinie / Familie | Gruppe Basis + Zusatzpakete unter einem Manifest |
| Basis / Core | Das Paket, das Add-ons voraussetzen |
| Zusatzpaket / Add-on | Optionales Paket mit `requiredpackage` auf die Basis |
| Manifest | `swpm-family.json` — Suchorte, keine Ersatz-`package.xml` |
| `paths` | Wo gesucht wird |
| Whitelist `packages[]` | Welche Package-IDs zur Linie gehören dürfen |
| Bau-Reihenfolge | Technisch: topologische Sortierung — Basis vor Add-ons |

---

## Siehe auch

- [PACKAGE-LAYOUT.de.md](PACKAGE-LAYOUT.de.md) — Aufbau **eines** Pakets (Dateien, templates/, files/)
- [PLUGIN-STORE-CHECKLIST.de.md](PLUGIN-STORE-CHECKLIST.de.md) — Store; Zusatzpakete brauchen die Basis im Listing
- [ACP-PACKAGE-INSTALL.de.md](ACP-PACKAGE-INSTALL.de.md) — lokale ACP-Installation (optional, Docker)
