{*
 * Template-Zweck: ACP-Formular für Hinzufügen von Featured Links
 * 
 * ACP-Template für das Hinzufügen von Featured Links für eine URL.
 * Zeigt Formular-Felder für URL, Titel, Sortierreihenfolge, etc.
 * Wird von WoltLab's FormBuilder gerendert.
 * 
 * Variablen:
 * @var string $action - Formular-Aktion ('add')
 * @var string $urlHash - URL-Hash für Anzeige (optional)
 * @var int $linkID - Link-ID (erforderlich)
 * @var bool $hasExistingFeaturedLinks - Ob bereits Links vorhanden sind
 * @var object $form - FormBuilder-Formular-Objekt
 * 
 * Logik:
 * - Zeigt Formular für Featured Link-Hinzufügen
 * - Zeigt Navigation zur Featured Link-Liste und URL-Bearbeitung
 * - Zeigt Badge "Weiteren" wenn bereits Links vorhanden
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{include file='header' pageTitle='shrinkr.acp.menu.link.featuredLink.'|concat:$action}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">
			{if $hasExistingFeaturedLinks|isset && $hasExistingFeaturedLinks}
				<span class="badge featuredLinkAddBadge">Weiteren</span>
			{/if}
			{lang}shrinkr.acp.menu.link.featuredLink.{$action}{/lang}{if $urlHash} ({$urlHash}){/if}
		</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='FeaturedLinkList'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.featuredLink.list{/lang}</span></a></li>
			{if $linkID}
			<li><a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$linkID}{/link}" class="button">{icon size=16 name='pen-to-square'} <span>{lang}wcf.shrinkr.featuredLink.editUrl{/lang}</span></a></li>
			{/if}
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
					<li><a href="{link controller='FeaturedLinkList'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.featuredLink.list{/lang}</span></a></li>
					{event name='contentFooterNavigation'}
				{/content}
			</ul>
		</nav>
	</footer>
{/hascontent}

{include file='footer'}
