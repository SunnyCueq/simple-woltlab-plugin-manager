{include file='documentHeader'}

<head>
	{if !$pageTitle|isset}
		{assign var='pageTitle' value=''}
		{if (!$__wcf->isLandingPage() || !USE_PAGE_TITLE_ON_LANDING_PAGE) && $__wcf->getActivePage() != null && $__wcf->getActivePage()->getTitle()}
			{capture assign='pageTitle'}{$__wcf->getActivePage()->getTitle()}{/capture}
		{/if}
	{/if}
	
	<title>{if $pageTitle}{unsafe:$pageTitle} - {/if}{PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
	
	{if !$canonicalURL|empty}
		<link rel="canonical" href="{$canonicalURL}">
	{/if}
	
	{if !$headContent|empty}
		{unsafe:$headContent}
	{/if}
</head>

<body id="tpl_{$templateNameApplication}_{$templateName}"
	itemscope itemtype="http://schema.org/WebPage"{if !$canonicalURL|empty} itemid="{$canonicalURL}"{/if}
	data-template="{$templateName}" data-application="{$templateNameApplication}"{if $__wcf->getActivePage() != null} data-page-id="{unsafe:$__wcf->getActivePage()->pageID}" data-page-identifier="{$__wcf->getActivePage()->identifier}"{/if}
	{if !$__pageDataAttributes|empty}{unsafe:$__pageDataAttributes}{/if}
	class="{if $__wcf->getActivePage() != null && $__wcf->getActivePage()->cssClassName}{$__wcf->getActivePage()->cssClassName}{/if}{if !$__pageCssClassName|empty} {$__pageCssClassName}{/if}">

    <span id="top"></span>

    <div id="pageContainer" class="pageContainer">
        {if URLSHORT_NORMAL_PAGE_MODE}
            {* Fix Missing DOM Elements (nice: -1000) - Zuerst *}
            {* Vollständige Dummy-Struktur für WoltLab's JavaScript *}
            {* Versteckt, aber vollständig, um JavaScript-Fehler zu vermeiden *}
            <div id="mainMenu" class="mainMenu" style="display: none !important; visibility: hidden; position: absolute; left: -9999px;" aria-hidden="true">
                <button type="button" class="pageHeaderMenuMobile" id="pageHeaderMenuMobile" aria-hidden="true">
                    <fa-icon size="24" name="bars"></fa-icon>
                </button>
                <nav>
                    <ol class="boxMenu">
                        <li class="boxMenuLink">
                            <a href="#" class="boxMenuLink" data-object-id="0" data-menu-item-id="0">
                                <fa-icon size="16" name="house"></fa-icon>
                                <span class="boxMenuLinkTitle">Home</span>
                            </a>
                        </li>
                    </ol>
                </nav>
            </div>
            <div id="pageHeaderSearch" class="pageHeaderSearch" style="display: none !important; visibility: hidden; position: absolute; left: -9999px;" aria-hidden="true">
                <div class="pageHeaderSearchInputContainer">
                    <div class="pageHeaderSearchType dropdown">
                        <a href="#" class="button dropdownToggle" id="pageHeaderSearchTypeSelect"><span>Suche</span></a>
                        <ul class="dropdownMenu"></ul>
                    </div>
                    <input type="search" name="q" id="pageHeaderSearchInput" class="pageHeaderSearchInput" placeholder="Suche" aria-label="Suche">
                </div>
            </div>
            <nav id="topMenu" class="userPanel" style="display: none !important; visibility: hidden; position: absolute; left: -9999px;" aria-hidden="true">
                <ul class="userPanelItems">
                    <li><a href="#" id="userPanelSearchButton"><fa-icon size="16" name="magnifying-glass"></fa-icon><span>Suche</span></a></li>
                </ul>
            </nav>

            {* Theme Stylesheet (nice: -50) *}
            {if $activeThemeIdentifier|isset && $activeThemeIdentifier}
                {* Load theme-specific CSS file *}
                <link rel="stylesheet" type="text/css" href="{unsafe:$__wcf->getPath('urlshort')}style/themes/{$activeThemeIdentifier}.css" />
            {/if}

            {event name='beforePageHeader'}
            
            <div id="pageHeaderContainer" class="pageHeaderContainer">
                <header id="pageHeader" class="pageHeader">                
                    <div id="pageHeaderFacade" class="pageHeaderFacade">
                        <div class="layoutBoundary">
                            {include file='pageHeaderLogo'}
                        </div>
                    </div>
                </header>
                
                {hascontent}
                    <div class="boxesHero">
                        <div class="layoutBoundary">
                            <div class="boxContainer">
                                {content}
                                    {if !$boxesHero|empty}
                                        {unsafe:$boxesHero}
                                    {/if}

                                    {foreach from=$__wcf->getBoxHandler()->getBoxes('hero') item=box}
                                        {unsafe:$box->render()}
                                    {/foreach}
                                {/content}
                            </div>
                        </div>
                    </div>
                {/hascontent}
            </div>

            
            {event name='afterPageHeader'}
        
            {hascontent}
                <div class="boxesHeaderBoxes">
                    <div class="layoutBoundary">
                        <div class="boxContainer">
                            {content}
                                {if !$headerBoxes|empty}
                                    {unsafe:$headerBoxes}
                                {/if}
                                
                                {foreach from=$__wcf->getBoxHandler()->getBoxes('headerBoxes') item=box}
                                    {unsafe:$box->render()}
                                {/foreach}
                            {/content}
                        </div>
                    </div>
                </div>
            {/hascontent}
            
            {include file='pageNavbarTop'}
            
            {hascontent}
                <div class="boxesTop">
                    <div class="boxContainer">
                        {content}
                            {if !$boxesTop|empty}
                                {unsafe:$boxesTop}
                            {/if}
                        
                            {foreach from=$__wcf->getBoxHandler()->getBoxes('top') item=box}
                                {unsafe:$box->render()}
                            {/foreach}
                        {/content}
                    </div>
                </div>
            {/hascontent}
        {/if}

        <section id="main" class="main" role="main"{if !$__mainItemScope|empty} {unsafe:$__mainItemScope}{/if}>
            <div class="layoutBoundary">
                {if URLSHORT_NORMAL_PAGE_MODE}
                    {hascontent}
                        {if !$__sidebarLeftShow|isset}{assign var='__sidebarLeftShow' value='wcf.global.button.showSidebarLeft'|phrase}{/if}
                        {if !$__sidebarLeftHide|isset}{assign var='__sidebarLeftHide' value='wcf.global.button.hideSidebar'|phrase}{/if}
                        
                        <aside class="sidebar boxesSidebarLeft{if !$__sidebarLeftHasMenu|empty || $__wcf->getBoxHandler()->sidebarLeftHasMenu()} boxesSidebarLeftHasMenu{/if}" aria-label="{lang}wcf.page.sidebar.left{/lang}" data-show-sidebar="{$__sidebarLeftShow}" data-hide-sidebar="{$__sidebarLeftHide}" data-show-navigation="{lang}wcf.global.button.showNavigation{/lang}" data-hide-navigation="{lang}wcf.global.button.hideNavigation{/lang}">
                            <div class="boxContainer">
                                {content}
                                    {event name='boxesSidebarLeftTop'}
                                    
                                    {* WCF2.1 Fallback *}
                                    {if !$sidebar|empty}
                                        {if !$sidebarOrientation|isset || $sidebarOrientation == 'left'}
                                            {unsafe:$sidebar}
                                        {/if}
                                    {/if}
                                    
                                    {if !$sidebarLeft|empty}
                                        {unsafe:$sidebarLeft}
                                    {/if}
                                    
                                    {foreach from=$__wcf->getBoxHandler()->getBoxes('sidebarLeft') item=box}
                                        {unsafe:$box->render()}
                                    {/foreach}
                                    
                                    {event name='boxesSidebarLeftBottom'}
                                {/content}
                            </div>
                        </aside>
                    {/hascontent}		
                    	
                    {capture assign='__sidebarRightContent'}
                        {if MODULE_WCF_AD && $__disableAds|empty && $__wcf->getAdHandler()->getAds('com.woltlab.wcf.sidebar.top')}
                            <div class="box boxBorderless">
                                <div class="boxContent">
                                    {unsafe:$__wcf->getAdHandler()->getAds('com.woltlab.wcf.sidebar.top')}
                                </div>
                            </div>
                        {/if}
                        
                        {event name='boxesSidebarRightTop'}
                        
                        {* WCF2.1 Fallback *}
                        {if !$sidebar|empty}
                            {if !$sidebarOrientation|isset || $sidebarOrientation == 'right'}
                                {unsafe:$sidebar}
                            {/if}
                        {/if}
                        
                        {if !$sidebarRight|empty}
                            {unsafe:$sidebarRight}
                        {/if}
                        
                        {foreach from=$__wcf->getBoxHandler()->getBoxes('sidebarRight') item=box}
                            {unsafe:$box->render()}
                        {/foreach}
                        
                        {event name='boxesSidebarRightBottom'}
        
                        {if MODULE_WCF_AD && $__disableAds|empty && $__wcf->getAdHandler()->getAds('com.woltlab.wcf.sidebar.bottom')}
                            <div class="box boxBorderless">
                                <div class="boxContent">
                                    {unsafe:$__wcf->getAdHandler()->getAds('com.woltlab.wcf.sidebar.bottom')}
                                </div>
                            </div>
                        {/if}
                    {/capture}
                {/if}
			
                <div id="content" class="content">
                    {if URLSHORT_NORMAL_PAGE_MODE}
                        {if MODULE_WCF_AD && $__disableAds|empty}{unsafe:$__wcf->getAdHandler()->getAds('com.woltlab.wcf.header.content')}{/if}
                        
                        {include file='userNotice'}
                        
                        {hascontent}
                            <div class="boxesContentTop">
                                <div class="boxContainer">
                                    {content}
                                        {if !$boxesContentTop|empty}
                                            {unsafe:$boxesContentTop}
                                        {/if}
                                        
                                        {foreach from=$__wcf->getBoxHandler()->getBoxes('contentTop') item=box}
                                            {unsafe:$box->render()}
                                        {/foreach}
                                    {/content}
                                </div>
                            </div>
                        {/hascontent}
                    {/if}

                    {include file='contentInteraction'}

                    {if MODULE_WCF_AD && $__disableAds|empty}
                        {unsafe:$__wcf->getAdHandler()->getAds('dev.tkirch.wsc.urlshort.beforeRedirectContainer')}
                    {/if}

                    {* Featured Links Promo Badge (nice: default 0) *}
                    {* Promotional badge template with discount value and countdown *}
                    {* Badge wird immer angezeigt, auch ohne Discount (leer mit Standardfarben) *}
                    <div class="badge-promo" style="
                        {if $discount}
                            {* Dynamic CSS variables from database - must remain as inline style *}
                            {* Set colors if they exist and are not the old default white (CSS variables always set) *}
                            {if $discount->primaryColor|isset && ($discount->primaryColor|str_starts_with:'var(' || $discount->primaryColor != 'rgba(255, 255, 255, 1)')}--badge-primary-bg: {$discount->primaryColor};{/if}
                            {if $discount->primaryTextColor|isset}--badge-primary-text: {$discount->primaryTextColor};{/if}
                            {if $discount->secondaryColor|isset && ($discount->secondaryColor|str_starts_with:'var(' || $discount->secondaryColor != 'rgba(255, 255, 255, 1)')}--badge-secondary-bg: {$discount->secondaryColor};{/if}
                            {if $discount->secondaryTextColor|isset}--badge-secondary-text: {$discount->secondaryTextColor};{/if}
                        {/if}
                    ">
                        {* Left: Discount value - nur anzeigen wenn Discount vorhanden *}
                        {if $discount}
                            <span class="badge-promo-content badge-promo-left" id="rabatt">
                                <strong class="rabatt-number">{$discount->getFormattedDiscountValue()}</strong>
                            </span>
                        {/if}
                        
                        {* Center: Theme name (NEW) *}
                        {if $specialThemeShortName|isset && $specialThemeShortName}
                            <span class="badge-promo-content badge-promo-center">
                                {$specialThemeShortName|strtoupper}
                            </span>
                        {/if}
                        
                        {* Right: Countdown display: active countdown, expired, or nothing *}
                        {if $discount && $countdownSeconds|isset && $countdownSeconds > 0}
                            <span class="badge-promo-content badge-promo-right badge" id="discount-countdown">
                                {* Initial value will be set by JavaScript *}
                            </span>
                            <script data-relocate="true">
                                require(["Benjaro/Urlshort/DiscountCountdown"], function(DiscountCountdown) {
                                    DiscountCountdown.init("discount-countdown", {unsafe:$countdownSeconds});
                                });
                            </script>
                        {elseif $discount && $countdownSeconds|isset}
                            <span class="badge-promo-content badge-promo-right badge">{lang}wcf.urlshort.countdown.expired{/lang}</span>
                        {/if}
                    </div>

                    {event name='beforeRedirectContainer'}

                    {include application="urlshort" file='__redirectContainer'}

                    {* Featured Links (nice: -1) *}
                    {if $featuredLinks|count > 0}
                        <dl class="featuredLinksContainer">
                            {event name='featuredLinksBefore'}
                            <dt class="featuredLinksContainer__header">
                                <div class="featuredLinksHeadline">
                                    <span class="featuredLinksHeadline__icon" aria-hidden="true">
                                        {icon size=16 name="star"}
                                    </span>
                                    <h2 class="featuredLinksHeadline__title">{lang}urlshort.featured.texts.recommended{/lang}</h2>
                                </div>
                            </dt>
                            <dd class="featuredLinksContainer__description small">
                                {lang}urlshort.featured.texts.discount{/lang}
                            </dd>
                            <dd id="sectionResult" class="featuredLinksContainer__content">
                                <ul class="containerList featuredLinksList">
                                    <li class="containerListButtonGroup featuredLinksButtonGroup">
                                        <ul class="buttonGroup">
                                    {* Loop through all featured links *}
                                    {foreach from=$featuredLinks key=featuredLink item=linkData}
                                        {event name='featuredLinkItem'}
                                                <li>
                                                    <a class="button small featuredLinkButton" href="{$featuredLink}" rel="noopener" aria-label="{$linkData.title}" data-link-id="{if $linkData.linkID|isset}{$linkData.linkID}{/if}" data-url-id="{if $url|isset && $url->urlID|isset}{$url->urlID}{/if}">
                                            <span class="badge badgeUpdate">{$linkData.host}</span>
                                                {icon size=16 name="star"} <span>{$linkData.title}</span>
                                            </a>
                                                </li>
                                        {event name='featuredLinkItemAfter'}
                                    {/foreach}
                                    {event name='featuredLinksList'}
                                        </ul>
                                    </li>
                                </ul>
                            </dd>
                            {event name='featuredLinksAfter'}
                        </dl>
                        
                        {* Track featured link clicks *}
                        {if $url|isset && $url->urlID|isset}
                        <script data-relocate="true">
                        (function() {
                            // Track button click function
                            function trackButtonClick(urlID, buttonType, linkID) {
                                if (!urlID) return;
                                
                                // Use WoltLab's Legacy AJAX API
                                // https://docs.woltlab.com/6.0/javascript/new-api_ajax/
                                if (typeof require !== 'undefined') {
                                    require(['Ajax'], function(Ajax) {
                                        Ajax.apiOnce({
                                            data: {
                                                actionName: 'trackClick',
                                                className: 'urlshort\\data\\buttonclick\\ButtonClickAction',
                                                parameters: {
                                                    urlID: urlID,
                                                    buttonType: buttonType,
                                                    linkID: linkID || null
                                                }
                                            },
                                            silent: true,
                                            ignoreError: true
                                        });
                                    });
                                }
                            }
                            
                            // Add click tracking to all featured link buttons
                            document.addEventListener('DOMContentLoaded', function() {
                                const featuredLinkButtons = document.querySelectorAll('.featuredLinkButton');
                                featuredLinkButtons.forEach(function(button) {
                                    button.addEventListener('click', function(e) {
                                        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                                            return;
                                        }
                                        e.preventDefault();
                                        const urlID = parseInt(this.getAttribute('data-url-id')) || 0;
                                        const linkID = parseInt(this.getAttribute('data-link-id')) || null;
                                        if (urlID > 0) {
                                            trackButtonClick(urlID, 'featured_link', linkID);
                                        }
                                        const targetUrl = this.getAttribute('href') || '#';
                                        setTimeout(() => {
                                            window.location.href = targetUrl;
                                        }, 120);
                                    });
                                });
                            });
                        })();
                        </script>
                        {/if}
                    {/if}

                    {* Theme Effects (nice: 10) - Zuletzt *}
                    {* Theme effects template *}
                    {if $activeThemeEffect|isset && $activeThemeEffect.identifier !== 'none'}
                        {if $activeThemeEffect.identifier === 'autumnLeaves'}
                            {include file='__effectAutumnLeaves' application='urlshort' effect=$activeThemeEffect.settings}
                        {elseif $activeThemeEffect.identifier === 'snow'}
                            {include file='__effectSnow' application='urlshort' effect=$activeThemeEffect.settings}
                        {elseif $activeThemeEffect.identifier === 'ghosts'}
                            {include file='__effectGhosts' application='urlshort' effect=$activeThemeEffect.settings}
                        {/if}
                        
                        {* Load additional effects (e.g. autumn leaves for Halloween) *}
                        {if $activeThemeEffect.additionalEffects|isset}
                            {foreach from=$activeThemeEffect.additionalEffects item=additionalEffect}
                                {if $additionalEffect.identifier === 'autumnLeaves'}
                                    {include file='__effectAutumnLeaves' application='urlshort' effect=$additionalEffect.settings}
                                {elseif $additionalEffect.identifier === 'snow'}
                                    {include file='__effectSnow' application='urlshort' effect=$additionalEffect.settings}
                                {elseif $additionalEffect.identifier === 'ghosts'}
                                    {include file='__effectGhosts' application='urlshort' effect=$additionalEffect.settings}
                                {/if}
                            {/foreach}
                        {/if}
                    {/if}

                    {event name='afterRedirectContainer'}

                    {if MODULE_WCF_AD && $__disableAds|empty}
                        {unsafe:$__wcf->getAdHandler()->getAds('dev.tkirch.wsc.urlshort.afterRedirectContainer')}
                    {/if}
                    
                    {if URLSHORT_NORMAL_PAGE_MODE}
                        {include file='footer'}
                    {else}
                    <!-- {$__wcf->getRequestNonce('JAVASCRIPT_RELOCATE_POSITION')} -->
                </div>
            </div>
        </section>
    </div>

</body>
</html>
{/if}
