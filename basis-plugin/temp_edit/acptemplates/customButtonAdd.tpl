{include file='header' pageTitle='shrinkr.acp.menu.link.customButton.'|concat:$action}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">
			{if $hasExistingCustomButtons|isset && $hasExistingCustomButtons}
				<span class="badge customButtonAddBadge">Weiteren</span>
			{/if}
			{lang}shrinkr.acp.menu.link.customButton.{$action}{/lang}{if $urlHash} ({$urlHash}){/if}
		</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='CustomButtonList' application='shrinkr'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.customButton.list{/lang}</span></a></li>
			{if $linkID}
			<li><a href="{link controller='UrlEdit' application='shrinkr' id=$linkID}{/link}" class="button">{icon size=16 name='pen-to-square'} <span>{lang}wcf.shrinkr.customButton.editUrl{/lang}</span></a></li>
			{/if}
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{unsafe:$form->getHtml()}

{hascontent}
	<footer class="contentFooter">
		<nav class="contentFooterNavigation">
			<ul>
				{content}
					<li><a href="{link controller='CustomButtonList' application='shrinkr'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.customButton.list{/lang}</span></a></li>
					{event name='contentFooterNavigation'}
				{/content}
			</ul>
		</nav>
	</footer>
{/hascontent}

{include file='footer'}

