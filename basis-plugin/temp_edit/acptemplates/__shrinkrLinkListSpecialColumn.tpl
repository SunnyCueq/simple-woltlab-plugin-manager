{*
 * Template-Zweck: Special Spalte für URL-Liste
 * 
 * Zeigt den Status des Special-Events für eine URL in der ACP-Liste an.
 * Zeigt "Aktiv"-Badge wenn aktives Special vorhanden, oder "0" wenn inaktiv,
 * oder "0 / +" wenn kein Special vorhanden ist.
 * 
 * Variablen:
 * @var \shrinkr\data\shrinkrlink\ShrinkrLink $link - Link-Objekt
 * @var array $linksArray - Array mit zusätzlichen Daten pro Link-ID
 * @var bool $linksArray[$link->linkID]['hasActiveSpecial'] - Ob aktives Special vorhanden
 * @var int $linksArray[$link->linkID]['firstActiveSpecialID'] - ID des ersten aktiven Specials
 * @var int $linksArray[$link->linkID]['firstSpecialID'] - ID des ersten Specials (auch inaktiv)
 * 
 * Logik:
 * - Prüft ob aktives Special vorhanden ist
 * - Zeigt "Aktiv"-Badge + Bearbeiten-Button (wenn aktiv)
 * - Zeigt "0" + Bearbeiten-Button (wenn inaktiv)
 * - Zeigt "0 / +" mit Hinzufügen-Button (wenn kein Special vorhanden)
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<td class="columnTitle columnSpecial text-center">
    {if $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['hasActiveSpecial']|isset && ($linksArray[$link->linkID]['hasActiveSpecial'] == true || $linksArray[$link->linkID]['hasActiveSpecial'] == 1)}
        {* Aktives Special vorhanden: Status + Bearbeiten-Button *}
        <span class="badge green">{lang}wcf.shrinkr.special.status.active{/lang}</span>
        {if $linksArray[$link->linkID]['firstActiveSpecialID']|isset && $linksArray[$link->linkID]['firstActiveSpecialID']}
            <a href="{link controller='SpecialEdit' application='shrinkr' id=$linksArray[$link->linkID]['firstActiveSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
        {/if}
    {elseif $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['firstSpecialID']|isset && $linksArray[$link->linkID]['firstSpecialID']}
        {* Inaktives Special vorhanden: 0 + Bearbeiten-Button *}
        0 <a href="{link controller='SpecialEdit' application='shrinkr' id=$linksArray[$link->linkID]['firstSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
    {else}
        {* Kein Special vorhanden: 0 / + *}
        0 / <a href="{link controller='SpecialAdd'}linkID={#$link->linkID}{/link}" title="{lang}wcf.shrinkr.special.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
    {/if}
</td>

