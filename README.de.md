<p align="center">
  <img src="docs/assets/swpm-logo.png" alt="SWPM — Simple WoltLab Plugin Manager" width="300">
</p>

<h1 align="center">Simple WoltLab Plugin Manager</h1>

<p align="center">
  <strong>CLI-Workbench für WoltLab-Plugins — Build, Validierung und Release im Terminal.</strong>
</p>

<p align="center">
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/releases/latest"><img src="https://img.shields.io/github/v/release/benjarogit/simple-woltlab-plugin-manager?style=for-the-badge&color=0e7490&labelColor=0a0e18" alt="Aktuelles Release"></a>
  <a href="https://benjarogit.github.io/simple-woltlab-plugin-manager/"><img src="https://img.shields.io/badge/Docs-Handbuch%20DE%2FEN-4f46e5?style=for-the-badge&labelColor=0a0e18" alt="Dokumentation"></a>
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/blob/main/LICENSE"><img src="https://img.shields.io/badge/Lizenz-MIT-65a30d?style=for-the-badge&labelColor=0a0e18" alt="MIT-Lizenz"></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/WoltLab%20Suite-6.2%2B-0e7490?style=flat-square&logoColor=white&labelColor=0a0e18" alt="WoltLab Suite 6.2+">
  <img src="https://img.shields.io/badge/Plattform-Linux%20%7C%20macOS%20%7C%20WSL2%20%7C%20Git%20Bash-0d9488?style=flat-square&labelColor=0a0e18" alt="Plattformen">
  <img src="https://img.shields.io/badge/Vibe%20Coding-Friendly-e11d48?style=flat-square&labelColor=0a0e18" alt="Vibe Coding Friendly">
  <a href="https://github.com/benjarogit/simple-woltlab-plugin-manager/actions/workflows/tools-tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/benjarogit/simple-woltlab-plugin-manager/tools-tests.yml?branch=main&style=flat-square&label=toolkit%20tests&labelColor=0a0e18" alt="Toolkit-Tests"></a>
</p>

<p align="center"><a href="README.md"><strong>English version</strong></a></p>

---

## Dokumentation (Handbuch)

Alles Wichtige steht im **Online-Handbuch** — Navigation, Suche, Deutsch und Englisch. Die README hier ist nur der Einstieg.

**→ [SWPM Dokumentation](https://benjarogit.github.io/simple-woltlab-plugin-manager/)**

Quelle im Repo: `tools/docs/` (wird bei Push auf `main` automatisch nach GitHub Pages gebaut).

---

## Was ist SWPM?

Generisches Toolkit für WoltLab-Suite-Plugins: Paket schnüren (`package.xml`), Checks laufen lassen, Store-Regeln und Übersetzungen prüfen — im Terminal-Menü, ohne Bindung an ein bestimmtes Produkt-Plugin. Orientiert an den [WoltLab Plugin-Store-Richtlinien](https://www.woltlab.com/pluginstore/de/richtlinien/).

Gebaut für agenten-gestützte Abläufe mit Bodenhaftung: klares CLI, lokale Checks und Doku, die Tools ansteuern können — **vibe coding friendly**, weiterhin store-tauglich.

Kurzüberblick:

- Interaktives Menü: `./tools.sh`
- Build → installierbare `.tar.gz` (patch/minor/major/same)
- Validierung (PHP/XML, Templates, Sprachen, Store-Heuristiken)
- Optional: Produktlinie (Basis + Add-ons), TypeScript, PHPStan, Docker-Helfer für ACP

Details und Guides: **[Handbuch](https://benjarogit.github.io/simple-woltlab-plugin-manager/)**.

---

## Schnellstart

```bash
git clone https://github.com/benjarogit/simple-woltlab-plugin-manager.git
cd simple-woltlab-plugin-manager
./tools.sh
```

Windows ohne Unix-Shell: `tools.cmd`. Optional: `./tools.sh setup` und `tools/.env` aus `.env.example`.

Häufige Befehle: `./tools.sh build` · `./tools.sh validate …` · `./tools.sh help`  
CLI-Details: [tools/README.de.md](tools/README.de.md)

---

## Mitwirken & Lizenz

Beiträge willkommen — SWPM bleibt **generisch** (keine Produkt-Hardcodierung). Siehe [CONTRIBUTING.de.md](CONTRIBUTING.de.md).

**[MIT-Lizenz](LICENSE)** · [Changelog](CHANGELOG.md) · [Releases](https://github.com/benjarogit/simple-woltlab-plugin-manager/releases/latest)
