{*
 * Template-Zweck: Font Awesome Icon Option Type
 * 
 * Zeigt Icon-Auswahl-Dialog für Optionen. Ermöglicht Auswahl eines
 * Font Awesome Icons mit Option für Solid-Style. Zeigt Vorschau des
 * ausgewählten Icons.
 * 
 * Variablen:
 * @var \wcf\data\option\Option $option - Option-Objekt
 * @var string $option->optionName - Option-Name
 * @var string $value - Aktueller Wert (Format: "iconName;forceSolid")
 * @var \wcf\system\style\FontAwesomeIcon|null $icon - Aktuelles Icon-Objekt
 * @var bool $__iconFormFieldIncludeJavaScript - Ob JavaScript eingebunden werden soll
 * 
 * Logik:
 * - Zeigt aktuelles Icon (wenn vorhanden)
 * - Zeigt Bearbeiten-Button zum Öffnen des Icon-Dialogs
 * - Zeigt Löschen-Button (wenn Icon vorhanden)
 * - Initialisiert Font Awesome Dialog
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<span id="{$option->optionName}_icon">
	{if $icon}
		{unsafe:$icon->toHtml(64)}
	{/if}
</span>
<button type="button" class="button small" id="{$option->optionName}_openIconDialog">{lang}wcf.global.button.edit{/lang}</button>
<button type="button" class="button small" id="{$option->optionName}_removeIcon"{if !$icon} hidden{/if}>{lang}wcf.global.button.delete{/lang}</button>
<input type="hidden" id="{$option->optionName}" name="values[{$option->optionName}]" value="{$value}">

{if $__iconFormFieldIncludeJavaScript}
	{include file='shared_fontAwesomeJavaScript'}
{/if}

<script data-relocate="true">
	require(['WoltLabSuite/Core/Ui/Style/FontAwesome'], (UiStyleFontAwesome) => {
		const iconContainer = document.getElementById('{unsafe:$option->optionName|encodeJS}_icon');
		const input = document.getElementById('{unsafe:$option->optionName|encodeJS}');
		const buttonRemoveIcon = document.getElementById('{unsafe:$option->optionName|encodeJS}_removeIcon');
		
		const callback = (iconName, forceSolid) => {
			input.value = `${ iconName };${ forceSolid }`;

			let icon = iconContainer.querySelector("fa-icon");
			if (icon) {
				icon.setIcon(iconName, forceSolid);
			} else {
				icon = document.createElement("fa-icon");
				icon.size = 64;
				icon.setIcon(iconName, forceSolid);
				iconContainer.append(icon);
			}

			buttonRemoveIcon.hidden = false;
		};
		
		const button = document.getElementById('{unsafe:$option->optionName|encodeJS}_openIconDialog');
		button.addEventListener('click', () => UiStyleFontAwesome.open(callback));
		buttonRemoveIcon.addEventListener("click", () => {
			input.value = "";
			iconContainer.querySelector("fa-icon")?.remove();

			buttonRemoveIcon.hidden = true;
		});
	});
</script>

