{include file='header' pageTitle='shrinkr.acp.menu.link.description.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}shrinkr.acp.menu.link.description.list{/lang}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='DescriptionAdd' application='shrinkr'}{/link}"
					class="button">{icon size=16 name='plus'}
					<span>{lang}shrinkr.acp.menu.link.description.add{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{include file='formError'}

{if $objects|count || $title || $descriptionText}
	<form action="{link controller='DescriptionList' application='shrinkr'}{/link}" method="POST">
		<section class="section">
			<h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

			<div class="row rowColGap formGrid">
				<dl class="col-xs-12 col-md-4">
					<dt><label for="title">{lang}wcf.global.title{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="title" name="title" value="{$title}" placeholder="{lang}wcf.global.title{/lang}">
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-4">
					<dt><label for="descriptionText">{lang}wcf.shrinkr.description.descriptionText{/lang}</label></dt>
					<dd>
						<input class="long" type="text" id="descriptionText" name="descriptionText" value="{$descriptionText}" placeholder="{lang}wcf.shrinkr.description.descriptionText{/lang}">
					</dd>
				</dl>

				<dl class="col-xs-12 col-md-4">
					<dt><label for="isActive">{lang}wcf.shrinkr.description.isActive{/lang}</label></dt>
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
{pages print=true assign=pagesLinks application='shrinkr' controller="DescriptionList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&title=$title&descriptionText=$descriptionText&isActive=$isActiveFilter"}
	{/content}
</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table jsObjectActionContainer" data-object-action-class-name="shrinkr\data\description\DescriptionAction">
			<thead>
				<tr>
					<th class="columnID columnDescriptionID{if $sortField == 'descriptionID'} active {unsafe:$sortOrder}{/if}" colspan="2">
						<a href="{link controller='DescriptionList' application='shrinkr'}pageNo={#$pageNo}&sortField=descriptionID&sortOrder={if $sortField == 'descriptionID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&descriptionText={$descriptionText}&isActive={$isActiveFilter}{/link}">
							{lang}wcf.global.objectID{/lang}
						</a>
					</th>
					<th class="columnTitle{if $sortField == 'title'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='DescriptionList' application='shrinkr'}pageNo={#$pageNo}&sortField=title&sortOrder={if $sortField == 'title' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&descriptionText={$descriptionText}&isActive={$isActiveFilter}{/link}">
							{lang}wcf.global.title{/lang}
						</a>
					</th>
					<th class="columnText">
						{lang}wcf.shrinkr.description.preview{/lang}
					</th>
					<th class="columnDigits columnIsActive{if $sortField == 'isActive'} active {unsafe:$sortOrder}{/if}">
						<a href="{link controller='DescriptionList' application='shrinkr'}pageNo={#$pageNo}&sortField=isActive&sortOrder={if $sortField == 'isActive' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&title={$title}&descriptionText={$descriptionText}&isActive={$isActiveFilter}{/link}">
							{lang}wcf.shrinkr.description.status{/lang}
						</a>
					</th>
				</tr>
			</thead>
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item=object}
					<tr class="jsObjectActionObject" data-object-id="{#$object->descriptionID}">
						<td class="columnIcon">
							{assign var='descriptionIsDisabled' value=true}
							{if $object->isActive()}
								{assign var='descriptionIsDisabled' value=false}
							{/if}
							{objectAction action="toggle" isDisabled=$descriptionIsDisabled disableTitle='wcf.shrinkr.description.isActive.yes' enableTitle='wcf.shrinkr.description.isActive.no'}
							<a href="{link controller='DescriptionEdit' id=$object->descriptionID application='shrinkr'}{/link}"
								title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon size=16 name='pencil'}</a>
							{objectAction action="delete" objectTitle=$object->getTitle()}
							{event name='rowButtons'}
						</td>
						<td class="columnID">{#$object->descriptionID}</td>
						<td class="columnTitle">{$object->title}</td>
						<td class="columnText">
							{if $object->descriptionText}
								{$object->descriptionText|strip_tags|truncate:120}
							{else}
								-
							{/if}
						</td>
						<td class="columnDigits">
							{if $object->isActive()}
								<span class="badge green">{lang}wcf.shrinkr.description.isActive.yes{/lang}</span>
							{else}
								<span class="badge red">{lang}wcf.shrinkr.description.isActive.no{/lang}</span>
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
				<li><a href="{link controller='DescriptionAdd' application='shrinkr'}{/link}"
						class="button">{icon size=16 name='plus'}
						<span>{lang}shrinkr.acp.menu.link.description.add{/lang}</span></a></li>
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{else}
	<p class="info">{lang}wcf.global.noItems{/lang}</p>
{/if}

{include file='footer'}
