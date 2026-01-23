{*
 * Template-Zweck: Special Spaltenkopf für URL-Liste
 * 
 * Spaltenkopf für die Special-Spalte in der ACP-URL-Liste.
 * Unterstützt Sortierung nach Special-Status.
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
<th class="columnTitle columnSpecial text-center{if $sortField == 'special'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=special&sortOrder={if $sortField == 'special' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.special{/lang}</a></th>

