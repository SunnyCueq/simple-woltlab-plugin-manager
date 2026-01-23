{*
 * Template-Zweck: ACP-Formular für Hinzufügen von Themes
 * 
 * ACP-Template für das Hinzufügen von Themes. Zeigt Formular-Felder
 * für Identifier, Titel, Effekt, Farben, etc. Wird von WoltLab's
 * FormBuilder gerendert.
 * 
 * Variablen:
 * @var string $action - Formular-Aktion ('add')
 * @var object $form - FormBuilder-Formular-Objekt
 * 
 * Logik:
 * - Zeigt Formular für Theme-Hinzufügen
 * - Zeigt Navigation zur Theme-Liste
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{include file='header' pageTitle='shrinkr.acp.menu.link.theme.add'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}shrinkr.acp.menu.link.theme.{$action}{/lang}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='ThemeList'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.theme.list{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{unsafe:$form->getHtml()}

{hascontent}
	<footer class="contentFooter">
		<nav class="contentFooterNavigation">
			<ul>
				{content}
					<li><a href="{link controller='ThemeList'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.theme.list{/lang}</span></a></li>
					{event name='contentFooterNavigation'}
				{/content}
			</ul>
		</nav>
	</footer>
{/hascontent}

{include file='footer'}

