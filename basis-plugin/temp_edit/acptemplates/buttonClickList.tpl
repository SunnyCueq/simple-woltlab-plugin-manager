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

{* Filter Form *}
<form method="get" action="{link controller='ButtonClickList' application='shrinkr'}{/link}">
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>
		
		<div class="row rowColGap formGrid">
			<dl class="col-xs-12 col-md-4">
				<dt><label for="linkID">{lang}wcf.shrinkr.url{/lang}</label></dt>
				<dd>
					<input type="number" id="linkID" name="linkID" value="{if $linkID}{$linkID}{/if}" placeholder="{lang}wcf.global.objectID{/lang}" class="long">
				</dd>
			</dl>
			
			<dl class="col-xs-12 col-md-4">
				<dt><label for="buttonType">{lang}wcf.shrinkr.buttonClick.buttonType{/lang}</label></dt>
				<dd>
					<select id="buttonType" name="buttonType" class="long">
						<option value="">{lang}wcf.global.all{/lang}</option>
						<option value="forward"{if $buttonType == 'forward'} selected{/if}>{lang}wcf.shrinkr.buttonClick.type.forward{/lang}</option>
						<option value="featured_link"{if $buttonType == 'featured_link'} selected{/if}>{lang}wcf.shrinkr.buttonClick.type.featured_link{/lang}</option>
						<option value="custom"{if $buttonType == 'custom'} selected{/if}>{lang}wcf.shrinkr.buttonClick.type.custom{/lang}</option>
					</select>
				</dd>
			</dl>
			
			<dl class="col-xs-12 col-md-4">
				<dt><label for="dateFrom">{lang}wcf.date.period.start{/lang}</label></dt>
				<dd>
					<input type="date" id="dateFrom" name="dateFrom" value="{if $dateFrom}{$dateFrom}{/if}" class="long">
				</dd>
			</dl>
			
			<dl class="col-xs-12 col-md-4">
				<dt><label for="dateTo">{lang}wcf.date.period.end{/lang}</label></dt>
				<dd>
					<input type="date" id="dateTo" name="dateTo" value="{if $dateTo}{$dateTo}{/if}" class="long">
				</dd>
			</dl>
			
			{event name='filterFields'}
		</div>
		
		<div class="formSubmit">
			<input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s">
			{if $linkID || $buttonType || $dateFrom || $dateTo}
				<a href="{link controller='ButtonClickList' application='shrinkr'}{/link}" class="button">{lang}wcf.global.button.reset{/lang}</a>
			{/if}
		</div>
	</section>
</form>

{* Statistics Overview Cards *}
{if $statistics|isset}
	<div class="section">
		<h2 class="sectionTitle">{lang}wcf.shrinkr.buttonClick.statistics.overview{/lang}</h2>
		<div class="statisticsOverviewGrid">
			<dl class="dataList plain statisticsCard">
				<dt>{lang}wcf.shrinkr.buttonClick.statistics.total{/lang}</dt>
				<dd>
					<span class="number">{if $statistics.total|isset}{#$statistics.total}{else}0{/if}</span>
				</dd>
			</dl>
			<dl class="dataList plain statisticsCard">
				<dt>{lang}wcf.shrinkr.buttonClick.statistics.today{/lang}</dt>
				<dd>
					<span class="number">{if $statistics.today|isset}{#$statistics.today}{else}0{/if}</span>
				</dd>
			</dl>
			<dl class="dataList plain statisticsCard">
				<dt>{lang}wcf.shrinkr.buttonClick.statistics.yesterday{/lang}</dt>
				<dd>
					<span class="number">{if $statistics.yesterday|isset}{#$statistics.yesterday}{else}0{/if}</span>
				</dd>
			</dl>
			<dl class="dataList plain statisticsCard">
				<dt>{lang}wcf.shrinkr.buttonClick.statistics.last7{/lang}</dt>
				<dd>
					<span class="number">{if $statistics.last7|isset}{#$statistics.last7}{else}0{/if}</span>
				</dd>
			</dl>
		</div>
	</div>
{/if}

{* Statistics Section with Tab Menu *}
{if $statistics|isset && $statistics|count}
	<div class="section tabMenuContainer">
		<h2 class="sectionTitle">{lang}wcf.shrinkr.buttonClick.statistics{/lang}</h2>
		
		<nav class="tabMenu">
			<ul>
				<li><a href="#statisticsPeriod">{lang}wcf.shrinkr.buttonClick.statistics.period{/lang}</a></li>
				<li><a href="#statisticsTimeSeries">{lang}wcf.shrinkr.statistics.timeSeries{/lang}</a></li>
				<li><a href="#statisticsButtonType">{lang}wcf.shrinkr.buttonClick.buttonType{/lang}</a></li>
				<li><a href="#statisticsAnalytics">{lang}wcf.shrinkr.statistics.analytics{/lang}</a></li>
				<li><a href="#statisticsCountries">{lang}wcf.shrinkr.statistics.countries{/lang}</a></li>
				<li><a href="#statisticsVisits">{lang}wcf.shrinkr.statistics.visits{/lang}</a></li>
				<li><a href="#statisticsReferrers">{lang}wcf.shrinkr.statistics.referrer{/lang}</a></li>
				<li><a href="#statisticsCombined">{lang}wcf.shrinkr.statistics.combined{/lang}</a></li>
				<li><a href="#statisticsTopUrls">{lang}wcf.shrinkr.buttonClick.statistics.topUrls{/lang}</a></li>
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
		
		<div id="statisticsTimeSeries" class="tabMenuContent">
			{if $statistics.timeSeries|isset && ($statistics.timeSeries.visits|isset || $statistics.timeSeries.clicks|isset)}
				<div class="section">
					<div class="statisticsHeaderContainer">
						<h3 class="sectionTitle">{lang}wcf.shrinkr.statistics.timeSeries{/lang}</h3>
						<div class="statisticsDropdownContainer">
							<label for="timeGranularitySelect">{lang}wcf.shrinkr.statistics.timeGranularity{/lang}:</label>
							<select id="timeGranularitySelect" class="select" onchange="window.location.href = window.location.pathname + window.location.search.replace(/[&?]timeGranularity=[^&]*/, '') + (window.location.search ? '&' : '?') + 'timeGranularity=' + this.value">
								<option value="day"{if $timeGranularity == 'day'} selected{/if}>{lang}wcf.shrinkr.statistics.timeGranularity.day{/lang}</option>
								<option value="hour"{if $timeGranularity == 'hour'} selected{/if}>{lang}wcf.shrinkr.statistics.timeGranularity.hour{/lang}</option>
							</select>
						</div>
					</div>
					<div id="timeSeriesChart" class="statisticsChartContainer"></div>
					<script data-relocate="true">
						// Configure require.js path for D3.js (Woltlab pattern)
						require.config({
							paths: {
								"d3": "shrinkr/js/3rdParty/d3/d3"
							}
						});
						console.log('DEBUG D3: require.js config applied, paths:', require.config().paths);
						require(["Shrinkr/Acp/Ui/Statistics/TimeSeriesChart"], (TimeSeriesChart) => {
							var visitsData = {if $statistics.timeSeries.visits|isset}{@$statistics.timeSeries.visits|json}{else}[]{/if};
							var clicksData = {if $statistics.timeSeries.clicks|isset}{@$statistics.timeSeries.clicks|json}{else}[]{/if};
							var timeGranularity = '{if $timeGranularity|isset}{$timeGranularity}{else}day{/if}';
							
							TimeSeriesChart.init({
								elementId: "timeSeriesChart",
								visitsData: visitsData,
								clicksData: clicksData,
								timeGranularity: timeGranularity
							});
						});
					</script>
				</div>
			{else}
				<div class="section">
					<p class="info">{lang}wcf.shrinkr.statistics.noData.timeSeries{/lang}</p>
				</div>
			{/if}
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
		
		<div id="statisticsAnalytics" class="tabMenuContent">
			<div class="section">
				{if $statistics.analytics.devices|isset && $statistics.analytics.devices|count}
					<div class="tabularBox">
						<h3 class="sectionTitle">{lang}wcf.shrinkr.statistics.devices{/lang}</h3>
						<div class="statisticsFlexContainer">
							<div>
								<div id="deviceChart" class="statisticsChartContainerSmall"></div>
							</div>
							<div>
								<table class="table">
									<thead>
										<tr>
											<th class="columnTitle">{lang}wcf.shrinkr.statistics.device{/lang}</th>
											<th class="columnDigits">{lang}wcf.shrinkr.statistics.visits{/lang}</th>
										</tr>
									</thead>
									<tbody>
										{foreach from=$statistics.analytics.devices item=deviceData}
											<tr>
												<td class="columnTitle">{$deviceData.label}</td>
												<td class="columnDigits">{#$deviceData.value}</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							</div>
						</div>
						<script data-relocate="true">
							require(["Shrinkr/Acp/Ui/Statistics/PieChart"], (PieChart) => {
								var deviceData = {if $statistics.analytics.devices|isset}{@$statistics.analytics.devices|json}{else}[]{/if};
								
								PieChart.init({
									elementId: "deviceChart",
									data: deviceData
								});
							});
						</script>
					</div>
				{/if}
				
				{if $statistics.analytics.browsers|isset && $statistics.analytics.browsers|count}
					<div class="tabularBox {if $statistics.analytics.devices|isset && $statistics.analytics.devices|count}marginTop{/if}">
						<h3 class="sectionTitle">{lang}wcf.shrinkr.statistics.browsers{/lang}</h3>
						<div class="statisticsFlexContainer">
							<div>
								<div id="browserChart" class="statisticsChartContainerSmall"></div>
							</div>
							<div>
								<table class="table">
									<thead>
										<tr>
											<th class="columnTitle">{lang}wcf.shrinkr.statistics.browser{/lang}</th>
											<th class="columnDigits">{lang}wcf.shrinkr.statistics.visits{/lang}</th>
										</tr>
									</thead>
									<tbody>
										{foreach from=$statistics.analytics.browsers item=browserData}
											<tr>
												<td class="columnTitle">{$browserData.label}</td>
												<td class="columnDigits">{#$browserData.value}</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							</div>
						</div>
						<script data-relocate="true">
							require(["Shrinkr/Acp/Ui/Statistics/PieChart"], (PieChart) => {
								var browserData = {if $statistics.analytics.browsers|isset}{@$statistics.analytics.browsers|json}{else}[]{/if};
								
								PieChart.init({
									elementId: "browserChart",
									data: browserData
								});
							});
						</script>
					</div>
				{/if}
				
				{if $statistics.analytics.os|isset && $statistics.analytics.os|count}
					<div class="tabularBox {if ($statistics.analytics.devices|isset && $statistics.analytics.devices|count) || ($statistics.analytics.browsers|isset && $statistics.analytics.browsers|count)}marginTop{/if}">
						<h3 class="sectionTitle">{lang}wcf.shrinkr.statistics.operatingSystems{/lang}</h3>
						<div class="tabularBox">
							<table class="table">
								<thead>
									<tr>
										<th class="columnTitle">{lang}wcf.shrinkr.statistics.os{/lang}</th>
										<th class="columnDigits">{lang}wcf.shrinkr.statistics.visits{/lang}</th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$statistics.analytics.os item=count key=os}
										<tr>
											<td class="columnTitle">{$os}</td>
											<td class="columnDigits">{#$count}</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
					</div>
				{/if}
				
				{if !($statistics.analytics.devices|isset && $statistics.analytics.devices|count) && !($statistics.analytics.browsers|isset && $statistics.analytics.browsers|count) && !($statistics.analytics.os|isset && $statistics.analytics.os|count)}
					<p class="info">{lang}wcf.shrinkr.statistics.noData.analytics{/lang}</p>
				{/if}
			</div>
		</div>
		
		<div id="statisticsCountries" class="tabMenuContent">
			{if $statistics.analytics|isset && $statistics.analytics.countries|isset && $statistics.analytics.countries|count}
				<div class="section">
					<h3 class="sectionTitle">{lang}wcf.shrinkr.statistics.countries{/lang}</h3>
					<div class="statisticsFlexContainer">
						<div class="statisticsFlexContainerLarge">
							<div id="countryMap" class="statisticsMapContainer"></div>
							<script data-relocate="true">
								require(["Shrinkr/Acp/Ui/Statistics/CountryMap"], (CountryMap) => {
									var countryData = {
										{foreach from=$statistics.analytics.countries item=count key=country name=countryLoop}
											'{$country|strtolower}': {#$count}{if !$countryLoop@last},{/if}
										{/foreach}
									};
									
									CountryMap.init({
										elementId: "countryMap",
										countryData: countryData
									});
								});
							</script>
						</div>
						<div>
							<div class="tabularBox">
								<table class="table">
									<thead>
										<tr>
											<th class="columnTitle">{lang}wcf.shrinkr.statistics.country{/lang}</th>
											<th class="columnDigits">{lang}wcf.shrinkr.statistics.visits{/lang}</th>
										</tr>
									</thead>
									<tbody>
										{foreach from=$statistics.analytics.countries item=count key=country}
											<tr>
												<td class="columnTitle">{$country}</td>
												<td class="columnDigits">{#$count}</td>
											</tr>
										{/foreach}
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			{else}
				<div class="section">
					<p class="info">{lang}wcf.shrinkr.statistics.noData.countries{/lang}</p>
				</div>
			{/if}
		</div>
		
		<div id="statisticsVisits" class="tabMenuContent">
			{if $statistics.visits|isset}
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
						<div class="tabularBox statisticsTableContainer">
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
													<a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$linkID}{/link}">{$statistics.visits.topUrlHashes[$linkID]}</a>
												{else}
													<a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$linkID}{/link}">URL #{#$linkID}</a>
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
			{else}
				<div class="section">
					<p class="info">{lang}wcf.shrinkr.statistics.noData.referrers{/lang}</p>
				</div>
			{/if}
		</div>
		
		<div id="statisticsCombined" class="tabMenuContent">
			{if $statistics.combined|isset}
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
		
		<div id="statisticsTopUrls" class="tabMenuContent">
			{if $statistics.topUrls|isset && $statistics.topUrls|count}
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
												<a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$linkID}{/link}">{$statistics.topUrlHashes[$linkID]}</a>
											{else}
												<a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$linkID}{/link}">URL #{#$linkID}</a>
											{/if}
										</td>
										<td class="columnDigits">{#$count}</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			{else}
				<div class="section">
					<p class="info">{lang}wcf.shrinkr.statistics.noData.referrers{/lang}</p>
				</div>
			{/if}
		</div>
		
		{event name='statisticsContents'}
		</div>
	{/if}

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
							<span class="badge">{lang}wcf.shrinkr.buttonClick.type.{$object->buttonType}{/lang}</span>
						</td>
						<td class="columnDigits">
							{if $object->linkID|isset && $object->linkID && ($object->buttonType == 'featured_link' || $object->buttonType == 'custom') && $object->linkID}
								<a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$object->linkID}{/link}" title="{lang}wcf.shrinkr.buttonClick.buttonID.tooltip{/lang}" class="jsTooltip">#{#$object->linkID}</a>
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
{/if}

{include file='footer'}

