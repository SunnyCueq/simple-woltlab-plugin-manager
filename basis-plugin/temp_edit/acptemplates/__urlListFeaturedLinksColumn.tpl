<td class="columnTitle columnFeaturedLinks">
    {assign var="featuredLinksCount" value=0}
    {if $linksArray|isset && $linksArray[$url->urlID]|isset && $linksArray[$url->urlID]['countFeaturedLinks']|isset}
        {assign var="featuredLinksCount" value=$linksArray[$url->urlID]['countFeaturedLinks']}
    {/if}
    {if $featuredLinksCount > 0}
        {* Featured Links vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
        {$featuredLinksCount} <a href="{link application='urlshort' controller='UrlEdit' id=$url->urlID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
    {else}
        {* Keine Featured Links vorhanden: 0 / + *}
        0 / <a href="{link application='urlshort' controller='FeaturedLinkAdd'}urlID={#$url->urlID}{/link}" title="{lang}wcf.urlshort.featuredLink.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
    {/if}
</td>

