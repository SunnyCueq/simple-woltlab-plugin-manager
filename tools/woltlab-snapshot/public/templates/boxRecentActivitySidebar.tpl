<ul class="sidebarItemList">
	{foreach from=$eventList item=event}
		<li class="box24{if $__wcf->getUserProfileHandler()->isIgnoredUser($event->getUserProfile()->userID, 2)} ignoredUserContent{/if}">
			{user object=$event->getUserProfile() type='avatar24' ariaHidden='true' tabindex='-1'}
			
			<div class="sidebarItemTitle">
				{if $event->getLink()}
					<a href="{$event->getLink()}">{unsafe:$event->getTitle()}</a>
				{else}
					{unsafe:$event->getTitle()}
				{/if}

				<p><small>{time time=$event->time}</small></p>
			</div>
		</li>
	{/foreach}
</ul>
