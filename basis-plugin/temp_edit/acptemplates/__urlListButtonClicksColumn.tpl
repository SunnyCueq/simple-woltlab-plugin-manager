<td class="columnTitle columnButtonClicks">
    {if $buttonClicksArray|isset && $buttonClicksArray[$url->urlID]|isset && $buttonClicksArray[$url->urlID]['total']|isset && $buttonClicksArray[$url->urlID]['total'] > 0}
        <span class="jsTooltip" title="{lang}wcf.urlshort.buttonClick.total{/lang}: {#$buttonClicksArray[$url->urlID]['total']}{if $buttonClicksArray[$url->urlID]['forward']|isset && $buttonClicksArray[$url->urlID]['forward'] > 0}&#10;{lang}wcf.urlshort.buttonClick.type.forward{/lang}: {#$buttonClicksArray[$url->urlID]['forward']}{/if}{if $buttonClicksArray[$url->urlID]['featured_link']|isset && $buttonClicksArray[$url->urlID]['featured_link'] > 0}&#10;{lang}wcf.urlshort.buttonClick.type.featured_link{/lang}: {#$buttonClicksArray[$url->urlID]['featured_link']}{/if}{if $buttonClicksArray[$url->urlID]['custom']|isset && $buttonClicksArray[$url->urlID]['custom'] > 0}&#10;{lang}wcf.urlshort.buttonClick.type.custom{/lang}: {#$buttonClicksArray[$url->urlID]['custom']}{/if}">
            {#$buttonClicksArray[$url->urlID]['total']}
        </span>
    {else}
        0
    {/if}
</td>

