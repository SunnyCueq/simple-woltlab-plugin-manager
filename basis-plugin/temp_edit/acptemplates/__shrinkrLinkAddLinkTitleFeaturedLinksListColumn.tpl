{*
 * Template-Zweck: URL-Titel und Featured Links Spalte für URL-Liste
 * 
 * Zeigt URL-Titel und Anzahl Featured Links in einer kombinierten Spalte
 * in der ACP-URL-Liste an. Zeigt Tooltip mit Liste aller Featured Links.
 * 
 * Variablen:
 * @var \shrinkr\data\shrinkrlink\ShrinkrLink $link - Link-Objekt
 * @var array $linksArray - Array mit zusätzlichen Daten pro Link-ID
 * @var array $linksArray[$link->linkID][plainLinks] - Array von Featured Links (URL => Titel)
 * @var int $linksArray[$link->linkID][countFeaturedLinks] - Anzahl Featured Links
 * 
 * Logik:
 * - Zeigt URL-Titel
 * - Zeigt Anzahl Featured Links mit Tooltip (Liste aller Links)
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<td class="columnUrlTitle columnUrlTitle">{$link->linkTitle}</td>
<td class="columnTitle columnFeaturedLinks" title="{if $linksArray[$link->linkID][plainLinks]|isset}
{implode from=$linksArray[$link->linkID][plainLinks] key=link item=title glue="\n"}{$title}: {$link}{/implode}
{/if}">
    {#$linksArray[$link->linkID][countFeaturedLinks]}
</td>
