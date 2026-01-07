<td class="columnTitle columnSpecial text-center">
    {if $linksArray|isset && $linksArray[$url->urlID]|isset && $linksArray[$url->urlID]['hasActiveSpecial']|isset && ($linksArray[$url->urlID]['hasActiveSpecial'] == true || $linksArray[$url->urlID]['hasActiveSpecial'] == 1)}
        {* Aktives Special vorhanden: Status + Bearbeiten-Button *}
        <span class="badge green">{lang}wcf.urlshort.special.status.active{/lang}</span>
        {if $linksArray[$url->urlID]['firstActiveSpecialID']|isset && $linksArray[$url->urlID]['firstActiveSpecialID']}
            <a href="{link application='urlshort' controller='SpecialEdit' id=$linksArray[$url->urlID]['firstActiveSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
        {/if}
    {elseif $linksArray|isset && $linksArray[$url->urlID]|isset && $linksArray[$url->urlID]['firstSpecialID']|isset && $linksArray[$url->urlID]['firstSpecialID']}
        {* Inaktives Special vorhanden: 0 + Bearbeiten-Button *}
        0 <a href="{link application='urlshort' controller='SpecialEdit' id=$linksArray[$url->urlID]['firstSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
    {else}
        {* Kein Special vorhanden: 0 / + *}
        0 / <a href="{link application='urlshort' controller='SpecialAdd'}urlID={#$url->urlID}{/link}" title="{lang}wcf.urlshort.special.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
    {/if}
</td>

