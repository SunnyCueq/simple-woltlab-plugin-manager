{*
 * Template-Zweck: Custom Buttons Spalte für URL-Liste
 * 
 * Zeigt die Anzahl der Custom Buttons für eine URL in der ACP-Liste an.
 * Zeigt Bearbeiten-Button wenn Buttons vorhanden, oder Hinzufügen-Button
 * wenn keine vorhanden sind.
 * 
 * Variablen:
 * @var \shrinkr\data\shrinkrlink\ShrinkrLink $link - Link-Objekt
 * @var array $linksArray - Array mit zusätzlichen Daten pro Link-ID
 * @var int $linksArray[$link->linkID]['countCustomButtons'] - Anzahl Custom Buttons
 * 
 * Logik:
 * - Prüft ob Custom Buttons vorhanden sind
 * - Zeigt Anzahl + Bearbeiten-Button (wenn vorhanden)
 * - Zeigt "0 / +" mit Hinzufügen-Button (wenn keine vorhanden)
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<td class="columnTitle columnCustomButtons text-center">
    {assign var="customButtonsCount" value=0}
    {if $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['countCustomButtons']|isset}
        {assign var="customButtonsCount" value=$linksArray[$link->linkID]['countCustomButtons']}
    {/if}
    {if $customButtonsCount > 0}
        {* Custom Buttons vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
        {$customButtonsCount} <a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$link->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
    {else}
        {* Keine Custom Buttons vorhanden: 0 / + *}
        0 / <a href="{link controller='CustomButtonAdd'}linkID={#$link->linkID}{/link}" title="{lang}wcf.shrinkr.customButton.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
    {/if}
</td>

