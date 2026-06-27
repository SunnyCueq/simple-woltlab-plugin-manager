# Recovery Tool: WoltLab ACP-Stylesheet (statt Beer CSS)

## Ziel

Beer CSS entfernen. Basis-Stylesheet = **`acp/style/setup/WCFSetup.css`** aus der Installation (wie [Rescue Mode](https://github.com/woltlab/woltlab-suite/blob/master/wcfsetup/install/files/acp/templates/rescueMode.tpl) / offizielles Setup).

## Referenz WoltLab (`woltlab-github`)

- `wcfsetup/install/files/acp/templates/rescueMode.tpl` — HTML-Struktur
- `wcfsetup/install/files/acp/style/setup/WCFSetup.css` — ACP-Setup-Styles
- `RescueModeForm.class.php` — Assets: Logo + WCFSetup (data-URI im ACP; Recovery nutzt **relative URLs**)

## Umsetzung (erledigt in v2.0.2)

| Bereich | Änderung |
|---------|----------|
| `lib/Recovery/Ui/SetupAssets.php` | `recoveryGetSetupAssets()`, `recoveryAssetPublicHref()` |
| `lib/Recovery/Ui/AcpLayout.php` | `recoveryRenderPageStart/End` mit `pageContainer`, `wcfAcp`, Logo |
| `lib/Recovery/Ui/recovery-acp-extensions.css` | Nur Wizard, Nav, Szenario-Karten (kein WCF-Override) |
| `stub/recovery-stub-shell.php` | Auth-UI gleiche ACP-Hülle |
| `app.php` | Beer-Konstanten + alte PageStart/End entfernt |

## Release

- `plugin-recovery-tool.php` + `recovery-2.0.2.tar.gz` unter GitHub Releases
