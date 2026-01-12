<td class="columnTitle columnFeaturedLinks">
    {assign var="featuredLinksCount" value=0}
    {if $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['countFeaturedLinks']|isset}
        {assign var="featuredLinksCount" value=$linksArray[$link->linkID]['countFeaturedLinks']}
    {/if}
    {if $featuredLinksCount > 0}
        {* Featured Links vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
        {$featuredLinksCount} <a href="{link application='shrinkr' controller='ShrinkrLinkEdit' id=$link->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
    {else}
        {* Keine Featured Links vorhanden: 0 / + *}
        0 / <a href="{link application='shrinkr' controller='FeaturedLinkAdd'}linkID={#$link->linkID}{/link}" title="{lang}wcf.shrinkr.featuredLink.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
    {/if}
</td>

