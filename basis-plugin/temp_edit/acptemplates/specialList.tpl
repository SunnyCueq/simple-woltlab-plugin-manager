{include file='header' pageTitle='urlshort.acp.menu.link.special.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}urlshort.acp.menu.link.special.list{/lang}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='UrlList' application='urlshort'}{/link}"
					class="button">{icon size=16 name='link'}
					<span>{lang}wcf.urlshort.special.urlList{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{include file='formError'}

{if $objects|count || $title || $theme || $isActive !== null}
	<form action="{link controller='SpecialList' application='urlshort'}{/link}" method="POST">
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
					<dt><label for="theme">{lang}wcf.urlshort.special.theme{/lang}</label></dt>
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
					<dt><label for="isActive">{lang}wcf.urlshort.special.isActive{/lang}</label></dt>
					<dd>
						<select id="isActive" name="isActive" class="long">
							<option value="">{lang}wcf.global.noSelection{/lang}</option>
							<option value="1" {if $isActive === 1}selected{/if}>{lang}wcf.urlshort.yes{/lang}</option>
							<option value="0" {if $isActive === 0}selected{/if}>{lang}wcf.urlshort.no{/lang}</option>
						</select>
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-3">
					<dt><label for="shortUrl">{lang}wcf.urlshort.special.shortUrl{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="shortUrl" name="shortUrl" value="{$shortUrl}" placeholder="{lang}wcf.urlshort.special.shortUrl.placeholder{/lang}">
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
{pages print=true assign=pagesLinks application='urlshort' controller="SpecialList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&title=$title&theme=$theme&isActive=$isActive&shortUrl=$shortUrl"}
	{/content}
</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table specialList jsObjectActionContainer" data-object-action-class-name="urlshort\data\special\SpecialAction">
			<thead>
				<tr>
					<th></th>
					<th class="columnID columnSpecialID{if $sortField == 'specialID'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='urlshort'}pageNo={#$pageNo}&sortField=specialID&sortOrder={if $sortField == 'specialID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.global.objectID{/lang}
						</a>
					</th>
					<th class="columnTitle columnSpecialTitle{if $sortField == 'title'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='urlshort'}pageNo={#$pageNo}&sortField=title&sortOrder={if $sortField == 'title' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.global.title{/lang}
						</a>
					</th>
					<th>{lang}wcf.urlshort.special.urlHash{/lang}</th>
					<th class="columnText columnSpecialTheme{if $sortField == 'theme'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='urlshort'}pageNo={#$pageNo}&sortField=theme&sortOrder={if $sortField == 'theme' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.urlshort.special.theme{/lang}
						</a>
					</th>
					<th>{lang}wcf.urlshort.codes{/lang}</th>
					<th>{lang}wcf.urlshort.special.timeRange{/lang}</th>
					<th class="columnDigits columnSpecialEndTime{if $sortField == 'endTime'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='urlshort'}pageNo={#$pageNo}&sortField=endTime&sortOrder={if $sortField == 'endTime' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.urlshort.countdownEnd{/lang}
						</a>
					</th>
					<th class="columnDigits columnSpecialIsActive{if $sortField == 'isActive'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='SpecialList' application='urlshort'}pageNo={#$pageNo}&sortField=isActive&sortOrder={if $sortField == 'isActive' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&theme={$theme}&isActive={$isActive}&shortUrl={$shortUrl}{/link}">
							{lang}wcf.urlshort.special.status{/lang}
						</a>
					</th>
				</tr>
			</thead>
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item=object}
					<tr class="jsObjectActionObject" data-object-id="{#$object->specialID}">
						<td class="columnIcon">
							<a href="{link controller='SpecialEdit' id=$object->specialID application='urlshort'}{/link}"
								title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon size=16 name='pencil'}</a>
							{objectAction action="delete" objectTitle=$object->specialID}
							{event name='rowButtons'}
						</td>
					<td class="columnID">{#$object->specialID}</td>
					<td class="columnText">
						{if $object->urlID}
							{assign var="urlHash" value=""}
							{if $urlHashes|isset && $urlHashes[$object->urlID]|isset}
								{assign var="urlHash" value=$urlHashes[$object->urlID]}
							{/if}
							{if $urlHash}
								<a href="/urls/index.php?r/{$urlHash}/" title="{$object->title}">
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
							{if $object->urlID}
								<a href="{link controller='UrlEdit' application='urlshort' id=$object->urlID}{/link}" class="badge badgeInverse">
									{if $urlHashes|isset && $urlHashes[$object->urlID]|isset}
										{$urlHashes[$object->urlID]}
									{else}
										#{#$object->urlID}
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
										<span class="badge badgeInverse">{lang}wcf.urlshort.special.timeRange.start{/lang}: {unsafe:$object->startTime|date}</span>
									{/if}
									{if $object->endTime}
										<span class="badge badgeInverse">{lang}wcf.urlshort.special.timeRange.end{/lang}: {unsafe:$object->endTime|date}</span>
									{/if}
								</div>
							{else}
								<span class="badge">{lang}wcf.urlshort.special.timeRange.notConfigured{/lang}</span>
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
									<span class="badge red">{lang}wcf.urlshort.countdown.expired{/lang}</span>
								{/if}
							{else}
								<span class="badge">{lang}wcf.urlshort.discount.countdown.notConfigured{/lang}</span>
							{/if}
						</td>
						<td class="columnText">
							{if $object->isCurrentlyActive()}
								<span class="badge green">{lang}wcf.urlshort.special.status.active{/lang}</span>
							{elseif $object->endTime && TIME_NOW > $object->endTime}
								<span class="badge red">{lang}wcf.urlshort.special.status.expired{/lang}</span>
							{elseif $object->startTime && TIME_NOW < $object->startTime}
								<span class="badge">{lang}wcf.urlshort.special.status.scheduled{/lang}</span>
							{else}
								<span class="badge">{lang}wcf.urlshort.special.status.inactive{/lang}</span>
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
				<li><a href="{link controller='UrlList' application='urlshort'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}urlshort.acp.menu.link.url.list{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{else}
	<div class="info">
		<dl>
			<dt>{lang}wcf.urlshort.special.noItems{/lang}</dt>
			<dd>
				{lang}wcf.urlshort.special.noItems.howToAdd.title{/lang}
				<small>
					{lang}wcf.urlshort.special.noItems.howToAdd.text{/lang}
					<ul class="nativeList">
						<li>In der <a href="{link controller='UrlList' application='urlshort'}{/link}">URL-Liste</a> über den <a href="{link controller='UrlList' application='urlshort'}{/link}">+-Button</a> in der Special-Spalte</li>
						<li>{lang}wcf.urlshort.special.noItems.howToAdd.viaUrlEdit{/lang}</li>
					</ul>
				</small>
			</dd>
		</dl>
		<dl>
			<dt>{lang}wcf.urlshort.special.noItems.dependencies.title{/lang}</dt>
			<dd>
				<small>{lang}wcf.urlshort.special.noItems.dependencies.text{/lang}</small>
			</dd>
		</dl>
		<dl>
			<dt>{lang}wcf.urlshort.special.noItems.impact.title{/lang}</dt>
			<dd>
				<small>{lang}wcf.urlshort.special.noItems.impact.text{/lang}</small>
			</dd>
		</dl>
	</div>

	<footer class="contentFooter">
		<nav class="contentFooterNavigation">
			<ul>
				<li><a href="{link controller='UrlList' application='urlshort'}{/link}" class="button">{icon size=16 name='list'} <span>{lang}urlshort.acp.menu.link.url.list{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{/if}

<script data-relocate="true">
	require(['Benjaro/Urlshort/DiscountCountdown'], function(DiscountCountdown) {
		DiscountCountdown.initList();
	});
</script>

{include file='footer'}

