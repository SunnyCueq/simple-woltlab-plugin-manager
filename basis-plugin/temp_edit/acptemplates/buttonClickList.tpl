{include file='header' pageTitle='shrinkr.acp.menu.link.statistics.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}shrinkr.acp.menu.link.statistics.list{/lang}</h1>
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

{* Statistics Section with Tab Menu *}
{if $statistics|isset && $statistics|count}
	<div class="section tabMenuContainer">
		<h2 class="sectionTitle">{lang}wcf.shrinkr.buttonClick.statistics{/lang}</h2>
		
		<nav class="tabMenu">
			<ul>
				<li><a href="#statisticsPeriod">{lang}wcf.shrinkr.buttonClick.statistics.period{/lang}</a></li>
				<li><a href="#statisticsButtonType">{lang}wcf.shrinkr.buttonClick.buttonType{/lang}</a></li>
				{if $statistics.visits|isset}
					<li><a href="#statisticsVisits">{lang}wcf.shrinkr.statistics.visits{/lang}</a></li>
				{/if}
				{if $statistics.referrers|isset && ($statistics.referrers.top|isset || $statistics.referrers.topDomains|isset)}
					<li><a href="#statisticsReferrers">{lang}wcf.shrinkr.statistics.referrer{/lang}</a></li>
				{/if}
				{if $statistics.combined|isset}
					<li><a href="#statisticsCombined">{lang}wcf.shrinkr.statistics.combined{/lang}</a></li>
				{/if}
				{if $statistics.topUrls|isset && $statistics.topUrls|count}
					<li><a href="#statisticsTopUrls">{lang}wcf.shrinkr.buttonClick.statistics.topUrls{/lang}</a></li>
				{/if}
				{event name='statisticsTabs'}
			</ul>
		</nav>
		
		<div id="statisticsPeriod" class="tabMenuContent">
			<div class="section">
				<div class="tabularBox">
					<table class="table">
						<thead>
							<tr>
								<th class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.period{/lang}</th>
								<th class="columnDigits">{lang}wcf.shrinkr.buttonClick.clicks{/lang}</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.total{/lang}</td>
								<td class="columnDigits">{#$statistics.total}</td>
							</tr>
							<tr>
								<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.today{/lang}</td>
								<td class="columnDigits">{if $statistics.today|isset}{#$statistics.today}{else}0{/if}</td>
							</tr>
							<tr>
								<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.yesterday{/lang}</td>
								<td class="columnDigits">{if $statistics.yesterday|isset}{#$statistics.yesterday}{else}0{/if}</td>
							</tr>
							<tr>
								<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.last7{/lang}</td>
								<td class="columnDigits">{if $statistics.last7|isset}{#$statistics.last7}{else}0{/if}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		
		<div id="statisticsButtonType" class="tabMenuContent">
			<div class="section">
				<div class="tabularBox">
					<table class="table">
						<thead>
							<tr>
								<th class="columnTitle">{lang}wcf.shrinkr.buttonClick.buttonType{/lang}</th>
								<th class="columnDigits">{lang}wcf.shrinkr.buttonClick.clicks{/lang}</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.type.forward{/lang}</td>
								<td class="columnDigits">{if $statistics.byType.forward|isset}{#$statistics.byType.forward}{else}0{/if}</td>
							</tr>
							<tr>
								<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.type.featured_link{/lang}</td>
								<td class="columnDigits">{if $statistics.byType.featured_link|isset}{#$statistics.byType.featured_link}{else}0{/if}</td>
							</tr>
							<tr>
								<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.type.custom{/lang}</td>
								<td class="columnDigits">{if $statistics.byType.custom|isset}{#$statistics.byType.custom}{else}0{/if}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		
		{if $statistics.visits|isset}
			<div id="statisticsVisits" class="tabMenuContent">
				<div class="section">
					<div class="tabularBox">
						<table class="table">
							<thead>
								<tr>
									<th class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.period{/lang}</th>
									<th class="columnDigits">{lang}wcf.shrinkr.statistics.visits{/lang}</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="columnTitle">{lang}wcf.shrinkr.statistics.visits.total{/lang}</td>
									<td class="columnDigits">{if $statistics.visits.total|isset}{#$statistics.visits.total}{else}0{/if}</td>
								</tr>
								<tr>
									<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.today{/lang}</td>
									<td class="columnDigits">{if $statistics.visits.today|isset}{#$statistics.visits.today}{else}0{/if}</td>
								</tr>
								<tr>
									<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.yesterday{/lang}</td>
									<td class="columnDigits">{if $statistics.visits.yesterday|isset}{#$statistics.visits.yesterday}{else}0{/if}</td>
								</tr>
								<tr>
									<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.last7{/lang}</td>
									<td class="columnDigits">{if $statistics.visits.last7|isset}{#$statistics.visits.last7}{else}0{/if}</td>
								</tr>
							</tbody>
						</table>
					</div>
					{if $statistics.visits.topUrls|isset && $statistics.visits.topUrls|count}
						<div class="tabularBox" style="margin-top: 1.5rem;">
							<table class="table">
								<thead>
									<tr>
										<th class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.topUrls{/lang}</th>
										<th class="columnDigits">{lang}wcf.shrinkr.statistics.visits{/lang}</th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$statistics.visits.topUrls item=count key=linkID}
										<tr>
											<td class="columnTitle">
												{if $statistics.visits.topUrlHashes|isset && $statistics.visits.topUrlHashes[$linkID]|isset}
													<a href="{link controller='UrlEdit' application='shrinkr' id=$linkID}{/link}">{$statistics.visits.topUrlHashes[$linkID]}</a>
												{else}
													<a href="{link controller='UrlEdit' application='shrinkr' id=$linkID}{/link}">URL #{#$linkID}</a>
												{/if}
											</td>
											<td class="columnDigits">{#$count}</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
					{/if}
				</div>
			</div>
		{/if}
		
		{if $statistics.referrers|isset && ($statistics.referrers.top|isset || $statistics.referrers.topDomains|isset)}
			<div id="statisticsReferrers" class="tabMenuContent">
				<div class="section">
					{if $statistics.referrers.topDomains|isset && $statistics.referrers.topDomains|count}
						<div class="tabularBox">
							<table class="table">
								<thead>
									<tr>
										<th class="columnTitle">{lang}wcf.shrinkr.statistics.referrer.topDomains{/lang}</th>
										<th class="columnDigits">{lang}wcf.shrinkr.statistics.visits{/lang}</th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$statistics.referrers.topDomains item=count key=domain}
										<tr>
											<td class="columnTitle">{$domain}</td>
											<td class="columnDigits">{#$count}</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
					{/if}
					{if $statistics.referrers.top|isset && $statistics.referrers.top|count}
						<div class="tabularBox {if $statistics.referrers.topDomains|isset && $statistics.referrers.topDomains|count}marginTop{/if}">
							<table class="table">
								<thead>
									<tr>
										<th class="columnTitle">{lang}wcf.shrinkr.statistics.referrer.top{/lang}</th>
										<th class="columnDigits">{lang}wcf.shrinkr.statistics.visits{/lang}</th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$statistics.referrers.top item=count key=referrer}
										<tr>
											<td class="columnTitle">
												<a href="{$referrer}" target="_blank" rel="noopener noreferrer" title="{$referrer}">
													{if $referrer|strlen > 60}
														{$referrer|substr:0:60}...
													{else}
														{$referrer}
													{/if}
												</a>
											</td>
											<td class="columnDigits">{#$count}</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
					{/if}
				</div>
			</div>
		{/if}
		
		{if $statistics.combined|isset}
			<div id="statisticsCombined" class="tabMenuContent">
				<div class="section">
					<div class="tabularBox">
						<table class="table">
							<thead>
								<tr>
									<th class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.period{/lang}</th>
									<th class="columnDigits">{lang}wcf.shrinkr.statistics.combined{/lang}</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="columnTitle">{lang}wcf.shrinkr.statistics.combined.total{/lang}</td>
									<td class="columnDigits">{if $statistics.combined.total|isset}{#$statistics.combined.total}{else}0{/if}</td>
								</tr>
								<tr>
									<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.today{/lang}</td>
									<td class="columnDigits">{if $statistics.combined.today|isset}{#$statistics.combined.today}{else}0{/if}</td>
								</tr>
								<tr>
									<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.yesterday{/lang}</td>
									<td class="columnDigits">{if $statistics.combined.yesterday|isset}{#$statistics.combined.yesterday}{else}0{/if}</td>
								</tr>
								<tr>
									<td class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.last7{/lang}</td>
									<td class="columnDigits">{if $statistics.combined.last7|isset}{#$statistics.combined.last7}{else}0{/if}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		{/if}
		
		{if $statistics.topUrls|isset && $statistics.topUrls|count}
			<div id="statisticsTopUrls" class="tabMenuContent">
				<div class="section">
					<div class="tabularBox">
						<table class="table">
							<thead>
								<tr>
									<th class="columnTitle">{lang}wcf.shrinkr.buttonClick.statistics.topUrls{/lang}</th>
									<th class="columnDigits">{lang}wcf.shrinkr.buttonClick.clicks{/lang}</th>
								</tr>
							</thead>
							<tbody>
								{foreach from=$statistics.topUrls item=count key=linkID}
									<tr>
										<td class="columnTitle">
											{if $statistics.topUrlHashes|isset && $statistics.topUrlHashes[$linkID]|isset}
												<a href="{link controller='UrlEdit' application='shrinkr' id=$linkID}{/link}">{$statistics.topUrlHashes[$linkID]}</a>
											{else}
												<a href="{link controller='UrlEdit' application='shrinkr' id=$linkID}{/link}">URL #{#$linkID}</a>
											{/if}
										</td>
										<td class="columnDigits">{#$count}</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		{/if}
		
		{event name='statisticsContents'}
		</div>
	{/if}

	{* Filter Section *}
<form action="{link controller='ButtonClickList' application='shrinkr'}{/link}" method="POST">
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

		<div class="row rowColGap formGrid">
			<dl class="col-xs-12 col-md-3">
				<dt><label for="linkID">{lang}wcf.shrinkr.special.urlHash{/lang}</label></dt>
				<dd>
					<input class="long" type="number" id="linkID" name="linkID" value="{$linkID}" placeholder="{lang}wcf.shrinkr.buttonClick.filter.linkID{/lang}">
				</dd>
			</dl>

			<dl class="col-xs-12 col-md-3">
				<dt><label for="buttonType">{lang}wcf.shrinkr.buttonClick.buttonType{/lang}</label></dt>
				<dd>
					<select id="buttonType" name="buttonType" class="long">
						<option value="">{lang}wcf.global.noSelection{/lang}</option>
						<option value="forward" {if $buttonType == 'forward'}selected{/if}>{lang}wcf.shrinkr.buttonClick.type.forward{/lang}</option>
						<option value="featured_link" {if $buttonType == 'featured_link'}selected{/if}>{lang}wcf.shrinkr.buttonClick.type.featured_link{/lang}</option>
						<option value="custom" {if $buttonType == 'custom'}selected{/if}>{lang}wcf.shrinkr.buttonClick.type.custom{/lang}</option>
					</select>
				</dd>
			</dl>

			<dl class="col-xs-12 col-md-3">
				<dt><label for="dateFrom">{lang}wcf.shrinkr.buttonClick.filter.dateFrom{/lang}</label></dt>
				<dd>
					<input class="long" type="date" id="dateFrom" name="dateFrom" value="{$dateFrom}">
				</dd>
			</dl>

			<dl class="col-xs-12 col-md-3">
				<dt><label for="dateTo">{lang}wcf.shrinkr.buttonClick.filter.dateTo{/lang}</label></dt>
				<dd>
					<input class="long" type="date" id="dateTo" name="dateTo" value="{$dateTo}">
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

{hascontent}
<div class="paginationTop">
	{content}
{pages print=true assign=pagesLinks application='shrinkr' controller="ButtonClickList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&linkID=$linkID&buttonType=$buttonType&dateFrom=$dateFrom&dateTo=$dateTo"}
	{/content}
</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table jsObjectActionContainer" data-object-action-class-name="shrinkr\data\buttonclick\ButtonClickAction">
			<thead>
				<tr>
					<th class="columnIcon"></th>
					<th class="columnID columnClickID{if $sortField == 'clickID'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='ButtonClickList' application='shrinkr'}pageNo={#$pageNo}&sortField=clickID&sortOrder={if $sortField == 'clickID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&linkID={$linkID}&buttonType={$buttonType}&dateFrom={$dateFrom}&dateTo={$dateTo}{/link}">
							{lang}wcf.global.objectID{/lang}
						</a>
					</th>
					<th class="columnTitle columnUrlHash{if $sortField == 'linkID'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='ButtonClickList' application='shrinkr'}pageNo={#$pageNo}&sortField=linkID&sortOrder={if $sortField == 'linkID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&linkID={$linkID}&buttonType={$buttonType}&dateFrom={$dateFrom}&dateTo={$dateTo}{/link}">
							{lang}wcf.shrinkr.special.urlHash{/lang}
						</a>
					</th>
					<th class="columnText columnButtonType{if $sortField == 'buttonType'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='ButtonClickList' application='shrinkr'}pageNo={#$pageNo}&sortField=buttonType&sortOrder={if $sortField == 'buttonType' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&linkID={$linkID}&buttonType={$buttonType}&dateFrom={$dateFrom}&dateTo={$dateTo}{/link}">
							{lang}wcf.shrinkr.buttonClick.buttonType{/lang}
						</a>
					</th>
					<th class="columnDigits">{lang}wcf.shrinkr.buttonClick.buttonID{/lang}</th>
					<th class="columnDigits columnClickTime{if $sortField == 'clickTime'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='ButtonClickList' application='shrinkr'}pageNo={#$pageNo}&sortField=clickTime&sortOrder={if $sortField == 'clickTime' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&linkID={$linkID}&buttonType={$buttonType}&dateFrom={$dateFrom}&dateTo={$dateTo}{/link}">
							{lang}wcf.shrinkr.buttonClick.clickTime{/lang}
						</a>
					</th>
					<th class="columnText">{lang}wcf.shrinkr.buttonClick.user{/lang}</th>
				</tr>
			</thead>
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item=object}
					<tr class="jsObjectActionObject" data-object-id="{#$object->clickID}">
						<td class="columnIcon">
							{objectAction action="delete" objectTitle=$object->clickID}
							{event name='rowButtons'}
						</td>
						<td class="columnID">{#$object->clickID}</td>
						<td class="columnText">
							{if $object->linkID}
								<a href="{link controller='UrlEdit' application='shrinkr' id=$object->linkID}{/link}" class="badge badgeInverse">
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
							<span class="badge">{lang}wcf.shrinkr.buttonClick.type.{$object->buttonType}{/lang}</span>
						</td>
						<td class="columnDigits">
							{if $object->linkID|isset && $object->linkID && ($object->buttonType == 'featured_link' || $object->buttonType == 'custom') && $object->linkID}
								<a href="{link controller='UrlEdit' application='shrinkr' id=$object->linkID}{/link}" title="{lang}wcf.shrinkr.buttonClick.buttonID.tooltip{/lang}" class="jsTooltip">#{#$object->linkID}</a>
							{elseif $object->linkID|isset && $object->linkID}
								#{#$object->linkID}
							{else}
								-
							{/if}
						</td>
						<td class="columnDigits">
							{unsafe:$object->clickTime|date}
						</td>
						<td class="columnText">
							{if $object->userID}
								{lang}wcf.shrinkr.buttonClick.user.loggedIn{/lang} (#{$object->userID})
							{elseif $object->sessionID}
								{lang}wcf.shrinkr.buttonClick.user.guest{/lang}
							{else}
								-
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
	</footer>
{else}
	<p class="info">{lang}wcf.shrinkr.buttonClick.noItems{/lang}</p>
{/if}

{include file='footer'}

