{include file='header' pageTitle='shrinkr.acp.menu.link.special.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}shrinkr.acp.menu.link.special.list{/lang}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='ShrinkrLinkList' application='shrinkr'}{/link}"
					class="button">{icon size=16 name='link'}
					<span>{lang}wcf.shrinkr.special.urlList{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{include file='formError'}

{if $objects|count || $title || $theme || $isActive !== null}
	<form action="{link controller='SpecialList' application='shrinkr'}{/link}" method="POST">
		<section class="section">
			<h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

			<div class="row rowColGap formGrid">
				<dl class="col-xs-12 col-md-3">
					<dt><label for="title">{lang}wcf.global.title{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="title" name="title" value="{$title}" placeholder="{lang}wcf.global.title{/lang}">
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-3">
					<dt><label for="theme">{lang}wcf.shrinkr.special.theme{/lang}</label></dt>
					<dd>
						<select id="theme" name="theme" class="long">
							<option value="">{lang}wcf.global.noSelection{/lang}</option>
							{if $themes|isset && $themes|count}
								{foreach from=$themes item=themeData key=themeKey}
									<option value="{$themeKey}" {if $theme == $themeKey}selected{/if}>{$themeData.name}</option>
								{/foreach}
							{/if}
						</select>
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-3">
					<dt><label for="isActive">{lang}wcf.shrinkr.special.isActive{/lang}</label></dt>
					<dd>
						<select id="isActive" name="isActive" class="long">
							<option value="">{lang}wcf.global.noSelection{/lang}</option>
							<option value="1" {if $isActive === 1}selected{/if}>{lang}wcf.shrinkr.yes{/lang}</option>
							<option value="0" {if $isActive === 0}selected{/if}>{lang}wcf.shrinkr.no{/lang}</option>
						</select>
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-3">
					<dt><label for="shortUrl">{lang}wcf.shrinkr.special.shortUrl{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="shortUrl" name="shortUrl" value="{$shortUrl}" placeholder="{lang}wcf.shrinkr.special.shortUrl.placeholder{/lang}">
					</dd>
				</dl>

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
{pages print=true assign=pagesLinks application='shrinkr' controller="SpecialList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&title=$title&theme=$theme&isActive=$isActive&shortUrl=$shortUrl"}
	{/content}
</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table specialList jsObjectActionContainer" data-object-action-class-name="shrinkr\data\special\SpecialAction">
			<thead>
				<tr>
					<th></th>
					<th class="columnID columnSpecialID{if $sortField == 'specialID'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='shrinkr'}pageNo={#$pageNo}&sortField=specialID&sortOrder={if $sortField == 'specialID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.global.objectID{/lang}
						</a>
					</th>
					<th class="columnTitle columnSpecialTitle{if $sortField == 'title'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='shrinkr'}pageNo={#$pageNo}&sortField=title&sortOrder={if $sortField == 'title' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.global.title{/lang}
						</a>
					</th>
					<th>{lang}wcf.shrinkr.special.urlHash{/lang}</th>
					<th class="columnText columnSpecialTheme{if $sortField == 'theme'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='shrinkr'}pageNo={#$pageNo}&sortField=theme&sortOrder={if $sortField == 'theme' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.shrinkr.special.theme{/lang}
						</a>
					</th>
					<th>{lang}wcf.shrinkr.codes{/lang}</th>
					<th>{lang}wcf.shrinkr.special.timeRange{/lang}</th>
					<th class="columnDigits columnSpecialEndTime{if $sortField == 'endTime'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='shrinkr'}pageNo={#$pageNo}&sortField=endTime&sortOrder={if $sortField == 'endTime' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.shrinkr.countdownEnd{/lang}
						</a>
					</th>
					<th>{lang}wcf.shrinkr.special.status.expired{/lang}</th>
					<th class="columnDigits columnSpecialIsActive{if $sortField == 'isActive'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='shrinkr'}pageNo={#$pageNo}&sortField=isActive&sortOrder={if $sortField == 'isActive' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.shrinkr.special.status{/lang}
						</a>
					</th>
				</tr>
			</thead>
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item=object}
					<tr class="jsObjectActionObject" data-object-id="{#$object->specialID}">
						<td class="columnIcon">
							{assign var='specialIsDisabled' value=true}
							{if $object->isActive}
								{assign var='specialIsDisabled' value=false}
							{/if}
							{objectAction action="toggle" isDisabled=$specialIsDisabled disableTitle='wcf.shrinkr.special.isActive.yes' enableTitle='wcf.shrinkr.special.isActive.no'}
							<a href="{link controller='SpecialEdit' id=$object->specialID application='shrinkr'}{/link}"
								title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon size=16 name='pencil'}</a>
							{objectAction action="delete" objectTitle=$object->specialID}
							{event name='rowButtons'}
						</td>
					<td class="columnID">{#$object->specialID}</td>
					<td class="columnText">
						{if $object->linkID}
							{assign var="urlHash" value=""}
							{if $urlHashes|isset && $urlHashes[$object->linkID]|isset}
								{assign var="urlHash" value=$urlHashes[$object->linkID]}
							{/if}
							{if $urlHash}
								<a href="/shrinkr/index.php?r/{$urlHash}/" title="{$object->title}">
									{$object->title}
								</a>
							{else}
								{$object->title}
							{/if}
						{else}
							{$object->title}
						{/if}
					</td>
						<td class="columnText">
							{if $object->linkID}
								<a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$object->linkID}{/link}" class="badge badgeInverse">
									{if $urlHashes|isset && $urlHashes[$object->linkID]|isset}
										{$urlHashes[$object->linkID]}
									{else}
										#{#$object->linkID}
									{/if}
								</a>
							{else}
								-
							{/if}
						</td>
						<td class="columnText">
							{if $themes|isset && $themes[$object->theme]|isset && $themes[$object->theme].name|isset}
								{$themes[$object->theme].name}
							{else}
								{$object->theme}
							{/if}
						</td>
						<td class="columnText">
							{assign var="codesArray" value=$object->getCodes()}
							{if $codesArray|count}
								{foreach from=$codesArray item=code}
									{if $code|trim}
										<span class="badge">{$code|trim}</span>
									{/if}
								{/foreach}
							{else}
								-
							{/if}
						</td>
						<td class="columnText">
							{if $object->startTime || $object->endTime}
								<div class="timeRange">
									{if $object->startTime}
										<span class="badge badgeInverse">{lang}wcf.shrinkr.special.timeRange.start{/lang}: {unsafe:$object->startTime|date}</span>
									{/if}
									{if $object->endTime}
										<span class="badge badgeInverse">{lang}wcf.shrinkr.special.timeRange.end{/lang}: {unsafe:$object->endTime|date}</span>
									{/if}
								</div>
							{else}
								<span class="badge">{lang}wcf.shrinkr.special.timeRange.notConfigured{/lang}</span>
							{/if}
						</td>

						<td class="columnText">
							{if $object->endTime && $object->endTime > 0}
								{assign var="countdownEnd" value=$object->endTime}
								{assign var="now" value=TIME_NOW}
								{assign var="remainingSeconds" value=$countdownEnd - $now}
								{if $remainingSeconds > 0 && $object->isCurrentlyActive()}
									<span class="badge green discount-countdown" data-end-time="{unsafe:$countdownEnd}" data-special-id="{#$object->specialID}">
										<span class="countdown-display">{* Wird von JavaScript gefüllt *}</span>
									</span>
								{else}
									<span class="badge red">{lang}wcf.shrinkr.countdown.expired{/lang}</span>
								{/if}
							{else}
								<span class="badge">{lang}wcf.shrinkr.discount.countdown.notConfigured{/lang}</span>
							{/if}
						</td>
						<td class="columnText">
							{if $object->endTime && $object->endTime > 0 && TIME_NOW > $object->endTime}
								<span class="badge red">{lang}wcf.shrinkr.special.status.expired{/lang}</span>
							{else}
								-
							{/if}
						</td>
						<td class="columnText">
							{if $object->isActive}
								<span class="badge green">{lang}wcf.shrinkr.special.status.active{/lang}</span>
							{else}
								<span class="badge red">{lang}wcf.shrinkr.special.status.inactive{/lang}</span>
							{/if}
						</td>
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
				<li><a href="{link controller='ShrinkrLinkList' application='shrinkr'}{/link}" class="button">{icon size=16 name='link'} <span>{lang}wcf.shrinkr.special.urlList{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{else}
	<div class="info">
		<dl>
			<dt>{lang}wcf.shrinkr.special.noItems{/lang}</dt>
			<dd>
				{lang}wcf.shrinkr.special.noItems.howToAdd.title{/lang}
				<small>
					{lang}wcf.shrinkr.special.noItems.howToAdd.text{/lang}
					<ul class="nativeList">
						<li>In der <a href="{link controller='ShrinkrLinkList' application='shrinkr'}{/link}">URL-Liste</a> über den <a href="{link controller='ShrinkrLinkList' application='shrinkr'}{/link}">+-Button</a> in der Special-Spalte</li>
						<li>{lang}wcf.shrinkr.special.noItems.howToAdd.viaLinkEdit{/lang}</li>
					</ul>
				</small>
			</dd>
		</dl>
		<dl>
			<dt>{lang}wcf.shrinkr.special.noItems.dependencies.title{/lang}</dt>
			<dd>
				<small>{lang}wcf.shrinkr.special.noItems.dependencies.text{/lang}</small>
			</dd>
		</dl>
		<dl>
			<dt>{lang}wcf.shrinkr.special.noItems.impact.title{/lang}</dt>
			<dd>
				<small>{lang}wcf.shrinkr.special.noItems.impact.text{/lang}</small>
			</dd>
		</dl>
	</div>

	<footer class="contentFooter">
		<nav class="contentFooterNavigation">
			<ul>
				<li><a href="{link controller='ShrinkrLinkList' application='shrinkr'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.link.list{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{/if}

<script data-relocate="true">
	require(['Shrinkr/DiscountCountdown'], function(DiscountCountdown) {
		DiscountCountdown.initList();
	});
</script>

{include file='footer'}

