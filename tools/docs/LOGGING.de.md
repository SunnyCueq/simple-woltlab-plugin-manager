# Log-System der Tools

!!! tip "Bei Build-/Validate-Fehlern"

    Wenn ein Skript scheitert und du mehr Kontext brauchst: hier liegt das zentrale Debug-Log. Übersicht der Skripte: [Tools-Übersicht](TOOLS-OVERVIEW.md).

Die Skripte unter `tools/` schreiben in **eine** zentrale Debug-Log-Datei. Wenn die Standard-Datei nicht beschreibbar ist, fällt die Ausgabe auf `/tmp/woltlab-dev-debug.log` zurück.

## Konvention

- **Zentrale Log-Datei:** `tools/docs/logs/woltlab-dev-debug.log` (Standard)
- **Umgebungsvariablen:**
  - `DEBUG_LOG_DIR` – Verzeichnis für die Log-Datei (Standard: `tools/docs/logs`)
  - `DEBUG_LOG_FILE` – Vollständiger Pfad zur Log-Datei (Standard: `$DEBUG_LOG_DIR/woltlab-dev-debug.log`)
  - `DEBUG_ENABLED` – `true`/`1` = Logging an, sonst aus (Standard: `true`)
  - `DEBUG_LEVEL` – Mindest-Level: `ERROR`, `WARNING`, `INFO`, `DEBUG`, `TRACE` (Standard: `INFO`)

## Log-Level (aufsteigend detailliert)

| Level   | Bedeutung |
|--------|-----------|
| ERROR  | Nur Fehler |
| WARNING| Fehler + Warnungen |
| INFO   | + allgemeine Infos (Standard) |
| DEBUG  | + Debug-Ausgaben |
| TRACE  | + sehr detaillierte Ablauf-Infos |

## Nutzung in Skripten

Die Funktionen kommen aus `common.sh` (wird von allen Tools eingebunden):

- `debug_error "Nachricht" "optionale Daten"`
- `debug_warning "Nachricht" "optionale Daten"`
- `debug_info "Nachricht" "optionale Daten"`
- `debug_debug "Nachricht" "optionale Daten"`
- `debug_trace "Nachricht" "optionale Daten"`
- `log_error_with_context "Fehlermeldung" "Kontext"` – schreibt ERROR und gibt dem Nutzer den Log-Pfad aus

**Empfehlung:** Fehler und wichtige Kontexte mit `log_error_with_context` oder `debug_error` loggen; für Ablauf-Details `debug_debug`/`debug_trace` nutzen.

## Log anzeigen / leeren

- **Anzeigen:** Im Menü (falls angeboten) oder direkt: `tail -n 100 tools/docs/logs/woltlab-dev-debug.log`
- **Leeren:** Log-Datei löschen oder überschreiben; die Funktion `clear_debug_log` (in `common.sh`) setzt die Datei auf leer.

## Format eines Eintrags

```
[TIMESTAMP] [LEVEL] [SCRIPT:FUNCTION] MESSAGE | optionale Daten
```

Beispiel: `[2025-02-06 12:00:00.123] [INFO] [tools.sh:main] Start | data=...`
