<td class="columnTitle columnButtonClicks">
    {if $buttonClicksArray|isset && $buttonClicksArray[$link->linkID]|isset && $buttonClicksArray[$link->linkID]['total']|isset && $buttonClicksArray[$link->linkID]['total'] > 0}
        <span class="jsTooltip" title="{lang}wcf.shrinkr.buttonClick.total{/lang}: {#$buttonClicksArray[$link->linkID]['total']}{if $buttonClicksArray[$link->linkID]['forward']|isset && $buttonClicksArray[$link->linkID]['forward'] > 0}&#10;{lang}wcf.shrinkr.buttonClick.type.forward{/lang}: {#$buttonClicksArray[$link->linkID]['forward']}{/if}{if $buttonClicksArray[$link->linkID]['featured_link']|isset && $buttonClicksArray[$link->linkID]['featured_link'] > 0}&#10;{lang}wcf.shrinkr.buttonClick.type.featured_link{/lang}: {#$buttonClicksArray[$link->linkID]['featured_link']}{/if}{if $buttonClicksArray[$link->linkID]['custom']|isset && $buttonClicksArray[$link->linkID]['custom'] > 0}&#10;{lang}wcf.shrinkr.buttonClick.type.custom{/lang}: {#$buttonClicksArray[$link->linkID]['custom']}{/if}">
            {#$buttonClicksArray[$link->linkID]['total']}
        </span>
    {else}
        0
    {/if}
</td>

