<td class="columnTitle columnCustomButtons text-center">
    {assign var="customButtonsCount" value=0}
    {if $linksArray|isset && $linksArray[$url->urlID]|isset && $linksArray[$url->urlID]['countCustomButtons']|isset}
        {assign var="customButtonsCount" value=$linksArray[$url->urlID]['countCustomButtons']}
    {/if}
    {if $customButtonsCount > 0}
        {* Custom Buttons vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
        {$customButtonsCount} <a href="{link application='urlshort' controller='UrlEdit' id=$url->urlID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
    {else}
        {* Keine Custom Buttons vorhanden: 0 / + *}
        0 / <a href="{link application='urlshort' controller='CustomButtonAdd'}urlID={#$url->urlID}{/link}" title="{lang}wcf.urlshort.customButton.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
    {/if}
</td>

