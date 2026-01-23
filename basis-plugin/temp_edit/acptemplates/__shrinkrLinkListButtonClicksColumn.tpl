{*
 * Template-Zweck: Button Clicks Spalte für URL-Liste
 * 
 * Zeigt die Gesamtzahl der Button-Klicks für eine URL in der ACP-Liste an.
 * Zeigt Tooltip mit Aufschlüsselung nach Button-Typ (Forward, Featured Link, Custom).
 * 
 * Variablen:
 * @var \shrinkr\data\shrinkrlink\ShrinkrLink $link - Link-Objekt
 * @var array $buttonClicksArray - Array mit Button-Click-Daten pro Link-ID
 * @var int $buttonClicksArray[$link->linkID]['total'] - Gesamtzahl Klicks
 * @var int $buttonClicksArray[$link->linkID]['forward'] - Anzahl Forward-Button-Klicks
 * @var int $buttonClicksArray[$link->linkID]['featured_link'] - Anzahl Featured Link-Klicks
 * @var int $buttonClicksArray[$link->linkID]['custom'] - Anzahl Custom Button-Klicks
 * 
 * Logik:
 * - Prüft ob Button-Klicks vorhanden sind
 * - Zeigt Gesamtzahl mit Tooltip (wenn vorhanden)
 * - Zeigt "0" (wenn keine Klicks vorhanden)
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<td class="columnTitle columnButtonClicks">
    {if $buttonClicksArray|isset && $buttonClicksArray[$link->linkID]|isset && $buttonClicksArray[$link->linkID]['total']|isset && $buttonClicksArray[$link->linkID]['total'] > 0}
        <span class="jsTooltip" title="{lang}wcf.shrinkr.buttonClick.total{/lang}: {#$buttonClicksArray[$link->linkID]['total']}{if $buttonClicksArray[$link->linkID]['forward']|isset && $buttonClicksArray[$link->linkID]['forward'] > 0}&#10;{lang}wcf.shrinkr.buttonClick.type.forward{/lang}: {#$buttonClicksArray[$link->linkID]['forward']}{/if}{if $buttonClicksArray[$link->linkID]['featured_link']|isset && $buttonClicksArray[$link->linkID]['featured_link'] > 0}&#10;{lang}wcf.shrinkr.buttonClick.type.featured_link{/lang}: {#$buttonClicksArray[$link->linkID]['featured_link']}{/if}{if $buttonClicksArray[$link->linkID]['custom']|isset && $buttonClicksArray[$link->linkID]['custom'] > 0}&#10;{lang}wcf.shrinkr.buttonClick.type.custom{/lang}: {#$buttonClicksArray[$link->linkID]['custom']}{/if}">
            {#$buttonClicksArray[$link->linkID]['total']}
        </span>
    {else}
        0
    {/if}
</td>

