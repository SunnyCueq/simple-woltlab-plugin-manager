{include file='header' pageTitle='urlshort.acp.menu.link.theme.add'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}urlshort.acp.menu.link.theme.{$action}{/lang}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='ThemeList' application='urlshort'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}urlshort.acp.menu.link.theme.list{/lang}</span></a></li>
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
					<li><a href="{link controller='ThemeList' application='urlshort'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}urlshort.acp.menu.link.theme.list{/lang}</span></a></li>
					{event name='contentFooterNavigation'}
				{/content}
			</ul>
		</nav>
	</footer>
{/hascontent}

{include file='footer'}

