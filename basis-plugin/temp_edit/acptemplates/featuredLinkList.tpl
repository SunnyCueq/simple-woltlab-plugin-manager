{include file='header' pageTitle='shrinkr.acp.menu.link.featuredLink.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">
			{lang}shrinkr.acp.menu.link.featuredLink.list{/lang}
			{if $urlHash}<small class="contentTitleBadge">#{$urlHash}</small>{/if}
		</h1>
		<p class="contentHeaderDescription">
			{lang}shrinkr.acp.featuredLink.list.description{/lang}
			{if $urlTarget}<br>{lang}wcf.shrinkr.featuredLink.forHash{/lang}: <code>{$urlTarget}</code>{/if}
		</p>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$linkID}{/link}"
					class="button buttonPrimary">{icon size=16 name='pen-to-square'}
					<span>{lang}wcf.shrinkr.featuredLink.backToUrl{/lang}</span></a></li>
			<li><a href="{link controller='FeaturedLinkAdd' application='shrinkr'}linkID={#$linkID}{/link}"
					class="button">{icon size=16 name='plus'}
					<span>{lang}shrinkr.acp.menu.link.featuredLink.add{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{include file='formError'}

{if $objects|count || $q}
	<form action="{link controller='FeaturedLinkList' application='shrinkr'}linkID={#$linkID}{/link}" method="POST">
		<section class="section">
			<h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

			<div class="row rowColGap formGrid">
				<dl class="col-xs-12 col-md-6">
					<dt></dt>
					<dd>
						<input class="long" type="text" name="q" value="{$q}" placeholder="{lang}wcf.global.title{/lang} / {lang}wcf.shrinkr.featuredLink.url{/lang}">
					</dd>
				</dl>

				<input type="hidden" name="linkID" value="{#$linkID}">
				{if $sortField|isset}<input type="hidden" name="sortField" value="{$sortField}">{/if}
				{if $sortOrder|isset}<input type="hidden" name="sortOrder" value="{$sortOrder}">{/if}

				{event name='filterFields'}
			</div>

			<div class="formSubmit">
				<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s">
				{csrfToken}
			</div>
		</section>
	</form>
{/if}

{hascontent}
<div class="paginationTop">
	{content}
	{pages print=true assign=pagesLinks application='shrinkr' controller="FeaturedLinkList" link="linkID=$linkID&pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&q=$q"}
	{/content}
</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table jsObjectActionContainer" data-object-action-class-name="shrinkr\data\featuredlink\FeaturedLinkAction">
			<thead>
				<tr>
					<th class="columnID columnLinkID{if $sortField == 'linkID'} active {$sortOrder}{/if}" colspan="2">
						<a href="{link controller='FeaturedLinkList' application='shrinkr'}linkID={#$linkID}&pageNo={#$pageNo}&sortField=linkID&sortOrder={if $sortField == 'linkID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.global.objectID{/lang}
						</a>
					</th>
					<th class="columnTitle{if $sortField == 'title'} active {$sortOrder}{/if}">
						<a href="{link controller='FeaturedLinkList' application='shrinkr'}linkID={#$linkID}&pageNo={#$pageNo}&sortField=title&sortOrder={if $sortField == 'title' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.global.title{/lang}
						</a>
					</th>
					<th class="columnText{if $sortField == 'url'} active {$sortOrder}{/if}">
						<a href="{link controller='FeaturedLinkList' application='shrinkr'}linkID={#$linkID}&pageNo={#$pageNo}&sortField=url&sortOrder={if $sortField == 'url' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.shrinkr.featuredLink.url{/lang}
						</a>
					</th>
					<th class="columnDigits columnSortOrder{if $sortField == 'sortOrder'} active {$sortOrder}{/if}">
						<a href="{link controller='FeaturedLinkList' application='shrinkr'}linkID={#$linkID}&pageNo={#$pageNo}&sortField=sortOrder&sortOrder={if $sortField == 'sortOrder' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.shrinkr.featuredLink.sortOrder{/lang}
						</a>
					</th>
				</tr>
			</thead>
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item=object}
					<tr class="jsObjectActionObject" data-object-id="{#$object->linkID}">
						<td class="columnIcon">
							<a href="{link controller='FeaturedLinkEdit' id=$object->linkID application='shrinkr'}{/link}"
								title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon size=16 name='pencil'}</a>
							{objectAction action="delete" objectTitle=$object->getTitle()}
							{event name='rowButtons'}
						</td>
						<td class="columnID">{#$object->linkID}</td>
						<td class="columnTitle">
							{$object->title}
							<small class="badge">{$object->getHost()}</small>
						</td>
						<td class="columnText">
							<a href="{$object->url}" target="_blank" rel="noopener">{$object->url|truncate:80}</a>
						</td>
						<td class="columnDigits">{#$object->sortOrder}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>

	<footer class="contentFooter">
		{hascontent}
			<div class="paginationBottom">
				{content}{$pagesLinks}{/content}
			</div>
		{/hascontent}

		<nav class="contentFooterNavigation">
			<ul>
				<li><a href="{link controller='FeaturedLinkAdd' application='shrinkr'}linkID={#$linkID}{/link}"
						class="button">{icon size=16 name='plus'}
						<span>{lang}shrinkr.acp.menu.link.featuredLink.add{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{else}
	<woltlab-core-notice type="info">{lang}wcf.shrinkr.featuredLink.noItems{/lang}</woltlab-core-notice>
	
	<div class="section">
		<a href="{link controller='ShrinkrLinkList' application='shrinkr'}{/link}" class="button buttonPrimary">
			{icon size=16 name='list'}
			<span>{lang}wcf.shrinkr.featuredLink.goToUrls{/lang}</span>
		</a>
	</div>
{/if}

{include file='footer'}
