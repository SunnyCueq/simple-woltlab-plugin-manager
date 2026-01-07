<td class="columnUrlTitle columnUrlTitle">{$url->urlTitle}</td>
<td class="columnTitle columnFeaturedLinks" title="{if $linksArray[$url->urlID][plainLinks]|isset}
{implode from=$linksArray[$url->urlID][plainLinks] key=link item=title glue="\n"}{$title}: {$link}{/implode}
{/if}">
    {#$linksArray[$url->urlID][countFeaturedLinks]}
</td>
