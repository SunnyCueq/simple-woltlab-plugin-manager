{*
 * Template-Zweck: URL-Titel und Featured Links Spaltenköpfe für URL-Liste
 * 
 * Spaltenköpfe für die kombinierte URL-Titel/Featured Links-Spalte in der
 * ACP-URL-Liste. Unterstützt Sortierung nach URL-Titel.
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
<th class="columnTitle columnUrl{if $sortField == 'linkTitle'} active {#$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={#$pageNo}&sortField=linkTitle&sortOrder={if $sortField == 'linkTitle' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.url.linkTitle{/lang}</a></th>
<th class="columnTitle columnFeaturedLinks{if $sortField == 'linkTitle'} active {unsafe:$sortOrder}{/if}">{lang}wcf.shrinkr.featuredLinks{/lang}</th>