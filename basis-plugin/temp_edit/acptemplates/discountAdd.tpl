{include file='header' pageTitle='shrinkr.acp.menu.link.discount.'|concat:$action}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}shrinkr.acp.menu.link.discount.{$action}{/lang}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='DiscountList' application='shrinkr'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.discount.list{/lang}</span></a></li>
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
					<li><a href="{link controller='DiscountList' application='shrinkr'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.discount.list{/lang}</span></a></li>
					{event name='contentFooterNavigation'}
				{/content}
			</ul>
		</nav>
	</footer>
{/hascontent}

{include file='footer'}
