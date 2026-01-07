{include file='header' pageTitle='urlshort.acp.menu.link.featuredLink.'|concat:$action}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">
			{if $hasExistingFeaturedLinks|isset && $hasExistingFeaturedLinks}
				<span class="badge featuredLinkAddBadge">Weiteren</span>
			{/if}
			{lang}urlshort.acp.menu.link.featuredLink.{$action}{/lang}{if $urlHash} ({$urlHash}){/if}
		</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='FeaturedLinkList' application='urlshort'}urlID={#$urlID}{/link}" class="button">{icon size=16 name='list'} <span>{lang}urlshort.acp.menu.link.featuredLink.list{/lang}</span></a></li>
			{if $urlID}
			<li><a href="{link controller='UrlEdit' application='urlshort' id=$urlID}{/link}" class="button">{icon size=16 name='pen-to-square'} <span>{lang}wcf.urlshort.featuredLink.editUrl{/lang}</span></a></li>
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
					<li><a href="{link controller='FeaturedLinkList' application='urlshort'}urlID={#$urlID}{/link}" class="button">{icon size=16 name='list'} <span>{lang}urlshort.acp.menu.link.featuredLink.list{/lang}</span></a></li>
					{event name='contentFooterNavigation'}
				{/content}
			</ul>
		</nav>
	</footer>
{/hascontent}

{include file='footer'}
