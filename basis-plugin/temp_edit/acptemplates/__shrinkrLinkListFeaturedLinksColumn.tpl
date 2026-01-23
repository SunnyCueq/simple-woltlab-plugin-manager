{*
 * Template-Zweck: Featured Links Spalte für URL-Liste
 * 
 * Zeigt die Anzahl der Featured Links für eine URL in der ACP-Liste an.
 * Zeigt Bearbeiten-Button wenn Links vorhanden, oder Hinzufügen-Button
 * wenn keine vorhanden sind.
 * 
 * Variablen:
 * @var \shrinkr\data\shrinkrlink\ShrinkrLink $link - Link-Objekt
 * @var array $linksArray - Array mit zusätzlichen Daten pro Link-ID
 * @var int $linksArray[$link->linkID]['countFeaturedLinks'] - Anzahl Featured Links
 * 
 * Logik:
 * - Prüft ob Featured Links vorhanden sind
 * - Zeigt Anzahl + Bearbeiten-Button (wenn vorhanden)
 * - Zeigt "0 / +" mit Hinzufügen-Button (wenn keine vorhanden)
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<td class="columnTitle columnFeaturedLinks">
    {assign var="featuredLinksCount" value=0}
    {if $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['countFeaturedLinks']|isset}
        {assign var="featuredLinksCount" value=$linksArray[$link->linkID]['countFeaturedLinks']}
    {/if}
    {if $featuredLinksCount > 0}
        {* Featured Links vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
        {$featuredLinksCount} <a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$link->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
    {else}
        {* Keine Featured Links vorhanden: 0 / + *}
        0 / <a href="{link controller='FeaturedLinkAdd'}linkID={#$link->linkID}{/link}" title="{lang}wcf.shrinkr.featuredLink.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
    {/if}
</td>

