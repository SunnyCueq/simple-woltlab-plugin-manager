<div class="shrinkrContainer">
	<div class="shrinkrBox">
		<h1>
            {hascontent}
                {content}
                    {* Title Icon *}
                    {if $titleIconName && $titleIconName|trim && $titleIconName != ';'}
                        <span class="titleIcon">
                            {if $titleIconForceSolid}
                                {icon name=$titleIconName type='solid'}
                            {else}
                                {icon name=$titleIconName}
                            {/if}
                        </span>
                    {/if}
                    
                    {* URL Title Show In Template *}
                    {if $activeThemeIdentifier|isset && $activeThemeIdentifier === 'blackweek'}
                        {* Black Week Theme: Apply glitch effect *}
                        {if $link->linkTitle}
                            {assign var="titleText" value=$link->linkTitle}
                        {elseif $extractedTitle|isset}
                            {assign var="titleText" value=$extractedTitle}
                        {else}
                            {assign var="titleText" value=""}
                        {/if}
                        {if $titleText}
                            <span class="deal-bw__glitch" data-text="{$titleText}">{$titleText}</span>
                        {/if}
                    {else}
                        {* Normal theme: Plain text *}
                        {if $link->linkTitle}{unsafe:$link->linkTitle}{elseif $extractedTitle|isset}{$extractedTitle}{/if}
                    {/if}
                    
                    {event name='redirectTitle'}
                {/content}
            {hascontentelse}
                {lang}shrinkr.redirect.headline{/lang}
            {/hascontent}
        </h1>
		<p>
            {hascontent}
                {content}
                    {* Featured Links Descriptions *}
                    {if $enableDescriptions && $randomDescription|isset}{unsafe:$randomDescription}{else}{* Prevent empty paragraph *}{/if}
                    
                    {event name='redirectDescription'}
                {/content}
            {hascontentelse}
                {lang}shrinkr.redirect.text.{if SHRINKR_FORWARDING_MUST_CONFIRMED}confirm{else}timer{/if}{/lang}
            {/hascontent}
        </p>
		{if SHRINKR_FORWARDING_MUST_CONFIRMED}
			<div class="buttons">
				<a href="{$link->url}" id="forwardButton" class="button buttonPrimary{if SHRINKR_TIME_UNTIL_FORWARDING > 0} disabled{/if}">
					{if SHRINKR_TIME_UNTIL_FORWARDING == 1}
						{lang}shrinkr.redirect.forwardingIn{/lang} {SHRINKR_TIME_UNTIL_FORWARDING} {lang}shrinkr.redirect.second{/lang}
					{elseif SHRINKR_TIME_UNTIL_FORWARDING > 1}
						{lang}shrinkr.redirect.forwardingIn{/lang} {SHRINKR_TIME_UNTIL_FORWARDING} {lang}shrinkr.redirect.seconds{/lang}
					{else}
						{lang}shrinkr.redirect.confirm{/lang}
					{/if}
				</a>
			</div>
		{else}
			<div class="timerContainer">
				<a id="timer">
					{if SHRINKR_TIME_UNTIL_FORWARDING == 1}
						{lang}shrinkr.redirect.forwardingIn{/lang} {SHRINKR_TIME_UNTIL_FORWARDING} {lang}shrinkr.redirect.second{/lang}
					{elseif SHRINKR_TIME_UNTIL_FORWARDING > 1}
						{lang}shrinkr.redirect.forwardingIn{/lang} {SHRINKR_TIME_UNTIL_FORWARDING} {lang}shrinkr.redirect.seconds{/lang}
					{else}
						{lang}shrinkr.redirect.forwardingNow{/lang}
					{/if}
				</a>
			</div>
		{/if}

        {* Custom Buttons - Zuerst *}
        {if $customButtons|count > 0}
            <div class="customButtonsContainer">
                {event name='customButtonsBefore'}
                <ul class="buttonGroup customButtonsList">
                    {foreach from=$customButtons item=buttonData}
                        {event name='customButtonItem'}
                        <li>
                            <a class="button small customButton" href="{$buttonData.targetUrl}" rel="noopener" aria-label="{$buttonData.title}" data-button-id="{$buttonData.customButtonID}" data-url-id="{if $link|isset && $link->linkID|isset}{$link->linkID}{/if}">
                                <span>{$buttonData.title}</span>
                            </a>
                        </li>
                        {event name='customButtonItemAfter'}
                    {/foreach}
                    {event name='customButtonsList'}
                </ul>
                {event name='customButtonsAfter'}
            </div>
            
            {* Track custom button clicks *}
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
                
                // Add click tracking to all custom buttons
                document.addEventListener('DOMContentLoaded', function() {
                    const customButtons = document.querySelectorAll('.customButton');
                    customButtons.forEach(function(button) {
                        button.addEventListener('click', function(e) {
                            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                                return;
                            }
                            e.preventDefault();
                            const linkID = parseInt(this.getAttribute('data-url-id')) || 0;
                            const buttonID = parseInt(this.getAttribute('data-button-id')) || null;
                            if (linkID > 0) {
                                trackButtonClick(linkID, 'custom', buttonID);
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

        {* Featured Links Additional Text *}
        {if $discount && $discount->additionalText}
            <div class="additionalTextContainer">
                <div class="htmlContent">
                    {unsafe:$discount->additionalText}
                </div>
            </div>
        {/if}

        {* Featured Links Codes *}
        {if $discount && $discount->hasValidCodes()}
            <div class="discountCodesContainer warning">
                <strong>{#$discount->getCodesCount()} {$discount->getCodesLabel()}</strong>
                {* Loop through all discount codes with their labels *}
                {foreach from=$discount->getCodesList() item=codeData}
                        <p>
                        <small>{$codeData.label}</small>
                        <kbd class="copyableCode" data-code="{$codeData.code}">{$codeData.code}</kbd>
                        <button class="copyUrlButton" data-copy-link="{$codeData.code}"
                                data-tooltip="{lang}wcf.shrinkr.copySpecialData{/lang}"
                                aria-label="{lang}wcf.shrinkr.copySpecialData{/lang}">
                                {icon name='copy'}
                            </button>
                        </p>
                    {/foreach}
            </div>

            {* Initialize copy functionality for discount codes *}
            <script data-relocate="true">
                require(["Shrinkr/Ui/CopyLinkButton", "Shrinkr/Ui/DiscountCodes", "Language"],
                (CopyLinkButton, DiscountCodes, Language) => {
                    Language.addObject({
                        'wcf.shrinkr.copyUrl.success': '{jslang}wcf.shrinkr.copyCode.success{/jslang}',
                        'wcf.shrinkr.copyUrl.error': '{jslang}wcf.shrinkr.copyCode.error{/jslang}'
                    });

                    CopyLinkButton.setup();
                    DiscountCodes.setup();
                });
            </script>
        {/if}

        {* Forward Button Tracking - Always load tracking for forward button (works for both 3D and standard button) *}
        <script data-relocate="true">
        (function() {
            // Track button click function (shared with __forwardButton.tpl)
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
            
            function attachStandardButtonTracking() {
                const button = document.getElementById('forwardButton');
                if (!button) return;
                
                // Skip if button was already enhanced (3D button has its own tracking)
                if (button.classList.contains('forwardButtonEnhanced')) {
                    return;
                }
                
                // Check if tracking is already attached
                if (button.hasAttribute('data-tracking-attached')) {
                    return;
                }
                
                // Mark as tracked
                button.setAttribute('data-tracking-attached', 'true');
                
                // Get button URL
                const buttonUrl = button.href || button.getAttribute('href') || '#';
                
                // Attach click handler
                button.addEventListener('click', function(event) {
                    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }
                    
                    {if $link|isset && $link->linkID|isset}
                    event.preventDefault();
                    trackButtonClick({$link->linkID}, 'forward');
                    setTimeout(function() {
                        window.location.href = buttonUrl;
                    }, 120);
                    {/if}
                }, { once: false });
            }
            
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(attachStandardButtonTracking, 100);
                });
            } else {
                setTimeout(attachStandardButtonTracking, 100);
            }
            
            // Also try after a delay (in case button loads later)
            setTimeout(attachStandardButtonTracking, 500);
        })();
        </script>

        {* Enhance Forward Button *}
        {if SHRINKR_ENABLE_CUSTOM_FORWARD_BUTTON}
        {* Enhanced forward button with 3D effect *}
        <script data-relocate="true">
        (function() {
            let enhanced = false;

            function enhanceForwardButton() {
                const button = document.getElementById('forwardButton');
                if (!button || enhanced) return;
                
                // Get button text and URL
                const buttonText = button.textContent ? button.textContent.trim() : (button.innerText ? button.innerText.trim() : 'Empfehlung ansehen');
                const buttonUrl = button.href || button.getAttribute('href') || '#';
                
                // Get colors from discount badge (spiegelverkehrt - mirrored)
                // Badge uses: primaryColor (left) -> secondaryColor (right)
                // Button uses: secondaryColor (left) -> primaryColor (right) - spiegelverkehrt
                const discountElement = document.querySelector('.badge-promo');
                let buttonLeft = '#e94057';   // Default: Will be badge's secondaryColor (left)
                let buttonRight = '#f27121';  // Default: Will be badge's primaryColor (right)
                
                if (discountElement) {
                    const computedStyle = window.getComputedStyle(discountElement);
                    let badgePrimary = computedStyle.getPropertyValue('--badge-primary-bg').trim();
                    let badgeSecondary = computedStyle.getPropertyValue('--badge-secondary-bg').trim();
                    
                    // If CSS variables are not set, try to get them from the inline style attribute
                    if (!badgePrimary || badgePrimary === '') {
                        const inlineStyle = discountElement.getAttribute('style') || '';
                        const primaryMatch = inlineStyle.match(/--badge-primary-bg:\s*([^;]+)/);
                        if (primaryMatch) {
                            badgePrimary = primaryMatch[1].trim();
                        }
                    }
                    if (!badgeSecondary || badgeSecondary === '') {
                        const inlineStyle = discountElement.getAttribute('style') || '';
                        const secondaryMatch = inlineStyle.match(/--badge-secondary-bg:\s*([^;]+)/);
                        if (secondaryMatch) {
                            badgeSecondary = secondaryMatch[1].trim();
                        }
                    }
                    
                    // Mirror colors: badge's secondary (right) becomes button's left, badge's primary (left) becomes button's right
                    // Accept CSS variables (starting with 'var(') or any non-empty value that's not the old default white
                    if (badgeSecondary && badgeSecondary !== '') {
                        // Always use if it's a CSS variable, or if it's not the old default white
                        if (badgeSecondary.startsWith('var(') || badgeSecondary !== 'rgba(255, 255, 255, 1)') {
                            buttonLeft = badgeSecondary; // Badge's right color -> Button's left
                        }
                    }
                    if (badgePrimary && badgePrimary !== '') {
                        // Always use if it's a CSS variable, or if it's not the old default white
                        if (badgePrimary.startsWith('var(') || badgePrimary !== 'rgba(255, 255, 255, 1)') {
                            buttonRight = badgePrimary; // Badge's left color -> Button's right
                        }
                    }
                }
                
                // Create new button structure
                const newButton = document.createElement('a');
                newButton.id = 'forwardButton';
                newButton.href = buttonUrl;
                newButton.className = 'forwardButtonEnhanced';
                newButton.style.setProperty('--forward-button-left', buttonLeft);
                newButton.style.setProperty('--forward-button-right', buttonRight);
                
                // Create DOM elements instead of using template strings
                const shadow = document.createElement('span');
                shadow.className = 'forwardButtonEnhanced__shadow';
                
                const content = document.createElement('div');
                content.className = 'forwardButtonEnhanced__content';
                
                const text = document.createElement('span');
                text.className = 'forwardButtonEnhanced__text';
                text.textContent = buttonText;
                
                const icon = document.createElement('fa-icon');
                icon.setAttribute('name', 'arrow-right');
                icon.setAttribute('size', '16');
                icon.className = 'forwardButtonEnhanced__icon';
                
                content.appendChild(text);
                content.appendChild(icon);
                
                newButton.appendChild(shadow);
                newButton.appendChild(content);
                
                // Replace old button
                button.parentNode.replaceChild(newButton, button);

                {if $link|isset && $link->linkID|isset}
                newButton.addEventListener('click', function(event) {
                    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }
                    event.preventDefault();
                    trackButtonClick({$link->linkID}, 'forward');
                    setTimeout(function() {
                        window.location.href = buttonUrl;
                    }, 120);
                }, { once: false });
                {/if}

                enhanced = true;
            }
            
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
            
            // Wait for DOM and discount badge to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(enhanceForwardButton, 100);
                });
            } else {
                setTimeout(enhanceForwardButton, 100);
            }
            
            // Also try after a delay (in case discount badge loads later)
            setTimeout(enhanceForwardButton, 500);
        })();
        </script>
        {/if}

        {* Reaction Button wird über Template Listener eingefügt (vor Copyright) *}

        {event name='beforeRedirectCopyright'}

	</div>
</div>


{if SHRINKR_TIME_UNTIL_FORWARDING > 0}
<script>
	var seconds = {SHRINKR_TIME_UNTIL_FORWARDING};
	
	{if SHRINKR_FORWARDING_MUST_CONFIRMED}
		var forwardButton = document.getElementById('forwardButton');
		var countdown = setInterval(function() {
			seconds = seconds - 1;
			
			if(seconds <= 0) {
				forwardButton.innerHTML = "{lang}shrinkr.redirect.confirm{/lang}";
				forwardButton.classList.remove("disabled");
				clearInterval(countdown);
			} else if(seconds == 1) {
				forwardButton.innerHTML = "{lang}shrinkr.redirect.forwardingIn{/lang} " + seconds + " {lang}shrinkr.redirect.second{/lang}";
			} else {
				forwardButton.innerHTML = "{lang}shrinkr.redirect.forwardingIn{/lang} " + seconds + " {lang}shrinkr.redirect.seconds{/lang}";
			}
		}, 1000);
	{else}
		var timer = document.getElementById('timer');
		var countdown = setInterval(function() {
			seconds = seconds - 1;
			
			if(seconds <= 0) {
				timer.innerHTML = "{lang}shrinkr.redirect.forwardingNow{/lang}";
				window.location.href = "{$link->url}";
				clearInterval(countdown);
			} else if(seconds == 1) {
				timer.innerHTML = "{lang}shrinkr.redirect.forwardingIn{/lang} " + seconds + " {lang}shrinkr.redirect.second{/lang}";
			} else {
				timer.innerHTML = "{lang}shrinkr.redirect.forwardingIn{/lang} " + seconds + " {lang}shrinkr.redirect.seconds{/lang}";
			}
		}, 1000);
	{/if}
</script>
{/if}
