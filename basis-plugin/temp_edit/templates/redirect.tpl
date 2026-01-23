{*
 * Template-Zweck: Haupt-Redirect-Seite für verkürzte URLs
 * 
 * Haupttemplate für die Anzeige von verkürzten URLs. Zeigt Titel, Beschreibung,
 * Countdown-Timer, Forward-Button, Custom Buttons, Featured Links, Discount Codes,
 * Reaction Buttons und Copyright an. Unterstützt verschiedene Themes und Modi
 * (Normal Page Mode vs. Minimal Mode). Enthält JavaScript für Countdown, Button
 * Tracking, Copy-Funktionalität und 3D-Button-Enhancement.
 * 
 * Variablen:
 * @var \shrinkr\data\shrinkrlink\ShrinkrLink $link - Link-Objekt mit URL-Daten
 * @var string $extractedTitle - Extrahierter oder auto-generierter Seitentitel
 * @var string $activeThemeIdentifier - Theme-Identifier (z.B. 'blackweek', 'halloween')
 * @var string $titleIconName - Font Awesome Icon-Name für Titel
 * @var bool $titleIconForceSolid - Ob Solid-Icon verwendet werden soll
 * @var bool $enableDescriptions - Ob Beschreibungen aktiviert sind
 * @var string $randomDescription - Zufällige Beschreibung für diese URL
 * @var array $customButtons - Array von Custom Button-Objekten
 * @var \shrinkr\data\discount\Discount $discount - Discount-Objekt (falls vorhanden)
 * @var string $canonicalURL - Canonical URL für SEO
 * @var string $headContent - Zusätzlicher Head-Content
 * @var string $pageTitle - Seitentitel
 * @var bool SHRINKR_NORMAL_PAGE_MODE - Ob Normal Page Mode aktiv ist
 * @var bool SHRINKR_FORWARDING_MUST_CONFIRMED - Ob Bestätigung erforderlich ist
 * @var int SHRINKR_TIME_UNTIL_FORWARDING - Sekunden bis zur Weiterleitung
 * @var bool SHRINKR_ENABLE_CUSTOM_FORWARD_BUTTON - Ob 3D-Button aktiviert ist
 * 
 * Logik:
 * - Erstellt Dummy-DOM-Elemente für WoltLab JavaScript (wenn Normal Page Mode)
 * - Lädt Theme-spezifisches CSS
 * - Zeigt Titel mit optionalem Icon und Glitch-Effekt (Black Week Theme)
 * - Zeigt Beschreibung oder Standard-Text
 * - Zeigt Forward-Button oder Countdown-Timer (je nach Konfiguration)
 * - Zeigt Custom Buttons mit Click-Tracking
 * - Zeigt Discount Codes mit Copy-Funktionalität
 * - Initialisiert JavaScript für Countdown, Tracking und Button-Enhancement
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
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
	
	<style>
		woltlab-core-dialog[data-dialog-id*="shrinkr"] button[aria-label*="Schließen" i],
		woltlab-core-dialog[data-dialog-id*="shrinkr"] button[aria-label*="Abbrechen" i],
		woltlab-core-dialog[data-dialog-id*="shrinkr"] .dialogClose,
		woltlab-core-dialog[data-dialog-id*="shrinkr"] button:not([data-dialog-button="primary"]):not([type="submit"]) {
			display: none !important;
			visibility: hidden !important;
			pointer-events: none !important;
		}
		
		/* Passwort-Dialog Styling */
		woltlab-core-dialog[data-dialog-id*="shrinkr"] {
			z-index: 9999 !important;
		}
		
		woltlab-core-dialog[data-dialog-id*="shrinkr"] .dialog__content {
			min-width: 400px;
		}
	</style>
</head>

<body id="tpl_{$templateNameApplication}_{$templateName}"
	itemscope itemtype="http://schema.org/WebPage"{if !$canonicalURL|empty} itemid="{$canonicalURL}"{/if}
	data-template="{$templateName}" data-application="{$templateNameApplication}"{if $__wcf->getActivePage() != null} data-page-id="{unsafe:$__wcf->getActivePage()->pageID}" data-page-identifier="{$__wcf->getActivePage()->identifier}"{/if}
	{if !$__pageDataAttributes|empty}{unsafe:$__pageDataAttributes}{/if}
	class="{if $__wcf->getActivePage() != null && $__wcf->getActivePage()->cssClassName}{$__wcf->getActivePage()->cssClassName}{/if}{if !$__pageCssClassName|empty} {$__pageCssClassName}{/if}">

    <span id="top"></span>

    <div id="pageContainer" class="pageContainer"{if $link && $link->linkID} data-link-id="{$link->linkID}"{/if}>
        {if SHRINKR_NORMAL_PAGE_MODE}
            {* Fix Missing DOM Elements - Zuerst *}
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

            {* Theme Stylesheet *}
            {if $activeThemeIdentifier|isset && $activeThemeIdentifier}
                {* Load theme-specific CSS file *}
                <link rel="stylesheet" type="text/css" href="{unsafe:$__wcf->getPath('shrinkr')}style/themes/{$activeThemeIdentifier}.css" />
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
                {if SHRINKR_NORMAL_PAGE_MODE}
                    {hascontent}
                        {if !$__sidebarLeftShow|isset}{assign var='__sidebarLeftShow' value='wcf.global.button.showSidebarLeft'|phrase}{/if}
                        {if !$__sidebarLeftHide|isset}{assign var='__sidebarLeftHide' value='wcf.global.button.hideSidebar'|phrase}{/if}
                        
                        <aside class="sidebar boxesSidebarLeft{if !$__sidebarLeftHasMenu|empty || $__wcf->getBoxHandler()->sidebarLeftHasMenu()} boxesSidebarLeftHasMenu{/if}" aria-label="{lang}wcf.page.sidebar.left{/lang}" data-show-sidebar="{$__sidebarLeftShow}" data-hide-sidebar="{$__sidebarLeftHide}" data-show-navigation="{lang}wcf.global.button.showNavigation{/lang}" data-hide-navigation="{lang}wcf.global.button.hideNavigation{/lang}">
                            <div class="boxContainer">
                                {content}
                                    {event name='boxesSidebarLeftTop'}
                                    
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
                    {if SHRINKR_NORMAL_PAGE_MODE}
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
                        {unsafe:$__wcf->getAdHandler()->getAds('de.sunnyc.wsc.shrinkr.beforeRedirectContainer')}
                    {/if}

                    {* Featured Links Promo Badge *}
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
                                require(["Shrinkr/DiscountCountdown"], function(DiscountCountdown) {
                                    DiscountCountdown.init("discount-countdown", {unsafe:$countdownSeconds});
                                });
                            </script>
                        {elseif $discount && $countdownSeconds|isset}
                            <span class="badge-promo-content badge-promo-right badge">{lang}wcf.shrinkr.countdown.expired{/lang}</span>
                        {/if}
                    </div>

                    {event name='beforeRedirectContainer'}

                    {include application="shrinkr" file='__redirectContainer'}

                    {* Featured Links *}
                    {if $featuredLinks|count > 0}
                        <dl class="featuredLinksContainer">
                            {event name='featuredLinksBefore'}
                            <dt class="featuredLinksContainer__header">
                                <div class="featuredLinksHeadline">
                                    <span class="featuredLinksHeadline__icon" aria-hidden="true">
                                        {icon size=16 name="star"}
                                    </span>
                                    <h2 class="featuredLinksHeadline__title">{lang}shrinkr.featured.texts.recommended{/lang}</h2>
                                </div>
                            </dt>
                            <dd class="featuredLinksContainer__description small">
                                {lang}shrinkr.featured.texts.discount{/lang}
                            </dd>
                            <dd id="sectionResult" class="featuredLinksContainer__content">
                                <ul class="containerList featuredLinksList">
                                    <li class="containerListButtonGroup featuredLinksButtonGroup">
                                        <ul class="buttonGroup">
                                    {* Loop through all featured links *}
                                    {foreach from=$featuredLinks key=featuredLink item=linkData}
                                        {event name='featuredLinkItem'}
                                                <li>
                                                    <a class="button small featuredLinkButton" href="{$featuredLink}" rel="noopener" aria-label="{$linkData.title}" data-link-id="{if $linkData.linkID|isset}{$linkData.linkID}{/if}" data-url-id="{if $link|isset && $link->linkID|isset}{$link->linkID}{/if}">
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
                        {if $link|isset && $link->linkID|isset}
                        <script data-relocate="true">
                        (function() {
                            // Track button click function
                            function trackButtonClick(linkID, buttonType, linkID) {
                                if (!linkID) return;
                                
                                if (typeof require !== 'undefined') {
                                    require(['WoltLabSuite/Core/Ajax'], function(Ajax) {
                                        Ajax.apiOnce({
                                            data: {
                                                actionName: 'trackClick',
                                                className: 'shrinkr\\data\\buttonclick\\ButtonClickAction',
                                                parameters: {
                                                    linkID: linkID,
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
                                        const linkID = parseInt(this.getAttribute('data-link-id')) || 0;
                                        if (linkID > 0) {
                                            trackButtonClick(linkID, 'featured_link', linkID);
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

                    {* Theme Effects - Zuletzt *}
                    {* Theme effects template *}
                    {if $activeThemeEffect|isset && $activeThemeEffect.identifier !== 'none'}
                        {if $activeThemeEffect.identifier === 'autumnLeaves'}
                            {include file='__effectAutumnLeaves' application='shrinkr' effect=$activeThemeEffect.settings}
                        {elseif $activeThemeEffect.identifier === 'snow'}
                            {include file='__effectSnow' application='shrinkr' effect=$activeThemeEffect.settings}
                        {elseif $activeThemeEffect.identifier === 'ghosts'}
                            {include file='__effectGhosts' application='shrinkr' effect=$activeThemeEffect.settings}
                        {/if}
                        
                        {* Load additional effects (e.g. autumn leaves for Halloween) *}
                        {if $activeThemeEffect.additionalEffects|isset}
                            {foreach from=$activeThemeEffect.additionalEffects item=additionalEffect}
                                {if $additionalEffect.identifier === 'autumnLeaves'}
                                    {include file='__effectAutumnLeaves' application='shrinkr' effect=$additionalEffect.settings}
                                {elseif $additionalEffect.identifier === 'snow'}
                                    {include file='__effectSnow' application='shrinkr' effect=$additionalEffect.settings}
                                {elseif $additionalEffect.identifier === 'ghosts'}
                                    {include file='__effectGhosts' application='shrinkr' effect=$additionalEffect.settings}
                                {/if}
                            {/foreach}
                        {/if}
                    {/if}

                    {event name='afterRedirectContainer'}

                    {if MODULE_WCF_AD && $__disableAds|empty}
                        {unsafe:$__wcf->getAdHandler()->getAds('de.sunnyc.wsc.shrinkr.afterRedirectContainer')}
                    {/if}
                    
                    {if SHRINKR_NORMAL_PAGE_MODE}
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
