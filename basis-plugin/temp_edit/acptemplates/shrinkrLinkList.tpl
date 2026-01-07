{include file='header' pageTitle='shrinkr.acp.url.list'}

<script data-relocate="true" src="{unsafe:$__wcf->getPath()}js/WCF.ImageViewer.js?v={unsafe:LAST_UPDATE_TIME}"></script>
{include file='imageViewer'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}shrinkr.acp.url.list{/lang}</h1>
	</div>
	
    {if SHRINKR_ACTIVE}
        <nav class="contentHeaderNavigation">
            <ul>
                <li><a href="{link application='shrinkr' controller='UrlAdd'}{/link}" class="button">{icon name='plus'} <span>{lang}shrinkr.acp.menu.link.url.add{/lang}</span></a></li>
                
                {event name='contentHeaderNavigation'}
            </ul>
        </nav>
    {/if}
</header>

{include file='formError'}

{if SHRINKR_ACTIVE && $objects|count}
	<form action="{link application='shrinkr' controller='ShrinkrLinkList'}{/link}" method="POST">
		<section class="section">
            <h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

			<div class="row rowColGap formGrid">
				<dl class="col-xs-12 col-md-6">
					<dt></dt>
					<dd>
                        <input class="long" type="text" name="q" value="{$q}" placeholder="{lang}wcf.shrinkr.url.hash{/lang} / {lang}wcf.shrinkr.urlGoal{/lang}">
					</dd>
				</dl>

                {* URL Title Filter (from template listener) *}
                <dl class="col-xs-12 col-md-6">
                    <dt></dt>
                    <dd>
                        <input class="long" type="text" name="qTitle" value="{$qTitle}" placeholder="{lang}wcf.shrinkr.url.linkTitle{/lang} / {lang}wcf.shrinkr.featuredLinks{/lang}">
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

{if SHRINKR_ACTIVE}

    {hascontent}
        <div class="paginationTop">
            {content}{pages print=true assign=pagesLinks application='shrinkr' controller="ShrinkrLinkList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&q=$q"}{/content}
        </div>
    {/hascontent}

    {if $objects|count}
        <div class="section tabularBox" id="urlTableContainer">
            <table class="table">
                <thead>
                    <tr>
                        <th class="columnID columnUrlID{if $sortField == 'linkID'} active {unsafe:$sortOrder}{/if}" colspan="2"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=linkID&sortOrder={if $sortField == 'linkID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.global.objectID{/lang}</a></th>
                        <th class="columnTitle columnHash{if $sortField == 'hash'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=hash&sortOrder={if $sortField == 'hash' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.url.hash{/lang}</a></th>
                        <th class="columnTitle columnUrl{if $sortField == 'url'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=url&sortOrder={if $sortField == 'url' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.url{/lang}</a></th>
                        <th class="columnTitle columnUrlGoal{if $sortField == 'urlGoal'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=urlGoal&sortOrder={if $sortField == 'urlGoal' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.urlGoal{/lang}</a></th>
                        {if SHRINKR_COUNTER_ACTIVE}
                            <th class="columnTitle columnCounter{if $sortField == 'counter'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=counter&sortOrder={if $sortField == 'counter' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.url.counter{/lang}</a></th>
                        {/if}
                        <th class="columnTitle columnQR"><a href="#">{lang}wcf.shrinkr.qrCode{/lang}</a></th>
                        
                        {* URL List Column Heads (from template listeners) *}
                        <th class="columnTitle columnFeaturedLinks{if $sortField == 'featuredLinks'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=featuredLinks&sortOrder={if $sortField == 'featuredLinks' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.featuredLink.section{/lang}</a></th>
                        <th class="columnTitle columnSpecial text-center{if $sortField == 'special'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=special&sortOrder={if $sortField == 'special' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.special{/lang}</a></th>
                        <th class="columnTitle columnCustomButtons text-center{if $sortField == 'customButtons'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=customButtons&sortOrder={if $sortField == 'customButtons' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.customButton.section{/lang}</a></th>
                        <th class="columnTitle columnButtonClicks">{lang}wcf.shrinkr.buttonClick.total{/lang}</th>
                        {if MODULE_LIKE && $__wcf->session->getPermission('user.like.canViewLike')}
                            <th class="columnTitle">{lang}wcf.reactions.summary.title{/lang}</th>
                        {/if}
                        
                        {event name='columnHeads'}
                    </tr>
                </thead>
                
                <tbody class="jsReloadPageWhenEmpty jsObjectActionContainer" data-object-action-class-name="shrinkr\data\shrinkrlink\ShrinkrLinkAction">
                    {foreach from=$objects item=url}
                        <tr class="jsUrlRow jsObjectActionObject" data-object-id="{unsafe:$link->getObjectID()}">
                            <td class="columnIcon">
                                <a href="{link application='shrinkr' controller='UrlEdit' id=$link->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {objectAction action="delete" objectTitle=$link->hash}
                                
                                {event name='rowButtons'}
                            </td>
                            <td class="columnID">{#$link->linkID}</td>
                            <td class="columnTitle columnHash"><a href="{link application='shrinkr' controller='UrlEdit' object=$link}{/link}">{$link->hash}</a></td>
                            <td class="columnTitle columnUrl">
                            <button class="copyUrlButton" data-copy-link="{$link->getShortedUrl(true)}" data-tooltip="{lang}wcf.shrinkr.copyUrl{/lang}" aria-label="{lang}wcf.shrinkr.copyUrl{/lang}">{icon name='copy'}</button>
                            <kbd>{$link->getShortedUrl(true)}</kbd>
                            </td>
                            <td class="columnTitle columnUrlGoal"><kbd>{$link->url}</kbd></td>
                            {if SHRINKR_COUNTER_ACTIVE}
                                <td class="columnTitle columnCounter">{$link->counter}</td>
                            {/if}
                            <td class="columnTitle columnQR" data-url="{$link->getShortedUrl(true)}">
                                <a href="#" class="qrDownloadLink" download="qr.png">{lang}wcf.shrinkr.qrCode.download{/lang}</a>
                            </td>
                            
                            {* URL List Columns (from template listeners) *}
                            <td class="columnTitle columnFeaturedLinks">
                                {assign var="featuredLinksCount" value=0}
                                {if $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['countFeaturedLinks']|isset}
                                    {assign var="featuredLinksCount" value=$linksArray[$link->linkID]['countFeaturedLinks']}
                                {/if}
                                {if $featuredLinksCount > 0}
                                    {* Featured Links vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
                                    {$featuredLinksCount} <a href="{link application='shrinkr' controller='UrlEdit' id=$link->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {else}
                                    {* Keine Featured Links vorhanden: 0 / + *}
                                    0 / <a href="{link application='shrinkr' controller='FeaturedLinkAdd'}linkID={#$link->linkID}{/link}" title="{lang}wcf.shrinkr.featuredLink.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
                                {/if}
                            </td>
                            <td class="columnTitle columnSpecial text-center">
                                {if $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['hasActiveSpecial']|isset && ($linksArray[$link->linkID]['hasActiveSpecial'] == true || $linksArray[$link->linkID]['hasActiveSpecial'] == 1)}
                                    {* Aktives Special vorhanden: Status + Bearbeiten-Button *}
                                    <span class="badge green">{lang}wcf.shrinkr.special.status.active{/lang}</span>
                                    {if $linksArray[$link->linkID]['firstActiveSpecialID']|isset && $linksArray[$link->linkID]['firstActiveSpecialID']}
                                        <a href="{link application='shrinkr' controller='SpecialEdit' id=$linksArray[$link->linkID]['firstActiveSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                    {/if}
                                {elseif $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['firstSpecialID']|isset && $linksArray[$link->linkID]['firstSpecialID']}
                                    {* Inaktives Special vorhanden: 0 + Bearbeiten-Button *}
                                    0 <a href="{link application='shrinkr' controller='SpecialEdit' id=$linksArray[$link->linkID]['firstSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {else}
                                    {* Kein Special vorhanden: 0 / + *}
                                    0 / <a href="{link application='shrinkr' controller='SpecialAdd'}linkID={#$link->linkID}{/link}" title="{lang}wcf.shrinkr.special.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
                                {/if}
                            </td>
                            <td class="columnTitle columnCustomButtons text-center">
                                {assign var="customButtonsCount" value=0}
                                {if $linksArray|isset && $linksArray[$link->linkID]|isset && $linksArray[$link->linkID]['countCustomButtons']|isset}
                                    {assign var="customButtonsCount" value=$linksArray[$link->linkID]['countCustomButtons']}
                                {/if}
                                {if $customButtonsCount > 0}
                                    {* Custom Buttons vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
                                    {$customButtonsCount} <a href="{link application='shrinkr' controller='UrlEdit' id=$link->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {else}
                                    {* Keine Custom Buttons vorhanden: 0 / + *}
                                    0 / <a href="{link application='shrinkr' controller='CustomButtonAdd'}linkID={#$link->linkID}{/link}" title="{lang}wcf.shrinkr.customButton.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
                                {/if}
                            </td>
                            <td class="columnTitle columnButtonClicks">
                                {if $buttonClicksArray|isset && $buttonClicksArray[$link->linkID]|isset && $buttonClicksArray[$link->linkID]['total']|isset && $buttonClicksArray[$link->linkID]['total'] > 0}
                                    <span class="jsTooltip" title="{lang}wcf.shrinkr.buttonClick.total{/lang}: {#$buttonClicksArray[$link->linkID]['total']}{if $buttonClicksArray[$link->linkID]['forward']|isset && $buttonClicksArray[$link->linkID]['forward'] > 0}&#10;{lang}wcf.shrinkr.buttonClick.type.forward{/lang}: {#$buttonClicksArray[$link->linkID]['forward']}{/if}{if $buttonClicksArray[$link->linkID]['featured_link']|isset && $buttonClicksArray[$link->linkID]['featured_link'] > 0}&#10;{lang}wcf.shrinkr.buttonClick.type.featured_link{/lang}: {#$buttonClicksArray[$link->linkID]['featured_link']}{/if}{if $buttonClicksArray[$link->linkID]['custom']|isset && $buttonClicksArray[$link->linkID]['custom'] > 0}&#10;{lang}wcf.shrinkr.buttonClick.type.custom{/lang}: {#$buttonClicksArray[$link->linkID]['custom']}{/if}">
                                        {#$buttonClicksArray[$link->linkID]['total']}
                                    </span>
                                {else}
                                    0
                                {/if}
                            </td>
                            {if MODULE_LIKE && $__wcf->session->getPermission('user.like.canViewLike')}
                                <td class="columnText">
                                    {assign var='__reactionSummaryJson' value='[]'}
                                    {assign var='__hasReactions' value=false}
                                    {if $reactionData|isset && $reactionData[$link->linkID]|isset}
                                        {assign var='__reactionSummaryJson' value=$reactionData[$link->linkID]->getReactionsJson()}
                                        {if $reactionData[$link->linkID]->cumulativeLikes > 0}
                                            {assign var='__hasReactions' value=true}
                                        {/if}
                                    {/if}
                                    {if $__hasReactions}
                                    <woltlab-core-reaction-summary
                                        data="{$__reactionSummaryJson}"
                                        object-type="{$reactionObjectType}"
                                        object-id="{#$link->linkID}"
                                        selected-reaction="{if $reactionData|isset && $reactionData[$link->linkID]|isset && $reactionData[$link->linkID]->reactionTypeID}{#$reactionData[$link->linkID]->reactionTypeID}{else}0{/if}"
                                    ></woltlab-core-reaction-summary>
                                    {else}
                                            0
                                    {/if}
                                </td>
                            {/if}
                            
                            {event name='columns'}
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
                    <li><a href="{link application='shrinkr' controller='UrlAdd'}{/link}" class="button">{icon name='plus'} <span>{lang}shrinkr.acp.menu.link.url.add{/lang}</span></a></li>
                    
                    {event name='contentFooterNavigation'}
                </ul>
            </nav>
        </footer>

        <script data-relocate="true">
            require(["Shrinkr/Ui/CopyLinkButton", 'WoltLabSuite/Core/Language'], (CopyLinkButton, Language) => {
                Language.addObject({
                    'wcf.shrinkr.copyUrl.success': '{jslang}wcf.shrinkr.copyUrl.success{/jslang}',
                    'wcf.shrinkr.copyUrl.error': '{jslang}wcf.shrinkr.copyUrl.error{/jslang}'
                });

                CopyLinkButton.setup();
            });

            require(['Shrinkr/Ui/User/Url/Qr', 'WoltLabSuite/Core/Language'], (Qr, Language) => {
                Language.addObject({
                    'wcf.shrinkr.qrCode': '{jslang}wcf.shrinkr.qrCode{/jslang}',
                });

                Qr.renderAll();
            });
        </script>
    {else}
        <p class="info">{lang}wcf.global.noItems{/lang}</p>
    {/if}
{else}
    <p class="error">{lang}wcf.shrinkr.notActive{/lang}</p>
{/if}

{include file='footer'}
