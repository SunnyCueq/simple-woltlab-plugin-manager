{*
 * Template-Zweck: Custom Buttons Spaltenkopf für URL-Liste
 * 
 * Spaltenkopf für die Custom Buttons-Spalte in der ACP-URL-Liste.
 * Unterstützt Sortierung nach Anzahl Custom Buttons.
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
<th class="columnTitle columnCustomButtons text-center{if $sortField == 'customButtons'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=customButtons&sortOrder={if $sortField == 'customButtons' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.customButton.section{/lang}</a></th>

