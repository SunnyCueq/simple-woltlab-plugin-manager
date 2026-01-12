<td class="columnTitle columnCustomButtons text-center">
    {assign var="customButtonsCount" value=0}
    {if $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['countCustomButtons']|isset}
        {assign var="customButtonsCount" value=$linksArray[$link->linkID]['countCustomButtons']}
    {/if}
    {if $customButtonsCount > 0}
        {* Custom Buttons vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
        {$customButtonsCount} <a href="{link application='shrinkr' controller='ShrinkrLinkEdit' id=$link->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
    {else}
        {* Keine Custom Buttons vorhanden: 0 / + *}
        0 / <a href="{link application='shrinkr' controller='CustomButtonAdd'}linkID={#$link->linkID}{/link}" title="{lang}wcf.shrinkr.customButton.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
    {/if}
</td>

