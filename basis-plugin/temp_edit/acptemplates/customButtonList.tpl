{include file='header' pageTitle='shrinkr.acp.menu.link.customButton.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">
			{lang}shrinkr.acp.menu.link.customButton.list{/lang}
			{if $urlHash}<small class="contentTitleBadge">#{$urlHash}</small>{/if}
		</h1>
		<p class="contentHeaderDescription">
			{lang}shrinkr.acp.customButton.list.description{/lang}
			{if $urlTarget}<br>{lang}wcf.shrinkr.customButton.forHash{/lang}: <code>{$urlTarget}</code>{/if}
		</p>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$linkID}{/link}"
					class="button buttonPrimary">{icon size=16 name='pen-to-square'}
					<span>{lang}wcf.shrinkr.customButton.backToUrl{/lang}</span></a></li>
			<li><a href="{link controller='CustomButtonAdd' application='shrinkr'}linkID={#$linkID}{/link}"
					class="button">{icon size=16 name='plus'}
					<span>{lang}shrinkr.acp.menu.link.customButton.add{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{include file='formError'}

{if $objects|count || $q}
	<form action="{link controller='CustomButtonList' application='shrinkr'}linkID={#$linkID}{/link}" method="POST">
		<section class="section">
			<h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

			<div class="row rowColGap formGrid">
				<dl class="col-xs-12 col-md-6">
					<dt></dt>
					<dd>
						<input class="long" type="text" name="q" value="{$q}" placeholder="{lang}wcf.global.title{/lang} / {lang}wcf.shrinkr.customButton.targetUrl{/lang}">
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
	{pages print=true assign=pagesLinks application='shrinkr' controller="CustomButtonList" link="linkID=$linkID&pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&q=$q"}
	{/content}
</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table jsObjectActionContainer" data-object-action-class-name="shrinkr\data\custombutton\CustomButtonAction">
			<thead>
				<tr>
					<th class="columnID columnCustomButtonID{if $sortField == 'customButtonID'} active {$sortOrder}{/if}" colspan="2">
						<a href="{link controller='CustomButtonList' application='shrinkr'}linkID={#$linkID}&pageNo={#$pageNo}&sortField=customButtonID&sortOrder={if $sortField == 'customButtonID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.global.objectID{/lang}
						</a>
					</th>
					<th class="columnTitle{if $sortField == 'title'} active {$sortOrder}{/if}">
						<a href="{link controller='CustomButtonList' application='shrinkr'}linkID={#$linkID}&pageNo={#$pageNo}&sortField=title&sortOrder={if $sortField == 'title' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.global.title{/lang}
						</a>
					</th>
					<th class="columnText{if $sortField == 'targetUrl'} active {$sortOrder}{/if}">
						<a href="{link controller='CustomButtonList' application='shrinkr'}linkID={#$linkID}&pageNo={#$pageNo}&sortField=targetUrl&sortOrder={if $sortField == 'targetUrl' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.shrinkr.customButton.targetUrl{/lang}
						</a>
					</th>
					<th class="columnDigits columnSortOrder{if $sortField == 'sortOrder'} active {$sortOrder}{/if}">
						<a href="{link controller='CustomButtonList' application='shrinkr'}linkID={#$linkID}&pageNo={#$pageNo}&sortField=sortOrder&sortOrder={if $sortField == 'sortOrder' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.shrinkr.customButton.sortOrder{/lang}
						</a>
					</th>
				</tr>
			</thead>
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item=object}
					<tr class="jsObjectActionObject" data-object-id="{#$object->customButtonID}">
						<td class="columnIcon">
							<a href="{link controller='CustomButtonEdit' id=$object->customButtonID application='shrinkr'}{/link}"
								title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon size=16 name='pencil'}</a>
							{objectAction action="delete" objectTitle=$object->getTitle()}
							{event name='rowButtons'}
						</td>
						<td class="columnID">{#$object->customButtonID}</td>
						<td class="columnTitle">
							{$object->title}
						</td>
						<td class="columnText">
							<a href="{$object->targetUrl}" target="_blank" rel="noopener">{$object->targetUrl|truncate:80}</a>
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
				<li><a href="{link controller='CustomButtonAdd' application='shrinkr'}linkID={#$linkID}{/link}"
						class="button">{icon size=16 name='plus'}
						<span>{lang}shrinkr.acp.menu.link.customButton.add{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{else}
	<woltlab-core-notice type="info">{lang}wcf.shrinkr.customButton.noItems{/lang}</woltlab-core-notice>
	
	<div class="section">
		<a href="{link controller='ShrinkrLinkList' application='shrinkr'}{/link}" class="button buttonPrimary">
			{icon size=16 name='list'}
			<span>{lang}wcf.shrinkr.customButton.goToUrls{/lang}</span>
		</a>
	</div>
{/if}

{include file='footer'}

