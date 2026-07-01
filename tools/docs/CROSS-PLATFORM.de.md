# Plattformübergreifende Nutzung (SWPM)

**[English version](CROSS-PLATFORM.md)**

SWPM ist ein **Bash**-Toolkit. Unterstützt werden:

| Umgebung | Start |
|----------|--------|
| **Linux** | `./tools.sh` im Repo-Root |
| **macOS** | `./tools.sh` (ggf. Xcode Command Line Tools / Git) |
| **Windows (WSL2)** | Repo in WSL klonen, `./tools.sh` im WSL-Terminal |
| **Windows (Git Bash)** | `./tools.sh` oder `tools.cmd` aus Explorer/cmd |

## Voraussetzungen

- **bash**, **git**, **tar** — Pflicht (werden beim Start geprüft)
- **python3** — empfohlen (Validierungsskripte)
- **php** — optional (PHP-Syntax in `validate-plugin.sh`)
- **Node.js / npm** — optional (TypeScript im Plugin)

Reines **cmd.exe** und **PowerShell** werden nicht direkt unterstützt — **Git Bash** oder **WSL2** verwenden.

## Windows-Hinweise

1. [Git for Windows](https://git-scm.com/download/win) installieren (enthält Git Bash und `tar`).
2. Repo mit Unix-tauglichem Pfad klonen, z. B. `C:\dev\simple-woltlab-plugin-manager` oder unter WSL in `~/projects/`.
3. Aus **cmd** oder Explorer: `tools.cmd help`
4. Zeilenenden: Skripte nutzen LF. Bei `$'\r'`-Fehlern: `git config core.autocrlf input` und erneut auschecken.

## Optionale Editor-Workspace-Datei

`simple-woltlab-plugin-manager.code-workspace` ist optional (VS Code o. Ä.). Alle Funktionen laufen ohne Editor allein im Terminal.
