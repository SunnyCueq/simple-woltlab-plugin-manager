{if MODULE_LIKE && $__wcf->session->getPermission('user.like.canViewLike')}
	<td class="columnText">
		{assign var='__reactionSummaryJson' value='[]'}
		{assign var='__hasReactions' value=false}
		{if $reactionData|isset && $reactionData[$link->linkID]|isset}
			{assign var='__reactionSummaryJson' value=$reactionData[$link->linkID]->getReactionsJson()}
			{if $reactionData[$link->linkID]->cumulativeLikes > 0}
				{assign var='__hasReactions' value=true}
			{/if}
		{/if}
		{if $__hasReactions}
		<woltlab-core-reaction-summary
			data="{$__reactionSummaryJson}"
			object-type="{$reactionObjectType}"
			object-id="{#$link->linkID}"
			selected-reaction="{if $reactionData|isset && $reactionData[$link->linkID]|isset && $reactionData[$link->linkID]->reactionTypeID}{#$reactionData[$link->linkID]->reactionTypeID}{else}0{/if}"
		></woltlab-core-reaction-summary>
		{else}
			0
		{/if}
	</td>
{/if}

