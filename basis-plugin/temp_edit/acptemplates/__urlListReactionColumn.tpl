{if MODULE_LIKE && $__wcf->session->getPermission('user.like.canViewLike')}
	<td class="columnText">
		{assign var='__reactionSummaryJson' value='[]'}
		{assign var='__hasReactions' value=false}
		{if $reactionData|isset && $reactionData[$url->urlID]|isset}
			{assign var='__reactionSummaryJson' value=$reactionData[$url->urlID]->getReactionsJson()}
			{if $reactionData[$url->urlID]->cumulativeLikes > 0}
				{assign var='__hasReactions' value=true}
			{/if}
		{/if}
		{if $__hasReactions}
		<woltlab-core-reaction-summary
			data="{$__reactionSummaryJson}"
			object-type="{$reactionObjectType}"
			object-id="{#$url->urlID}"
			selected-reaction="{if $reactionData|isset && $reactionData[$url->urlID]|isset && $reactionData[$url->urlID]->reactionTypeID}{#$reactionData[$url->urlID]->reactionTypeID}{else}0{/if}"
		></woltlab-core-reaction-summary>
		{else}
			0
		{/if}
	</td>
{/if}

