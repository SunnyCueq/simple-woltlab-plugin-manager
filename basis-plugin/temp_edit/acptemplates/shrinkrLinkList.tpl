{*
 * Template-Zweck: ACP-Liste für verkürzte URLs
 * 
 * ACP-Template für die Anzeige aller verkürzten URLs in einer sortierbaren
 * und filterbaren Tabelle. Zeigt Hash, URL, URL Goal, Counter, QR-Codes,
 * Featured Links, Specials, Custom Buttons und Reactions an. Unterstützt
 * Filterung nach Hash/URL Goal und Titel.
 * 
 * Variablen:
 * @var array $objects - Array von ShrinkrLink-Objekten
 * @var string $q - Suchfilter für Hash/URL Goal
 * @var string $qTitle - Suchfilter für Titel
 * @var string $sortField - Sortierfeld (hash, url, urlGoal, counter, etc.)
 * @var string $sortOrder - Sortierreihenfolge (ASC, DESC)
 * @var int $pageNo - Aktuelle Seitenzahl
 * @var bool SHRINKR_ACTIVE - Ob Plugin aktiv ist
 * @var bool SHRINKR_COUNTER_ACTIVE - Ob Counter aktiviert ist
 * @var bool MODULE_LIKE - Ob Like/Reaction-Modul aktiv ist
 * @var array $reactionData - Reaction-Daten (von Template Listener)
 * 
 * Logik:
 * - Zeigt Filter-Formular für Hash/URL Goal und Titel
 * - Zeigt sortierbare Tabelle mit allen URLs
 * - Zeigt QR-Codes mit Download-Funktionalität
 * - Zeigt Featured Links, Specials, Custom Buttons als Icons/Badges
 * - Zeigt Reactions (wenn Modul aktiv)
 * - Initialisiert QR-Code-Rendering und Image Viewer
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
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
                <li><a href="{link controller='ShrinkrLinkAdd'}{/link}" class="button">{icon name='plus'} <span>{lang}shrinkr.acp.menu.link.link.add{/lang}</span></a></li>
                
                {event name='contentHeaderNavigation'}
            </ul>
        </nav>
    {/if}
</header>

{include file='formError'}

{if SHRINKR_ACTIVE && $objects|count}
	<form action="{link controller='ShrinkrLinkList'}{/link}" method="POST">
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
                        <input class="long" type="text" name="qTitle" value="{$qTitle}" placeholder="{lang}wcf.shrinkr.url.linkTitle.placeholder{/lang}">
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
            {content}{pages print=true assign=pagesLinks controller="ShrinkrLinkList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder&q=$q"}{/content}
        </div>
    {/hascontent}

    {if $objects|count}
        <div class="section tabularBox" id="urlTableContainer">
            <table class="table">
                <thead>
                    <tr>
                        <th class="columnID columnUrlID{if $sortField == 'linkID'} active {unsafe:$sortOrder}{/if}" colspan="2"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=linkID&sortOrder={if $sortField == 'linkID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.global.objectID{/lang}</a></th>
                        <th class="columnTitle columnHash{if $sortField == 'hash'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=hash&sortOrder={if $sortField == 'hash' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.url.hash{/lang}</a></th>
                        <th class="columnTitle columnUrl{if $sortField == 'url'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=url&sortOrder={if $sortField == 'url' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.url{/lang}</a></th>
                        <th class="columnTitle columnUrlGoal{if $sortField == 'urlGoal'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=urlGoal&sortOrder={if $sortField == 'urlGoal' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.urlGoal{/lang}</a></th>
                        {if SHRINKR_COUNTER_ACTIVE}
                            <th class="columnTitle columnCounter text-center{if $sortField == 'counter'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=counter&sortOrder={if $sortField == 'counter' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.url.counter{/lang}</a></th>
                        {/if}
                        <th class="columnTitle columnQR text-center"><span class="qrIconHeader">{icon size=24 name='qrcode'}</span></th>
                        
                        {* URL List Column Heads (from template listeners) *}
                        <th class="columnTitle columnFeaturedLinks text-center{if $sortField == 'featuredLinks'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=featuredLinks&sortOrder={if $sortField == 'featuredLinks' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.featuredLink.section{/lang}</a></th>
                        <th class="columnTitle columnSpecial text-center{if $sortField == 'special'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=special&sortOrder={if $sortField == 'special' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.special{/lang}</a></th>
                        <th class="columnTitle columnCustomButtons text-center{if $sortField == 'customButtons'} active {unsafe:$sortOrder}{/if}"><a href="{link controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=customButtons&sortOrder={if $sortField == 'customButtons' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.customButton.section{/lang}</a></th>
                        
                        {if MODULE_LIKE && $__wcf->session->getPermission('user.like.canViewLike')}
                            <th class="columnTitle text-center">{lang}wcf.reactions.summary.title{/lang}</th>
                        {/if}
                        
                        {event name='columnHeads'}
                    </tr>
                </thead>
                
                <tbody class="jsReloadPageWhenEmpty jsObjectActionContainer" data-object-action-class-name="shrinkr\data\shrinkrlink\ShrinkrLinkAction">
                    {foreach from=$objects item=url}
                        <tr class="jsUrlRow jsObjectActionObject" data-object-id="{unsafe:$url->getObjectID()}">
                            <td class="columnIcon">
                                {if $url->passwordHash}
                                    <a href="{link controller='ShrinkrLinkList' application='shrinkr'}passwordProtected=1{/link}" title="{lang}wcf.shrinkr.link.passwordProtected{/lang}" class="acpPageSubMenuIcon{if $passwordProtected} active{/if}" data-tooltip="{lang}wcf.shrinkr.link.passwordProtected{/lang}" aria-label="{lang}wcf.shrinkr.link.passwordProtected{/lang}">
                                        <span class="shrinkrPasswordIcon">{icon name='key' size=16}</span>
                                    </a>
                                {/if}
                                <a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$url->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {objectAction action="delete" objectTitle=$url->hash}
                                
                                {event name='rowButtons'}
                            </td>
                            <td class="columnID">{#$url->linkID}</td>
                            <td class="columnTitle columnHash"><a href="{link controller='ShrinkrLinkEdit' application='shrinkr' object=$url}{/link}">{$url->hash}</a></td>
                            <td class="columnTitle columnUrl">
                            <button class="copyUrlButton" data-copy-link="{$url->getShortedUrl(true)}" data-tooltip="{lang}wcf.shrinkr.copyUrl{/lang}" aria-label="{lang}wcf.shrinkr.copyUrl{/lang}">{icon name='copy'}</button>
                            <kbd>{$url->getShortedUrl(true)}</kbd>
                            </td>
                            <td class="columnTitle columnUrlGoal"><kbd>{$url->url}</kbd></td>
                            {if SHRINKR_COUNTER_ACTIVE}
                                <td class="columnTitle columnCounter text-center">{$url->counter}</td>
                            {/if}
                            <td class="columnTitle columnQR text-center" data-url="{$url->getShortedUrl(true)}">
                                <button type="button" class="button qrDownloadLink">{icon name='download'}</button>
                            </td>
                            
                            {* URL List Columns (from template listeners) *}
                            <td class="columnTitle columnFeaturedLinks text-center">
                                {assign var="featuredLinksCount" value=0}
                                {if $linksArray|isset && $linksArray[$url->linkID]|isset && $linksArray[$url->linkID]['countFeaturedLinks']|isset}
                                    {assign var="featuredLinksCount" value=$linksArray[$url->linkID]['countFeaturedLinks']}
                                {/if}
                                {if $featuredLinksCount > 0}
                                    {* Featured Links vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
                                    {$featuredLinksCount} <a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$url->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {else}
                                    {* Keine Featured Links vorhanden: 0 + *}
                                    0 <a href="{link controller='FeaturedLinkAdd'}linkID={#$url->linkID}{/link}" title="{lang}wcf.shrinkr.featuredLink.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
                                {/if}
                            </td>
                            <td class="columnTitle columnSpecial text-center">
                                {if $linksArray|isset && $linksArray[$url->linkID]|isset && $linksArray[$url->linkID]['hasActiveSpecial']|isset && ($linksArray[$url->linkID]['hasActiveSpecial'] == true || $linksArray[$url->linkID]['hasActiveSpecial'] == 1)}
                                    {* Aktives Special vorhanden: Status + Bearbeiten-Button *}
                                    <div class="text-center specialBadgeContainer">
                                        <span class="badge green">{lang}wcf.shrinkr.special.status.active{/lang}</span>
                                        {if $linksArray[$url->linkID]['firstActiveSpecialID']|isset && $linksArray[$url->linkID]['firstActiveSpecialID']}
                                            <a href="{link controller='SpecialEdit' application='shrinkr' id=$linksArray[$url->linkID]['firstActiveSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                        {/if}
                                    </div>
                                {elseif $linksArray|isset && $linksArray[$url->linkID]|isset && $linksArray[$url->linkID]['firstSpecialID']|isset && $linksArray[$url->linkID]['firstSpecialID']}
                                    {* Inaktives Special vorhanden: Inaktiv Badge + Bearbeiten-Button *}
                                    <div class="text-center specialBadgeContainer">
                                        <span class="badge red">{lang}wcf.shrinkr.special.status.inactive{/lang}</span>
                                        <a href="{link controller='SpecialEdit' application='shrinkr' id=$linksArray[$url->linkID]['firstSpecialID']}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                    </div>
                                {else}
                                    {* Kein Special vorhanden: 0 + *}
                                    0 <a href="{link controller='SpecialAdd'}linkID={#$url->linkID}{/link}" title="{lang}wcf.shrinkr.special.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
                                {/if}
                            </td>
                            <td class="columnTitle columnCustomButtons text-center">
                                {assign var="customButtonsCount" value=0}
                                {if $linksArray|isset && $linksArray[$url->linkID]|isset && $linksArray[$url->linkID]['countCustomButtons']|isset}
                                    {assign var="customButtonsCount" value=$linksArray[$url->linkID]['countCustomButtons']}
                                {/if}
                                {if $customButtonsCount > 0}
                                    {* Custom Buttons vorhanden: Anzahl + Bearbeiten-Button (zur URL-Edit-Seite) *}
                                    {$customButtonsCount} <a href="{link controller='ShrinkrLinkEdit' application='shrinkr' id=$url->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
                                {else}
                                    {* Keine Custom Buttons vorhanden: 0 + *}
                                    0 <a href="{link controller='CustomButtonAdd'}linkID={#$url->linkID}{/link}" title="{lang}wcf.shrinkr.customButton.add{/lang}" class="jsTooltip">{icon name='plus'}</a>
                                {/if}
                            </td>
                            {if MODULE_LIKE && $__wcf->session->getPermission('user.like.canViewLike')}
                                <td class="columnTitle text-center">
                                    {assign var='__reactionSummaryJson' value='[]'}
                                    {assign var='__hasReactions' value=false}
                                    {if $reactionData|isset && $reactionData[$url->linkID]|isset}
                                        {assign var='__reactionSummaryJson' value=$reactionData[$url->linkID]->getReactionsJson()}
                                        {if $reactionData[$url->linkID]->cumulativeLikes > 0}
                                            {assign var='__hasReactions' value=true}
                                        {/if}
                                    {/if}
                                    {if $__hasReactions}
                                    <woltlab-core-reaction-summary
                                        data="{$__reactionSummaryJson}"
                                        object-type="{$reactionObjectType}"
                                        object-id="{#$url->linkID}"
                                        selected-reaction="{if $reactionData|isset && $reactionData[$url->linkID]|isset && $reactionData[$url->linkID]->reactionTypeID}{#$reactionData[$url->linkID]->reactionTypeID}{else}0{/if}"
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
                    <li><a href="{link controller='ShrinkrLinkAdd'}{/link}" class="button">{icon name='plus'} <span>{lang}shrinkr.acp.menu.link.link.add{/lang}</span></a></li>
                    
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

                // Handle both default export and direct export
                const qrModule = Qr && Qr.default ? Qr.default : Qr;
                if (qrModule && typeof qrModule.renderAll === 'function') {
                    qrModule.renderAll();
                }
            });
        </script>
    {else}
        <p class="info">{lang}wcf.global.noItems{/lang}</p>
    {/if}
{else}
    <p class="error">{lang}wcf.shrinkr.notActive{/lang}</p>
{/if}

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
