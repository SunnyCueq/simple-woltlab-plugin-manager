{*
 * Template-Zweck: ACP-Liste für Themes
 * 
 * ACP-Template für die Anzeige aller Themes in einer sortierbaren
 * und filterbaren Tabelle. Zeigt Identifier, Titel, Effekt und Status an.
 * Unterstützt Filterung nach Titel und Identifier.
 * 
 * Variablen:
 * @var array $objects - Array von Theme-Objekten
 * @var string $title - Filter: Titel
 * @var string $identifier - Filter: Identifier
 * @var string $sortField - Sortierfeld
 * @var string $sortOrder - Sortierreihenfolge
 * 
 * Logik:
 * - Zeigt Filter-Formular für Titel und Identifier
 * - Zeigt sortierbare Tabelle mit allen Themes
 * - Unterstützt Pagination
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{include file='header' pageTitle='shrinkr.acp.menu.link.theme.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}shrinkr.acp.menu.link.theme.list{/lang}</h1>
		<p class="contentHeaderDescription">{lang}shrinkr.acp.theme.list.description{/lang}</p>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='ThemeAdd'}{/link}"
					class="button">{icon size=16 name='plus'}
					<span>{lang}shrinkr.acp.menu.link.theme.add{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{include file='formError'}

{if $objects|count || $title || $identifier}
	<form action="{link controller='ThemeList'}{/link}" method="POST">
		<section class="section">
			<h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

			<div class="row rowColGap formGrid">
				<dl class="col-xs-12 col-md-4">
					<dt><label for="title">{lang}wcf.shrinkr.theme.title{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="title" name="title" value="{$title}" placeholder="{lang}wcf.shrinkr.theme.title{/lang}">
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-4">
					<dt><label for="identifier">{lang}wcf.shrinkr.theme.identifier{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="identifier" name="identifier" value="{$identifier}" placeholder="{lang}wcf.shrinkr.theme.identifier{/lang}">
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-4">
					<dt><label for="isActive">{lang}wcf.shrinkr.theme.isActive{/lang}</label></dt>
					<dd>
						<select id="isActive" name="isActive" class="long">
							<option value="">{lang}wcf.global.noSelection{/lang}</option>
							<option value="1" {if $isActiveFilter === 1}selected{/if}>{lang}wcf.shrinkr.yes{/lang}</option>
							<option value="0" {if $isActiveFilter === 0}selected{/if}>{lang}wcf.shrinkr.no{/lang}</option>
						</select>
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
{if $sortField == 'sortOrder'}
	{pages print=true assign=pagesLinks controller="ThemeList" link="pageNo=%d&sortField=themeID&sortOrder=ASC&title=$title&identifier=$identifier&isActive=$isActiveFilter"}
{else}
	{pages print=true assign=pagesLinks controller="ThemeList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&title=$title&identifier=$identifier&isActive=$isActiveFilter"}
{/if}
	{/content}
</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table jsObjectActionContainer" data-object-action-class-name="shrinkr\data\theme\ThemeAction">
			<thead>
				<tr>
					<th class="columnID columnThemeID{if $sortField == 'themeID'} active {unsafe:$sortOrder}{/if}" colspan="2">
						<a href="{link controller='ThemeList'}pageNo={unsafe:$pageNo}&sortField=themeID&sortOrder={if $sortField == 'themeID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&identifier={$identifier}&isActive={$isActiveFilter}{/link}">
							{lang}wcf.global.objectID{/lang}
						</a>
					</th>
					<th class="columnTitle columnThemeIdentifier{if $sortField == 'identifier'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='ThemeList'}pageNo={unsafe:$pageNo}&sortField=identifier&sortOrder={if $sortField == 'identifier' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&identifier={$identifier}&isActive={$isActiveFilter}{/link}">
							{lang}wcf.shrinkr.theme.identifier{/lang}
						</a>
					</th>
					<th class="columnTitle columnThemeTitle{if $sortField == 'title'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='ThemeList'}pageNo={unsafe:$pageNo}&sortField=title&sortOrder={if $sortField == 'title' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&identifier={$identifier}&isActive={$isActiveFilter}{/link}">
							{lang}wcf.shrinkr.theme.title{/lang}
						</a>
					</th>
					<th class="columnText">{lang}wcf.shrinkr.theme.colors{/lang}</th>
					<th class="columnText">{lang}wcf.shrinkr.theme.effect{/lang}</th>
					<th class="columnDigits">{lang}wcf.shrinkr.theme.status{/lang}</th>
				</tr>
			</thead>
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item=object}
					<tr class="jsObjectActionObject" data-object-id="{#$object->themeID}">
						<td class="columnIcon">
							{assign var='themeIsDisabled' value=true}
							{if $object->isActive}
								{assign var='themeIsDisabled' value=false}
							{/if}
							{objectAction action="toggle" isDisabled=$themeIsDisabled disableTitle='wcf.shrinkr.theme.isActive.yes' enableTitle='wcf.shrinkr.theme.isActive.no'}
							<a href="{link controller='ThemeEdit' application='shrinkr' id=$object->themeID}{/link}"
								title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon size=16 name='pencil'}</a>
							{objectAction action="delete" objectTitle=$object->title}
							{event name='rowButtons'}
						</td>
						<td class="columnID">{#$object->themeID}</td>
						<td class="columnText"><code>{$object->identifier}</code></td>
						<td class="columnTitle">{$object->title}</td>
						<td class="columnText">
							{unsafe:$object->getColorPreviewHtml()}
						</td>
						<td class="columnText">
							{if $object->effectIdentifier|isset && $object->effectIdentifier !== ''}
								{lang}wcf.shrinkr.theme.effect.{$object->effectIdentifier}{/lang}
							{else}
								{lang}wcf.shrinkr.theme.effect.none{/lang}
							{/if}
						</td>
						<td class="columnDigits">
							{if $object->isActive}
								<span class="badge green">{lang}wcf.shrinkr.theme.isActive.yes{/lang}</span>
							{else}
								<span class="badge red">{lang}wcf.shrinkr.theme.isActive.no{/lang}</span>
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
			{content}{unsafe:$pagesLinks}{/content}
		</div>
		{/hascontent}

		<nav class="contentFooterNavigation">
			<ul>
				<li><a href="{link controller='ThemeAdd'}{/link}"
						class="button">{icon size=16 name='plus'}
						<span>{lang}shrinkr.acp.menu.link.theme.add{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{else}
	<woltlab-core-notice type="info">{lang}wcf.global.noItems{/lang}</woltlab-core-notice>

	<footer class="contentFooter">
		<nav class="contentFooterNavigation">
			<ul>
				<li><a href="{link controller='ThemeAdd'}{/link}"
						class="button">{icon size=16 name='plus'}
						<span>{lang}shrinkr.acp.menu.link.theme.add{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{/if}

{include file='footer'}

