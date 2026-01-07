{include file='header' pageTitle='urlshort.acp.url.list'}

<script data-relocate="true" src="{unsafe:$__wcf->getPath()}js/WCF.ImageViewer.js?v={unsafe:LAST_UPDATE_TIME}"></script>
{include file='imageViewer'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}urlshort.acp.url.list{/lang}</h1>
	</div>
	
    {if URLSHORT_ACTIVE}
        <nav class="contentHeaderNavigation">
            <ul>
                <li><a href="{link application='urlshort' controller='UrlAdd'}{/link}" class="button">{icon name='plus'} <span>{lang}urlshort.acp.menu.link.url.add{/lang}</span></a></li>
                
                {event name='contentHeaderNavigation'}
            </ul>
        </nav>
    {/if}
</header>

{include file='formError'}

{if URLSHORT_ACTIVE && $objects|count}
	<form action="{link application='urlshort' controller='UrlList'}{/link}" method="POST">
		<section class="section">
            <h2 class="sectionTitle">{lang}wcf.global.filter{/lang}</h2>

			<div class="row rowColGap formGrid">
				<dl class="col-xs-12 col-md-6">
					<dt></dt>
					<dd>
                        <input class="long" type="text" name="q" value="{$q}" placeholder="{lang}wcf.urlshort.url.hash{/lang} / {lang}wcf.urlshort.urlGoal{/lang}">
					</dd>
				</dl>

                {* URL Title Filter (from template listener) *}
                <dl class="col-xs-12 col-md-6">
                    <dt></dt>
                    <dd>
                        <input class="long" type="text" name="qTitle" value="{$qTitle}" placeholder="{lang}wcf.urlshort.url.urlTitle{/lang} / {lang}wcf.urlshort.featuredLinks{/lang}">
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

{if URLSHORT_ACTIVE}

    {hascontent}
        <div class="paginationTop">
            {content}{pages print=true assign=pagesLinks application='urlshort' controller="UrlList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&q=$q"}{/content}
        </div>
    {/hascontent}

    {if $objects|count}
        <div class="section tabularBox" id="urlTableContainer">
            <table class="table">
                <thead>
                    <tr>
                        <th class="columnID columnUrlID{if $sortField == 'urlID'} active {unsafe:$sortOrder}{/if}" colspan="2"><a href="{link application='urlshort' controller='UrlList'}pageNo={unsafe:$pageNo}&sortField=urlID&sortOrder={if $sortField == 'urlID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.global.objectID{/lang}</a></th>
                        <th class="columnTitle columnHash{if $sortField == 'hash'} active {unsafe:$sortOrder}{/if}"><a href="{link application='urlshort' controller='UrlList'}pageNo={unsafe:$pageNo}&sortField=hash&sortOrder={if $sortField == 'hash' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.urlshort.url.hash{/lang}</a></th>
                        <th class="columnTitle columnUrl{if $sortField == 'url'} active {unsafe:$sortOrder}{/if}"><a href="{link application='urlshort' controller='UrlList'}pageNo={unsafe:$pageNo}&sortField=url&sortOrder={if $sortField == 'url' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.urlshort.url{/lang}</a></th>
                        <th class="columnTitle columnUrlGoal{if $sortField == 'urlGoal'} active {unsafe:$sortOrder}{/if}"><a href="{link application='urlshort' controller='UrlList'}pageNo={unsafe:$pageNo}&sortField=urlGoal&sortOrder={if $sortField == 'urlGoal' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.urlshort.urlGoal{/lang}</a></th>
                        {if URLSHORT_COUNTER_ACTIVE}
                            <th class="columnTitle columnCounter{if $sortField == 'counter'} active {unsafe:$sortOrder}{/if}"><a href="{link application='urlshort' controller='UrlList'}pageNo={unsafe:$pageNo}&sortField=counter&sortOrder={if $sortField == 'counter' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.urlshort.url.counter{/lang}</a></th>
                        {/if}
                        <th class="columnTitle columnQR"><a href="#">{lang}wcf.urlshort.qrCode{/lang}</a></th>
                        
                        {* URL List Column Heads (from template listeners) *}
                        <th class="columnTitle columnFeaturedLinks{if $sortField == 'featuredLinks'} active {unsafe:$sortOrder}{/if}"><a href="{link application='urlshort' controller='UrlList'}pageNo={unsafe:$pageNo}&sortField=featuredLinks&sortOrder={if $sortField == 'featuredLinks' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.urlshort.featuredLink.section{/lang}</a></th>
                        <th class="columnTitle columnSpecial text-center{if $sortField == 'special'} active {unsafe:$sortOrder}{/if}"><a href="{link application='urlshort' controller='UrlList'}pageNo={unsafe:$pageNo}&sortField=special&sortOrder={if $sortField == 'special' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.urlshort.special{/lang}</a></th>
                        <th class="columnTitle columnCustomButtons text-center{if $sortField == 'customButtons'} active {unsafe:$sortOrder}{/if}"><a href="{link application='urlshort' controller='UrlList'}pageNo={unsafe:$pageNo}&sortField=customButtons&sortOrder={if $sortField == 'customButtons' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.urlshort.customButton.section{/lang}</a></th>
                        <th class="columnTitle columnButtonClicks">{lang}wcf.urlshort.buttonClick.total{/lang}</th>
                        {if MODULE_LIKE && $__wcf->session->getPermission('user.like.canViewLike')}
                            <th class="columnTitle">{lang}wcf.reactions.summary.title{/lang}</th>
                        {/if}
                        
                        {event name='columnHeads'}
                    </tr>
                </thead>
                
                <tbody class="jsReloadPageWhenEmpty jsObjectActionContainer" data-object-action-class-name="urlshort\data\url\UrlAction">
                    {foreach from=$objects item=url}
                        <tr class="jsUrlRow jsObjectActionObject" data-object-id="{unsafe:$url->getObjectID()}">
                            <td class="columnIcon">
                                <a href="{link application='urlshort' controller='UrlEdit' id=$url->urlID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {objectAction action="delete" objectTitle=$url->hash}
                                
                                {event name='rowButtons'}
                            </td>
                            <td class="columnID">{#$url->urlID}</td>
                            <td class="columnTitle columnHash"><a href="{link application='urlshort' controller='UrlEdit' object=$url}{/link}">{$url->hash}</a></td>
                            <td class="columnTitle columnUrl">
                            <button class="copyUrlButton" data-copy-link="{$url->getShortedUrl(true)}" data-tooltip="{lang}wcf.urlshort.copyUrl{/lang}" aria-label="{lang}wcf.urlshort.copyUrl{/lang}">{icon name='copy'}</button>
                            <kbd>{$url->getShortedUrl(true)}</kbd>
                            </td>
                            <td class="columnTitle columnUrlGoal"><kbd>{$url->url}</kbd></td>
                            {if URLSHORT_COUNTER_ACTIVE}
                                <td class="columnTitle columnCounter">{$url->counter}</td>
                            {/if}
                            <td class="columnTitle columnQR" data-url="{$url->getShortedUrl(true)}">
                                <a href="#" class="qrDownloadLink" download="qr.png">{lang}wcf.urlshort.qrCode.download{/lang}</a>
                            </td>
                            
                            {* URL List Columns (from template listeners) *}
                            <td class="columnTitle columnFeaturedLinks">
                                {assign var="featuredLinksCount" value=0}
                                {if $linksArray|isset && $linksArray[$url->urlID]|isset && $linksArray[$url->urlID]['countFeaturedLinks']|isset}
                                    {assign var="featuredLinksCount" value=$linksArray[$url->urlID]['countFeaturedLinks']}
                                {/if}
                                {if $featuredLinksCount > 0}
                                    {* Featured Links vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
                                    {$featuredLinksCount} <a href="{link application='urlshort' controller='UrlEdit' id=$url->urlID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {else}
                                    {* Keine Featured Links vorhanden: 0 / + *}
                                    0 / <a href="{link application='urlshort' controller='FeaturedLinkAdd'}urlID={#$url->urlID}{/link}" title="{lang}wcf.urlshort.featuredLink.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
                                {/if}
                            </td>
                            <td class="columnTitle columnSpecial text-center">
                                {if $linksArray|isset && $linksArray[$url->urlID]|isset && $linksArray[$url->urlID]['hasActiveSpecial']|isset && ($linksArray[$url->urlID]['hasActiveSpecial'] == true || $linksArray[$url->urlID]['hasActiveSpecial'] == 1)}
                                    {* Aktives Special vorhanden: Status + Bearbeiten-Button *}
                                    <span class="badge green">{lang}wcf.urlshort.special.status.active{/lang}</span>
                                    {if $linksArray[$url->urlID]['firstActiveSpecialID']|isset && $linksArray[$url->urlID]['firstActiveSpecialID']}
                                        <a href="{link application='urlshort' controller='SpecialEdit' id=$linksArray[$url->urlID]['firstActiveSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                    {/if}
                                {elseif $linksArray|isset && $linksArray[$url->urlID]|isset && $linksArray[$url->urlID]['firstSpecialID']|isset && $linksArray[$url->urlID]['firstSpecialID']}
                                    {* Inaktives Special vorhanden: 0 + Bearbeiten-Button *}
                                    0 <a href="{link application='urlshort' controller='SpecialEdit' id=$linksArray[$url->urlID]['firstSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {else}
                                    {* Kein Special vorhanden: 0 / + *}
                                    0 / <a href="{link application='urlshort' controller='SpecialAdd'}urlID={#$url->urlID}{/link}" title="{lang}wcf.urlshort.special.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
                                {/if}
                            </td>
                            <td class="columnTitle columnCustomButtons text-center">
                                {assign var="customButtonsCount" value=0}
                                {if $linksArray|isset && $linksArray[$url->urlID]|isset && $linksArray[$url->urlID]['countCustomButtons']|isset}
                                    {assign var="customButtonsCount" value=$linksArray[$url->urlID]['countCustomButtons']}
                                {/if}
                                {if $customButtonsCount > 0}
                                    {* Custom Buttons vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
                                    {$customButtonsCount} <a href="{link application='urlshort' controller='UrlEdit' id=$url->urlID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {else}
                                    {* Keine Custom Buttons vorhanden: 0 / + *}
                                    0 / <a href="{link application='urlshort' controller='CustomButtonAdd'}urlID={#$url->urlID}{/link}" title="{lang}wcf.urlshort.customButton.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
                                {/if}
                            </td>
                            <td class="columnTitle columnButtonClicks">
                                {if $buttonClicksArray|isset && $buttonClicksArray[$url->urlID]|isset && $buttonClicksArray[$url->urlID]['total']|isset && $buttonClicksArray[$url->urlID]['total'] > 0}
                                    <span class="jsTooltip" title="{lang}wcf.urlshort.buttonClick.total{/lang}: {#$buttonClicksArray[$url->urlID]['total']}{if $buttonClicksArray[$url->urlID]['forward']|isset && $buttonClicksArray[$url->urlID]['forward'] > 0}&#10;{lang}wcf.urlshort.buttonClick.type.forward{/lang}: {#$buttonClicksArray[$url->urlID]['forward']}{/if}{if $buttonClicksArray[$url->urlID]['featured_link']|isset && $buttonClicksArray[$url->urlID]['featured_link'] > 0}&#10;{lang}wcf.urlshort.buttonClick.type.featured_link{/lang}: {#$buttonClicksArray[$url->urlID]['featured_link']}{/if}{if $buttonClicksArray[$url->urlID]['custom']|isset && $buttonClicksArray[$url->urlID]['custom'] > 0}&#10;{lang}wcf.urlshort.buttonClick.type.custom{/lang}: {#$buttonClicksArray[$url->urlID]['custom']}{/if}">
                                        {#$buttonClicksArray[$url->urlID]['total']}
                                    </span>
                                {else}
                                    0
                                {/if}
                            </td>
                            {if MODULE_LIKE && $__wcf->session->getPermission('user.like.canViewLike')}
                                <td class="columnText">
                                    {assign var='__reactionSummaryJson' value='[]'}
                                    {assign var='__hasReactions' value=false}
                                    {if $reactionData|isset && $reactionData[$url->urlID]|isset}
                                        {assign var='__reactionSummaryJson' value=$reactionData[$url->urlID]->getReactionsJson()}
                                        {if $reactionData[$url->urlID]->cumulativeLikes > 0}
                                            {assign var='__hasReactions' value=true}
                                        {/if}
                                    {/if}
                                    {if $__hasReactions}
                                    <woltlab-core-reaction-summary
                                        data="{$__reactionSummaryJson}"
                                        object-type="{$reactionObjectType}"
                                        object-id="{#$url->urlID}"
                                        selected-reaction="{if $reactionData|isset && $reactionData[$url->urlID]|isset && $reactionData[$url->urlID]->reactionTypeID}{#$reactionData[$url->urlID]->reactionTypeID}{else}0{/if}"
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
                    <li><a href="{link application='urlshort' controller='UrlAdd'}{/link}" class="button">{icon name='plus'} <span>{lang}urlshort.acp.menu.link.url.add{/lang}</span></a></li>
                    
                    {event name='contentFooterNavigation'}
                </ul>
            </nav>
        </footer>

        <script data-relocate="true">
            require(["JulianPfeil/Urlshort/Ui/CopyLinkButton", 'WoltLabSuite/Core/Language'], (CopyLinkButton, Language) => {
                Language.addObject({
                    'wcf.urlshort.copyUrl.success': '{jslang}wcf.urlshort.copyUrl.success{/jslang}',
                    'wcf.urlshort.copyUrl.error': '{jslang}wcf.urlshort.copyUrl.error{/jslang}'
                });

                CopyLinkButton.setup();
            });

            require(['JulianPfeil/Urlshort/Ui/User/Url/Qr', 'WoltLabSuite/Core/Language'], (Qr, Language) => {
                Language.addObject({
                    'wcf.urlshort.qrCode': '{jslang}wcf.urlshort.qrCode{/jslang}',
                });

                Qr.renderAll();
            });
        </script>
    {else}
        <p class="info">{lang}wcf.global.noItems{/lang}</p>
    {/if}
{else}
    <p class="error">{lang}wcf.urlshort.notActive{/lang}</p>
{/if}

{include file='footer'}
