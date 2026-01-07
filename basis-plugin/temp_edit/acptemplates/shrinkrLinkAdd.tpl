{include file='header' pageTitle='shrinkr.acp.url.'|concat:$action}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}shrinkr.acp.url.{$action}{/lang}</h1>
	</div>
	
    {if SHRINKR_ACTIVE}
        <nav class="contentHeaderNavigation">
            <ul>
                {if $removeUrlsPrefix && !$htaccessRuleExists && $detectedWebserver}
                    <li><button type="button" class="button buttonPrimary jsButtonGenerateRewriteRules">{icon name='code'} <span>{lang}shrinkr.acp.url.removeUrlsPrefix.info.htaccess.generate{/lang}</span></button></li>
                {/if}
                <li><a href="{link application='shrinkr' controller='ShrinkrLinkList'}{/link}" class="button">{icon name='list'} <span>{lang}shrinkr.acp.menu.link.url.list{/lang}</span></a></li>
                
                {* URL Edit Special Hint (from template listener) *}
                {if $action == 'edit' && $linkID|isset}
                    {* Featured Link hinzufügen - immer oben in Navigation *}
                    {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                        <li><a href="{link controller='FeaturedLinkAdd' application='shrinkr'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.featuredLink.addAnother{/lang}</span></a></li>
                    {else}
                        <li><a href="{link controller='FeaturedLinkAdd' application='shrinkr'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.featuredLink.add{/lang}</span></a></li>
                    {/if}
                    
                    {* Custom Button hinzufügen *}
                    {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                        <li><a href="{link controller='CustomButtonAdd' application='shrinkr'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.customButton.addAnother{/lang}</span></a></li>
                    {else}
                        <li><a href="{link controller='CustomButtonAdd' application='shrinkr'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.customButton.add{/lang}</span></a></li>
                    {/if}
                    
                    {* Special hinzufügen/bearbeiten *}
                    {if $firstActiveSpecialID|isset && $firstActiveSpecialID}
                        <li><a href="{link controller='SpecialEdit' application='shrinkr' id=$firstActiveSpecialID}{/link}" class="button">{icon size=16 name='pencil'} <span>{lang}wcf.shrinkr.special.edit{/lang}</span></a></li>
                    {else}
                        <li><a href="{link controller='SpecialAdd' application='shrinkr'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.special.add{/lang}</span></a></li>
                    {/if}
                {/if}
                
                {event name='contentHeaderNavigation'}
            </ul>
        </nav>
    {/if}
</header>


{if SHRINKR_ACTIVE}

    {include file='formError'}

    {if $removeUrlsPrefix && !$htaccessRuleExists && $detectedWebserver}
        <woltlab-core-notice class="info" type="info">
            <p><strong>{lang}shrinkr.acp.url.removeUrlsPrefix.info.title{/lang}</strong></p>
            <p>{lang}shrinkr.acp.url.removeUrlsPrefix.info.description{/lang}</p>
            <p><small>{lang}shrinkr.acp.url.removeUrlsPrefix.info.htaccess.generate.description{/lang}</small></p>
        </woltlab-core-notice>
        
        {if !$htaccessRuleExists}
            <script data-relocate="true">
                require(['WoltLabSuite/Core/Ajax', 'WoltLabSuite/Core/Language', 'WoltLabSuite/Core/Ui/Dialog'], (Ajax, Language, Dialog) => {
                    Language.addObject({
                        'shrinkr.acp.rewrite.title': '{jslang}shrinkr.acp.rewrite.title{/jslang}',
                    });
            
                    const rewriteRulesDialog = {
                        _dialogSetup() {
                            return {
                                id: 'dialogUrlshortRewriteRules',
                                options: {
                                    title: Language.get('shrinkr.acp.rewrite.title'),
                                },
                                source: null,
                            };
                        },
                        _ajaxSetup() {
                            return {
                                data: {
                                    actionName: 'generateRewriteRules',
                                    className: 'shrinkr\\data\\option\\OptionAction',
                                },
                            };
                        },
                        _ajaxSuccess(data) {
                            Dialog.open(this, data.returnValues);
                        },
                    };
            
                    $('.jsButtonGenerateRewriteRules').on('click', function() {
                        Ajax.api(rewriteRulesDialog);
                    });
                });
            </script>
        {/if}
    {/if}

    {if $success|isset}
            <p class="success">{lang}wcf.global.success.{$action}{/lang} {lang}shrinkr.acp.url.success.theShortUrlIs{/lang} <kbd>{$shortUrl}</kbd> <button class="copyUrlButton" data-copy-link="{$shortUrl}" data-tooltip="{lang}wcf.shrinkr.copyUrl{/lang}" aria-label="{lang}wcf.shrinkr.copyUrl{/lang}">{icon name='copy'}</button></p>
                    
            <script data-relocate="true">
                WCF.Language.addObject({
                    'wcf.shrinkr.copyUrl.success': '{jslang}wcf.shrinkr.copyUrl.success{/jslang}',
                    'wcf.shrinkr.copyUrl.error': '{jslang}wcf.shrinkr.copyUrl.error{/jslang}'
                });

                require(["Shrinkr/Ui/CopyLinkButton"], (CopyLinkButton) => {
                    CopyLinkButton.setup();
                });
            </script>
    {/if}

    <form method="post" action="{if $action == 'add'}{link application='shrinkr' controller='UrlAdd'}{/link}{else}{link application='shrinkr' controller='UrlEdit' id=$linkID}{/link}{/if}">
        <div class="section">
            <dl{if $errorField == 'hash'} class="formError"{/if}>
                <dt><label for="hash">{lang}wcf.shrinkr.url.hash{/lang}</label></dt>
                <dd>
                    <input type="text" id="hash" name="hash" placeholder="Hash" value="{$hash}" required autofocus maxlength="64" class="long" pattern="{SHRINKR_PATTERN}">
                    <small class="formFieldDescription">{lang}wcf.shrinkr.url.hash.description{/lang}</small>
                    {if $errorField == 'hash'}
                        <small class="innerError">
                            {if $errorType == 'empty'}
                                {lang}wcf.global.form.error.empty{/lang}
                            {else}
                                {lang}shrinkr.acp.url.hash.error.{$errorType}{/lang}
                            {/if}
                        </small>
                    {/if}
                </dd>
            </dl>
            
            <dl{if $errorField == 'url'} class="formError"{/if}>
                <dt><label for="url">{lang}wcf.shrinkr.urlGoal{/lang}</label></dt>
                <dd>
                    <input type="text" id="url" name="url" placeholder="http{if SHRINKR_ONLY_HTTPS}s{/if}://" value="{$link}" required maxlength="255" class="long">
                    <small class="formFieldDescription">{lang}wcf.shrinkr.urlGoal.description{/lang}</small>
                    {if $errorField == 'url'}
                        <small class="innerError">
                            {if $errorType == 'empty'}
                                {lang}wcf.global.form.error.empty{/lang}
                            {else}
                                {lang}shrinkr.acp.url.url.error.{$errorType}{/lang}
                            {/if}
                        </small>
                    {/if}
                </dd>
            </dl>
            
            {if $action == 'edit' && SHRINKR_COUNTER_ACTIVE}
                <dl>
                    <dt><label for="resetCounter">{lang}wcf.shrinkr.resetCounter{/lang}</label></dt>
                    <dd>
                        <input type="checkbox" id="resetCounter" name="resetCounter" value="reset">
                    </dd>
                </dl>
            {/if}
            
            {* URL Title Field (from template listener) *}
            <dl{if $errorField == 'linkTitle'} class="formError"{/if}>
                <dt><label for="linkTitle">{lang}wcf.shrinkr.url.linkTitle{/lang}</label></dt>
                <dd>
                    <input type="text" id="linkTitle" name="linkTitle" placeholder="{lang}wcf.shrinkr.url.linkTitle.description{/lang}" value="{$linkTitle}" maxlength="255" class="long">
                    {if $errorField == 'linkTitle'}
                        <small class="innerError">
                            {if $errorType == 'empty'}
                                {lang}wcf.global.form.error.empty{/lang}
                            {else}
                                {lang}shrinkr.acp.url.linkTitle.error.{$errorType}{/lang}
                            {/if}
                        </small>
                    {/if}
                </dd>
            </dl>
            
            {event name='dataFields'}
        </div>
        
        {* URL Edit Featured Links Section (from template listener) *}
        {if $action == 'edit' && $linkID|isset}
            <section class="section">
                <header class="sectionHeader">
                    <h2 class="sectionTitle">
                        {lang}wcf.shrinkr.featuredLink.section{/lang}
                        {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                            <span class="badge">{#$urlFeaturedLinks|count}</span>
                        {/if}
                    </h2>
                    <p class="sectionDescription">{lang}wcf.shrinkr.featuredLink.section.description{/lang}</p>
                </header>

                {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                    <div class="formSubmit">
                        <a href="{link controller='FeaturedLinkList' application='shrinkr'}linkID={#$linkID}{/link}" 
                           class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.featuredLink.list{/lang}</span></a>
                    </div>
                {/if}

                {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                    <section id="featuredLinksList" class="sortableListContainer">
                        <ol class="sortableList jsObjectActionContainer" data-object-action-class-name="shrinkr\data\featuredlink\FeaturedLinkAction" data-object-id="0" start="1">
                            {foreach from=$urlFeaturedLinks item=featuredLink}
                                <li class="sortableNode sortableNoNesting jsObjectActionObject" data-object-id="{#$featuredLink->linkID}">
                                    <span class="sortableNodeLabel">
                                        <a href="{link controller='FeaturedLinkEdit' application='shrinkr' id=$featuredLink->linkID}{/link}">{$featuredLink->title}</a>
                                        <small class="badge">{$featuredLink->getHost()}</small>
                                        
                                        <span class="statusDisplay sortableButtonContainer">
                                            <span class="sortableNodeHandle">
                                                {icon size=16 name='arrows-up-down-left-right'}
                                            </span>
                                            <a href="{link controller='FeaturedLinkEdit' application='shrinkr' id=$featuredLink->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">
                                                {icon size=16 name='pencil'}
                                            </a>
                                            {objectAction action="delete" objectTitle=$featuredLink->getTitle()}
                                            {event name='rowButtons'}
                                        </span>
                                    </span>
                                    <ol class="sortableList" data-object-id="{#$featuredLink->linkID}"></ol>
                                </li>
                            {/foreach}
                        </ol>
                    </section>
                    
                    <div class="formSubmit">
                        <button type="button" class="button buttonPrimary" data-type="submit">{icon size=16 name='check'} <span>{lang}wcf.global.button.saveSorting{/lang}</span></button>
                    </div>
                    
                    <script data-relocate="true">
                        require(['WoltLabSuite/Core/Ui/Sortable/List'], function(UiSortableList) {
                            new UiSortableList({
                                containerId: 'featuredLinksList',
                                className: 'shrinkr\\data\\featuredlink\\FeaturedLinkAction',
                                offset: 0
                            });
                        });
                    </script>
                {else}
                    <p class="info">{lang}wcf.shrinkr.featuredLink.noItemsInUrl{/lang}</p>
                {/if}
            </section>
        {/if}

        {* URL Edit Custom Buttons Section (from template listener) *}
        {if $action == 'edit' && $linkID|isset}
            <section class="section">
                <header class="sectionHeader">
                    <h2 class="sectionTitle">
                        {lang}wcf.shrinkr.customButton.section{/lang}
                        {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                            <span class="badge">{#$urlCustomButtons|count}</span>
                        {/if}
                    </h2>
                    <p class="sectionDescription">{lang}wcf.shrinkr.customButton.section.description{/lang}</p>
                </header>

                {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                    <div class="formSubmit">
                        <a href="{link controller='CustomButtonList' application='shrinkr'}linkID={#$linkID}{/link}" 
                           class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.customButton.list{/lang}</span></a>
                    </div>
                {/if}

                {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                    <section id="customButtonsList" class="sortableListContainer">
                        <ol class="sortableList jsObjectActionContainer" data-object-action-class-name="shrinkr\data\custombutton\CustomButtonAction" data-object-id="0" start="1">
                            {foreach from=$urlCustomButtons item=customButton}
                                <li class="sortableNode sortableNoNesting jsObjectActionObject" data-object-id="{#$customButton->customButtonID}">
                                    <span class="sortableNodeLabel">
                                        <a href="{link controller='CustomButtonEdit' application='shrinkr' id=$customButton->customButtonID}{/link}">{$customButton->title}</a>
                                        <small class="badge">{$customButton->targetUrl|truncate:50}</small>
                                        
                                        <span class="statusDisplay sortableButtonContainer">
                                            <span class="sortableNodeHandle">
                                                {icon size=16 name='arrows-up-down-left-right'}
                                            </span>
                                            <a href="{link controller='CustomButtonEdit' application='shrinkr' id=$customButton->customButtonID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">
                                                {icon size=16 name='pencil'}
                                            </a>
                                            {objectAction action="delete" objectTitle=$customButton->getTitle()}
                                            {event name='rowButtons'}
                                        </span>
                                    </span>
                                    <ol class="sortableList" data-object-id="{#$customButton->customButtonID}"></ol>
                                </li>
                            {/foreach}
                        </ol>
                    </section>
                    
                    <div class="formSubmit">
                        <button type="button" class="button buttonPrimary" data-type="submit">{icon size=16 name='check'} <span>{lang}wcf.global.button.saveSorting{/lang}</span></button>
                    </div>
                    
                    <script data-relocate="true">
                        require(['WoltLabSuite/Core/Ui/Sortable/List'], function(UiSortableList) {
                            new UiSortableList({
                                containerId: 'customButtonsList',
                                className: 'shrinkr\\data\\custombutton\\CustomButtonAction',
                                offset: 0
                            });
                        });
                    </script>
                {else}
                    <p class="info">{lang}wcf.shrinkr.customButton.noItemsInUrl{/lang}</p>
                {/if}
            </section>
        {/if}
        
        {event name='sections'}
        
        <div class="formSubmit">
            <input type="submit" value="{lang}wcf.global.button.submit{/lang}" accesskey="s">
            {csrfToken}
        </div>
    </form>
{else}
    <p class="error">{lang}wcf.shrinkr.notActive{/lang}</p>
{/if}

{hascontent}
<footer class="contentFooter">
    <nav class="contentFooterNavigation">
        <ul>
            {content}
                <li><a href="{link application='shrinkr' controller='ShrinkrLinkList'}{/link}" class="button">{icon name='list'} <span>{lang}shrinkr.acp.menu.link.url.list{/lang}</span></a></li>
            {/content}
        </ul>
    </nav>
</footer>
{/hascontent}

{include file='footer'}