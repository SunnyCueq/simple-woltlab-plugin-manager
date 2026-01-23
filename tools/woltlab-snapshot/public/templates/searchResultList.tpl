<div class="section sectionContainerList">
	<ul class="gridList messageSearchResultList">
		{foreach from=$objects item=message}
			<li class="gridListItem gridListItemMessage">
				<div class="gridListItemImage">
					{if $customIcons[$message->getObjectTypeName()]|isset}
						<div class="gridListItemLargeIcon">
							{icon size=48 name=$customIcons[$message->getObjectTypeName()]}
						</div>
						<div class="gridListItemSmallIcon">
							{icon size=32 name=$customIcons[$message->getObjectTypeName()]}
						</div>
					{elseif $message->getUserProfile()}
						{user object=$message->getUserProfile() type='avatar48' ariaHidden='true' tabindex='-1'}
					{else}
						<div class="gridListItemLargeIcon">
							{icon size=48 name='file'}
						</div>
						<div class="gridListItemSmallIcon">
							{icon size=32 name='file'}
						</div>
					{/if}
				</div>
				
				<h3 class="gridListItemTitle">
					<a href="{$message->getLink($query)}">{$message->getSubject()}</a>
				</h3>

				{hascontent}
				<div class="gridListItemMeta">
					<ul class="inlineList dotSeparated">
						{content}
							{if $message->getUserProfile()}
								<li>{user object=$message->getUserProfile()}</li>
							{/if}
							{if $message->getTime()}
								<li><small>{@$message->getTime()|time}</small></li>
							{/if}
							{if $message->getContainerTitle()}
								<li><small><a href="{$message->getContainerLink()}">{$message->getContainerTitle()}</a></small></li>
							{/if}
						{/content}
					</ul>
				</div>
				{/hascontent}

				<small class="gridListItemType">{lang}wcf.search.object.{@$message->getObjectTypeName()}{/lang}</small>
				
				<div class="gridListItemContent">{@$message->getFormattedMessage()}</div>
			</li>
		{/foreach}
	</ul>
</div>
