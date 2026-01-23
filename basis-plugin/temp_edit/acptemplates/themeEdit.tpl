{*
 * Template-Zweck: ACP-Formular für Bearbeiten von Themes
 * 
 * ACP-Template für das Bearbeiten von Themes. Zeigt Formular-Felder
 * für Identifier, Titel, Effekt, Farben, Custom CSS, etc. Wird von
 * WoltLab's FormBuilder gerendert.
 * 
 * Variablen:
 * @var object $form - FormBuilder-Formular-Objekt
 * 
 * Logik:
 * - Zeigt Formular für Theme-Bearbeitung
 * - Zeigt Footer-Navigation
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{include file='header' pageTitle='shrinkr.acp.menu.link.theme.edit'}

{unsafe:$form->getHtml()}

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

