# Entwickler-Hilfen (nur lokal — nicht ins GitHub-Repo)

Das öffentliche Repo [sc-woltlab-plugin-recovery](https://github.com/benjarogit/sc-woltlab-plugin-recovery) enthält **nur** `README.md`, `README.en.md` und `plugin-recovery-tool.php`. Quellcode und Skripte liegen hier unter `plugin-manager/tools/woltlab-plugin-recovery/`.

**Endnutzer** laden aus [GitHub Releases](https://github.com/benjarogit/sc-woltlab-plugin-recovery/releases):

- `plugin-recovery-tool.php` (Stub)
- `recovery-X.Y.Z.tar.gz` (wird nach Auth automatisch installiert)

## Struktur (v2.0, nur lokal)

| Pfad | Inhalt |
|------|--------|
| `stub/plugin-recovery-tool.php` | Release-Stub (entspricht der Datei auf GitHub) |
| `recovery-src/` | Paket-Quellen → `dist/recovery-tool/` |
| `dist/` | Build-Artefakte (`plugin-recovery-tool.php`, `recovery-*.tar.gz`) |
| `dev/legacy-monolith.php` | Referenz 1.x (nicht releasen) |

Es gibt **keine** `plugin-recovery-tool.php` mehr im Projektroot — die alte Monolith-Datei war ein Duplikat.

## Skripte

| Datei | Zweck |
|--------|--------|
| `build-release.sh [VERSION]` | `dist/recovery-{VERSION}.tar.gz` + Stub |
| `validate-php-syntax.sh` | `php -l` für `stub/` und `recovery-src/` |
| `extract-package-app.sh` | `app.php` aus Monolith regenerieren |
| `split-modes.sh` | Modi in `lib/Recovery/Modes/` aufteilen |
| `deploy-recovery.sh` | Stub + Paket in lokale WoltLab-Installation |

```bash
./dev/build-release.sh 2.0.0
./dev/validate-php-syntax.sh
./dev/deploy-recovery.sh /pfad/zum/wcf-root
```

Auth-Datei `plugin-recovery-auth.php` wird **vom Stub auf dem Server** erzeugt — nicht aus dem Repository.
