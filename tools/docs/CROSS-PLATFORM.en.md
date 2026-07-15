# Cross-platform usage (SWPM)

SWPM is a **Bash** toolkit. It runs on:

| Environment | How to start |
|-------------|----------------|
| **Linux** | `./tools.sh` from the repo root |
| **macOS** | `./tools.sh` (install Git/Xcode CLI tools if needed) |
| **Windows (WSL2)** | Clone the repo inside WSL, run `./tools.sh` in a WSL terminal |
| **Windows (Git Bash)** | `./tools.sh` or double-click / run `tools.cmd` from Explorer |

## Requirements

- **bash**, **git**, **tar** — required (checked on startup)
- **python3** — recommended (validation scripts)
- **php** — optional (PHP syntax check in `validate-plugin.sh`)
- **Node.js / npm** — optional (TypeScript in your plugin)

Plain **cmd.exe** and **PowerShell** are not supported directly — please use **Git Bash** or **WSL2**.

## Windows tips

1. Install [Git for Windows](https://git-scm.com/download/win) (includes Git Bash and `tar`).
2. Clone with a Unix-friendly path, e.g. `C:\dev\simple-woltlab-plugin-manager` or inside WSL under `~/projects/`.
3. From **cmd** or Explorer: `tools.cmd help`
4. Line endings: keep `core.autocrlf` as you prefer; scripts use LF. If `./tools.sh` fails with `$'\r'`, run `git config core.autocrlf input` and re-checkout.

## Optional editor workspace

`simple-woltlab-plugin-manager.code-workspace` is optional (VS Code or any compatible editor). All features work from the terminal alone.
