{*
 * Template-Zweck: ACP-Liste für Discounts
 * 
 * ACP-Template für die Anzeige aller Discounts in einer sortierbaren
 * und filterbaren Tabelle. Zeigt Discount-Wert, Codes, Hosts, Special
 * und Countdown an. Unterstützt Filterung nach Discount-Wert, Codes und Hosts.
 * 
 * Variablen:
 * @var array $objects - Array von Discount-Objekten
 * @var string $discountValue - Filter: Discount-Wert
 * @var string $codes - Filter: Discount-Codes
 * @var string $hosts - Filter: Hostnames
 * @var string $sortField - Sortierfeld
 * @var string $sortOrder - Sortierreihenfolge
 * 
 * Logik:
 * - Zeigt Filter-Formular für Discount-Wert, Codes und Hosts
 * - Zeigt sortierbare Tabelle mit allen Discounts
 * - Zeigt Countdown für aktive Discounts
 * - Initialisiert Countdown-Timer für Liste
 * - Unterstützt Pagination
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{include file='header' pageTitle='shrinkr.acp.menu.link.discount.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">
			{lang}shrinkr.acp.menu.link.discount.list{/lang}
			{if $objectCount|isset} <span class="badge badgeInverse">{#$objectCount}</span>{/if}
		</h1>
		<p class="contentHeaderDescription">
			{lang}shrinkr.acp.discount.list.description{/lang}
		</p>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='DiscountAdd'}{/link}"
					class="button">{icon size=16 name='plus'}
					<span>{lang}shrinkr.acp.menu.link.discount.add{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{include file='formError'}

{if $objects|count || $discountValue || $codes || $hosts}
	<form action="{link controller='DiscountList'}{/link}" method="POST">
		<section class="section">
			<h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

			<div class="row rowColGap formGrid">
				<dl class="col-xs-12 col-md-4">
					<dt><label for="discountValue">{lang}wcf.shrinkr.discount{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="discountValue" name="discountValue" value="{$discountValue}" placeholder="{lang}wcf.shrinkr.discount{/lang}">
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-4">
					<dt><label for="codes">{lang}wcf.shrinkr.codes{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="codes" name="codes" value="{$codes}" placeholder="{lang}wcf.shrinkr.codes{/lang}">
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-4">
					<dt><label for="hosts">{lang}wcf.shrinkr.hosts{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="hosts" name="hosts" value="{$hosts}" placeholder="{lang}wcf.shrinkr.hosts{/lang}">
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
{pages print=true assign=pagesLinks controller="DiscountList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&discountValue=$discountValue&codes=$codes&hosts=$hosts"}
	{/content}
</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table jsObjectActionContainer" data-object-action-class-name="shrinkr\data\discount\DiscountAction">
			<thead>
				<tr>
					<th></th>
					<th class="columnID columnDiscountID{if $sortField == 'discountID'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='DiscountList'}pageNo={#$pageNo}&sortField=discountID&sortOrder={if $sortField == 'discountID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&discountValue={$discountValue}&codes={$codes}&hosts={$hosts}{/link}">
							{lang}wcf.global.objectID{/lang}
						</a>
					</th>
					<th class="columnTitle columnDiscountValue{if $sortField == 'discountValue'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='DiscountList'}pageNo={#$pageNo}&sortField=discountValue&sortOrder={if $sortField == 'discountValue' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&discountValue={$discountValue}&codes={$codes}&hosts={$hosts}{/link}">
							{lang}wcf.shrinkr.discount{/lang}
						</a>
					</th>
					<th>{lang}wcf.acp.style.general.favicon{/lang}</th>
					<th>{lang}wcf.shrinkr.hosts{/lang}</th>
					<th>{lang}wcf.shrinkr.codes{/lang}</th>
				</tr>
			</thead>
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item=object}
					<tr class="jsObjectActionObject" data-object-id="{#$object->discountID}">
						<td class="columnIcon">
							<a href="{link controller='DiscountEdit' application='shrinkr' id=$object->discountID}{/link}"
								title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon size=16 name='pencil'}</a>
							{objectAction action="delete" objectTitle=$object->discountID}
							{event name='rowButtons'}
						</td>
						<td class="columnID">{#$object->discountID}</td>
						<td class="columnText">{$object->discountValue}</td>
						<td class="columnIcon">
							{assign var="hostUrl" value=$object->getFirstHostUrl()}
							{assign var="imagePath" value=$object->getImagePath($hostUrl)}
							{if $imagePath}
								<img src="{$imagePath}" alt="" class="discountFaviconPreview" loading="lazy">
							{/if}
						</td>
						<td class="columnText">
							{if $object->getHostsArray()|count}
								{foreach from=$object->getHostsArray() item=host}
									<span class="badge badgeInverse">{$host}</span>
								{/foreach}
							{else}
								-
							{/if}
						</td>
						<td class="columnText">
							{if $object->hasValidCodes()}
								{foreach from=$object->getCodesArray() item=code}
									<span class="badge">{$code}</span>
								{/foreach}
							{else}
								<span class="icon icon16 fa-times red"></span>
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
				<li><a href="{link controller='DiscountAdd'}{/link}"
						class="button">{icon size=16 name='plus'}
						<span>{lang}shrinkr.acp.menu.link.discount.add{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{else}
	<p class="info">{lang}wcf.global.noItems{/lang}</p>
{/if}

<script data-relocate="true">
	require(['Shrinkr/DiscountCountdown'], function(DiscountCountdown) {
		DiscountCountdown.initList();
	});
</script>

{* Menu Badge JavaScript *}
{if $menuBadgeText}
<script>
	window.SHRINKR_MENU_BADGE_TEXT = {$menuBadgeText|json};
	require(['Shrinkr/Acp/Ui/MenuBadge'], function(MenuBadge) {
		new MenuBadge();
	});
</script>
{/if}

{include file='footer'}