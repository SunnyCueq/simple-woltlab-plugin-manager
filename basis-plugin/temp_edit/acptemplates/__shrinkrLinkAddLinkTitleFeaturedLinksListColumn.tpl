<td class="columnUrlTitle columnUrlTitle">{$link->linkTitle}</td>
<td class="columnTitle columnFeaturedLinks" title="{if $linksArray[$link->linkID][plainLinks]|isset}
{implode from=$linksArray[$link->linkID][plainLinks] key=link item=title glue="\n"}{$title}: {$link}{/implode}
{/if}">
    {#$linksArray[$link->linkID][countFeaturedLinks]}
</td>
