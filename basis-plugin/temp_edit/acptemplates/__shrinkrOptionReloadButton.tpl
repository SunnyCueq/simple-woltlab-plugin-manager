{*
 * Template-Zweck: Reload-Button für Optionen-Seite
 * 
 * Fügt einen Reload-Button zur Success-Notice hinzu, wenn das Plugin
 * gerade aktiviert wurde. Ermöglicht schnelles Neuladen der Seite
 * um neue Menüpunkte anzuzeigen.
 * 
 * Variablen:
 * @var bool $success - Ob Optionen erfolgreich gespeichert wurden
 * @var object $category - Optionen-Kategorie
 * @var string $category->categoryName - Kategorie-Name
 * @var int $optionTree|count - Anzahl sichtbarer Unterkategorien
 * 
 * Logik:
 * - Prüft ob Plugin gerade aktiviert wurde (shrinkr_active = true)
 * - Fügt Reload-Button zur Success-Notice hinzu
 * - Button lädt Seite neu um neue Menüpunkte anzuzeigen
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{* Bedingungen: Hauptkategorie 'shrinkr' + Erfolg + nur 1 sichtbare Unterkategorie (Allgemein) *}
{if $success|isset && $category|isset && $category->categoryName === 'shrinkr' && $optionTree|count === 1}
<script data-relocate="true">
document.addEventListener('DOMContentLoaded', function() {
	// Prüfe ob shrinkr_active aktiviert ist
	var activeCheckbox = document.getElementById('shrinkr_active');
	if (!activeCheckbox || !activeCheckbox.checked) return;
	
	// Suche die Success-Notice
	var notice = document.querySelector('woltlab-core-notice[type="success"]');
	if (!notice) return;
	
	// Füge Reload-Button hinzu (WoltLab-Standard-Format)
	var btn = document.createElement('a');
	btn.href = window.location.href;
	btn.className = 'button small';
	btn.style.marginLeft = '10px';
	
	// Erstelle Icon
	var icon = document.createElement('fa-icon');
	icon.setAttribute('size', '16');
	icon.setAttribute('name', 'arrows-rotate');
	icon.setAttribute('aria-hidden', 'true');
	icon.setAttribute('translate', 'no');
	btn.appendChild(icon);
	
	// Füge Text hinzu (direkt neben Icon, kein Abstand)
	var span = document.createElement('span');
	span.textContent = '{jslang}shrinkr.acp.option.reload{/jslang}';
	btn.appendChild(span);
	
	notice.appendChild(btn);
});
</script>
{/if}
