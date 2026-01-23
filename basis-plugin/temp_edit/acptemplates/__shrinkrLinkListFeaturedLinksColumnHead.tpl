{*
 * Template-Zweck: Featured Links Spaltenkopf für URL-Liste
 * 
 * Spaltenkopf für die Featured Links-Spalte in der ACP-URL-Liste.
 * Unterstützt Sortierung nach Anzahl Featured Links.
 * 
 * Variablen:
 * @var string $sortField - Aktuelles Sortierfeld
 * @var string $sortOrder - Aktuelle Sortierreihenfolge
 * @var int $pageNo - Aktuelle Seitenzahl
 * @var string $q - Suchfilter
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<th class="columnTitle columnFeaturedLinks{if $sortField == 'featuredLinks'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=featuredLinks&sortOrder={if $sortField == 'featuredLinks' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.featuredLink.section{/lang}</a></th>

