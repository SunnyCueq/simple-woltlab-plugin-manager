<td class="columnTitle columnSpecial text-center">
    {if $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['hasActiveSpecial']|isset && ($linksArray[$link->linkID]['hasActiveSpecial'] == true || $linksArray[$link->linkID]['hasActiveSpecial'] == 1)}
        {* Aktives Special vorhanden: Status + Bearbeiten-Button *}
        <span class="badge green">{lang}wcf.shrinkr.special.status.active{/lang}</span>
        {if $linksArray[$link->linkID]['firstActiveSpecialID']|isset && $linksArray[$link->linkID]['firstActiveSpecialID']}
            <a href="{link application='shrinkr' controller='SpecialEdit' id=$linksArray[$link->linkID]['firstActiveSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
        {/if}
    {elseif $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['firstSpecialID']|isset && $linksArray[$link->linkID]['firstSpecialID']}
        {* Inaktives Special vorhanden: 0 + Bearbeiten-Button *}
        0 <a href="{link application='shrinkr' controller='SpecialEdit' id=$linksArray[$link->linkID]['firstSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
    {else}
        {* Kein Special vorhanden: 0 / + *}
        0 / <a href="{link application='shrinkr' controller='SpecialAdd'}linkID={#$link->linkID}{/link}" title="{lang}wcf.shrinkr.special.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
    {/if}
</td>

