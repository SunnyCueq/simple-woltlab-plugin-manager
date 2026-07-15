# Tools debug logging

**[Deutsche Version](LOGGING.de.md)**

Scripts under `tools/` write to **one** central debug log file. If the default file is not writable, output falls back to `/tmp/woltlab-dev-debug.log`.

## Convention

- **Central log file:** `tools/docs/logs/woltlab-dev-debug.log` (default)
- **Environment variables:**
  - `DEBUG_LOG_DIR` — directory for the log file (default: `tools/docs/logs`)
  - `DEBUG_LOG_FILE` — full path to the log file (default: `$DEBUG_LOG_DIR/woltlab-dev-debug.log`)
  - `DEBUG_ENABLED` — `true`/`1` = logging on, otherwise off (default: `true`)
  - `DEBUG_LEVEL` — minimum level: `ERROR`, `WARNING`, `INFO`, `DEBUG`, `TRACE` (default: `INFO`)

## Log levels (increasing detail)

| Level | Meaning |
|-------|---------|
| ERROR | Errors only |
| WARNING | Errors + warnings |
| INFO | + general information (default) |
| DEBUG | + debug output |
| TRACE | + very detailed flow information |

## Usage in scripts

Functions come from `common.sh` (included by all tools):

- `debug_error "message" "optional data"`
- `debug_warning "message" "optional data"`
- `debug_info "message" "optional data"`
- `debug_debug "message" "optional data"`
- `debug_trace "message" "optional data"`
- `log_error_with_context "error message" "context"` — writes ERROR and prints the log path to the user

**Recommendation:** Log errors and important context with `log_error_with_context` or `debug_error`; use `debug_debug`/`debug_trace` for flow details.

## View / clear log

- **View:** In the menu (if offered) or directly: `tail -n 100 tools/docs/logs/woltlab-dev-debug.log`
- **Clear:** Delete or truncate the log file; `clear_debug_log` (in `common.sh`) resets it to empty.

## Entry format

```
[TIMESTAMP] [LEVEL] [SCRIPT:FUNCTION] MESSAGE | optional data
```

Example: `[2025-02-06 12:00:00.123] [INFO] [tools.sh:main] Start | data=...`
