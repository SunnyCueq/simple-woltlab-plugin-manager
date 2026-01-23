{*
 * Template-Zweck: ACP-Formular für Hinzufügen/Bearbeiten von Specials
 * 
 * ACP-Template für das Hinzufügen und Bearbeiten von Special-Events.
 * Zeigt Formular-Felder für Titel, Theme, Zeitraum, Discount, etc.
 * Wird von WoltLab's FormBuilder gerendert.
 * 
 * Variablen:
 * @var string $action - Formular-Aktion ('add' oder 'edit')
 * @var string $urlHash - URL-Hash für Anzeige (optional)
 * @var int $linkID - Link-ID (optional)
 * @var object $form - FormBuilder-Formular-Objekt
 * 
 * Logik:
 * - Zeigt Formular für Special-Hinzufügen/Bearbeiten
 * - Zeigt Navigation zur Special-Liste und URL-Bearbeitung
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{include file='header' pageTitle='shrinkr.acp.menu.link.special.'|concat:$action}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}shrinkr.acp.menu.link.special.{$action}{/lang}{if $urlHash} ({$urlHash}){/if}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='SpecialList'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.special.list{/lang}</span></a></li>
			{if $linkID}
				<li><a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$linkID}{/link}" class="button">{icon size=16 name='pen-to-square'} <span>{lang}wcf.shrinkr.special.editUrl{/lang}</span></a></li>
			{/if}
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{unsafe:$form->getHtml()}

{* JavaScript to auto-fill colors when theme changes *}
{if $themes|isset && $themes|count}
<script data-relocate="true">
	require(['Dom/Util'], function(DomUtil) {
		var themeSelect = document.getElementById('theme');
		var themes = {unsafe:$themes|json};
		
		if (themeSelect && themes) {
			themeSelect.addEventListener('change', function() {
				var selectedTheme = this.value;
				if (themes[selectedTheme]) {
					var theme = themes[selectedTheme];
					
					// Update color fields
					var primaryColor = document.getElementById('primaryColor');
					var secondaryColor = document.getElementById('secondaryColor');
					var primaryTextColor = document.getElementById('primaryTextColor');
					var secondaryTextColor = document.getElementById('secondaryTextColor');
					
					if (primaryColor) primaryColor.value = theme.primaryColor;
					if (secondaryColor) secondaryColor.value = theme.secondaryColor;
					if (primaryTextColor) primaryTextColor.value = theme.primaryTextColor;
					if (secondaryTextColor) secondaryTextColor.value = theme.secondaryTextColor;
					
					// Trigger change event for color pickers
					[primaryColor, secondaryColor, primaryTextColor, secondaryTextColor].forEach(function(field) {
						if (field) {
							var event = new Event('change', { bubbles: true });
							field.dispatchEvent(event);
						}
					});
				}
			});
		}
	});
</script>
{/if}

{hascontent}
	<footer class="contentFooter">
		<nav class="contentFooterNavigation">
			<ul>
				{content}{event name='contentFooterNavigation'}{/content}
			</ul>
		</nav>
	</footer>
{/hascontent}

{include file='footer'}

