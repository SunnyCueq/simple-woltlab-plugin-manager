{include file='header' pageTitle='shrinkr.acp.menu.link.theme.edit'}

{unsafe:$form->getHtml()}

{hascontent}
	<footer class="contentFooter">
		<nav class="contentFooterNavigation">
			<ul>
				{content}{event name='contentFooterNavigation'}{/content}
			</ul>
		</nav>
	</footer>
{/hascontent}

{include file='footer'}

