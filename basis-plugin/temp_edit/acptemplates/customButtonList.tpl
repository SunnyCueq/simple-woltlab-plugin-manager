{include file='header' pageTitle='urlshort.acp.menu.link.customButton.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">
			{lang}urlshort.acp.menu.link.customButton.list{/lang}
			{if $urlHash}<small class="contentTitleBadge">#{$urlHash}</small>{/if}
		</h1>
		<p class="contentHeaderDescription">
			{lang}urlshort.acp.customButton.list.description{/lang}
			{if $urlTarget}<br>{lang}wcf.urlshort.customButton.forHash{/lang}: <code>{$urlTarget}</code>{/if}
		</p>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='UrlEdit' application='urlshort' id=$urlID}{/link}"
					class="button buttonPrimary">{icon size=16 name='pen-to-square'}
					<span>{lang}wcf.urlshort.customButton.backToUrl{/lang}</span></a></li>
			<li><a href="{link controller='CustomButtonAdd' application='urlshort'}urlID={#$urlID}{/link}"
					class="button">{icon size=16 name='plus'}
					<span>{lang}urlshort.acp.menu.link.customButton.add{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{include file='formError'}

{if $objects|count || $q}
	<form action="{link controller='CustomButtonList' application='urlshort'}urlID={#$urlID}{/link}" method="POST">
		<section class="section">
			<h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

			<div class="row rowColGap formGrid">
				<dl class="col-xs-12 col-md-6">
					<dt></dt>
					<dd>
						<input class="long" type="text" name="q" value="{$q}" placeholder="{lang}wcf.global.title{/lang} / {lang}wcf.urlshort.customButton.targetUrl{/lang}">
					</dd>
				</dl>

				<input type="hidden" name="urlID" value="{#$urlID}">
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
	{pages print=true assign=pagesLinks application='urlshort' controller="CustomButtonList" link="urlID=$urlID&pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&q=$q"}
	{/content}
</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table jsObjectActionContainer" data-object-action-class-name="urlshort\data\custombutton\CustomButtonAction">
			<thead>
				<tr>
					<th class="columnID columnCustomButtonID{if $sortField == 'customButtonID'} active {$sortOrder}{/if}" colspan="2">
						<a href="{link controller='CustomButtonList' application='urlshort'}urlID={#$urlID}&pageNo={#$pageNo}&sortField=customButtonID&sortOrder={if $sortField == 'customButtonID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.global.objectID{/lang}
						</a>
					</th>
					<th class="columnTitle{if $sortField == 'title'} active {$sortOrder}{/if}">
						<a href="{link controller='CustomButtonList' application='urlshort'}urlID={#$urlID}&pageNo={#$pageNo}&sortField=title&sortOrder={if $sortField == 'title' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.global.title{/lang}
						</a>
					</th>
					<th class="columnText{if $sortField == 'targetUrl'} active {$sortOrder}{/if}">
						<a href="{link controller='CustomButtonList' application='urlshort'}urlID={#$urlID}&pageNo={#$pageNo}&sortField=targetUrl&sortOrder={if $sortField == 'targetUrl' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.urlshort.customButton.targetUrl{/lang}
						</a>
					</th>
					<th class="columnDigits columnSortOrder{if $sortField == 'sortOrder'} active {$sortOrder}{/if}">
						<a href="{link controller='CustomButtonList' application='urlshort'}urlID={#$urlID}&pageNo={#$pageNo}&sortField=sortOrder&sortOrder={if $sortField == 'sortOrder' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">
							{lang}wcf.urlshort.customButton.sortOrder{/lang}
						</a>
					</th>
				</tr>
			</thead>
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item=object}
					<tr class="jsObjectActionObject" data-object-id="{#$object->customButtonID}">
						<td class="columnIcon">
							<a href="{link controller='CustomButtonEdit' id=$object->customButtonID application='urlshort'}{/link}"
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
				<li><a href="{link controller='CustomButtonAdd' application='urlshort'}urlID={#$urlID}{/link}"
						class="button">{icon size=16 name='plus'}
						<span>{lang}urlshort.acp.menu.link.customButton.add{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{else}
	<woltlab-core-notice type="info">{lang}wcf.urlshort.customButton.noItems{/lang}</woltlab-core-notice>
	
	<div class="section">
		<a href="{link controller='UrlList' application='urlshort'}{/link}" class="button buttonPrimary">
			{icon size=16 name='list'}
			<span>{lang}wcf.urlshort.customButton.goToUrls{/lang}</span>
		</a>
	</div>
{/if}

{include file='footer'}

