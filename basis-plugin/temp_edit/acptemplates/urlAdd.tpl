{include file='header' pageTitle='urlshort.acp.url.'|concat:$action}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}urlshort.acp.url.{$action}{/lang}</h1>
	</div>
	
    {if URLSHORT_ACTIVE}
        <nav class="contentHeaderNavigation">
            <ul>
                {if $removeUrlsPrefix && !$htaccessRuleExists && $detectedWebserver}
                    <li><button type="button" class="button buttonPrimary jsButtonGenerateRewriteRules">{icon name='code'} <span>{lang}urlshort.acp.url.removeUrlsPrefix.info.htaccess.generate{/lang}</span></button></li>
                {/if}
                <li><a href="{link application='urlshort' controller='UrlList'}{/link}" class="button">{icon name='list'} <span>{lang}urlshort.acp.menu.link.url.list{/lang}</span></a></li>
                
                {* URL Edit Special Hint (from template listener) *}
                {if $action == 'edit' && $urlID|isset}
                    {* Featured Link hinzufügen - immer oben in Navigation *}
                    {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                        <li><a href="{link controller='FeaturedLinkAdd' application='urlshort'}urlID={#$urlID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.urlshort.featuredLink.addAnother{/lang}</span></a></li>
                    {else}
                        <li><a href="{link controller='FeaturedLinkAdd' application='urlshort'}urlID={#$urlID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.urlshort.featuredLink.add{/lang}</span></a></li>
                    {/if}
                    
                    {* Custom Button hinzufügen *}
                    {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                        <li><a href="{link controller='CustomButtonAdd' application='urlshort'}urlID={#$urlID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.urlshort.customButton.addAnother{/lang}</span></a></li>
                    {else}
                        <li><a href="{link controller='CustomButtonAdd' application='urlshort'}urlID={#$urlID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.urlshort.customButton.add{/lang}</span></a></li>
                    {/if}
                    
                    {* Special hinzufügen/bearbeiten *}
                    {if $firstActiveSpecialID|isset && $firstActiveSpecialID}
                        <li><a href="{link controller='SpecialEdit' application='urlshort' id=$firstActiveSpecialID}{/link}" class="button">{icon size=16 name='pencil'} <span>{lang}wcf.urlshort.special.edit{/lang}</span></a></li>
                    {else}
                        <li><a href="{link controller='SpecialAdd' application='urlshort'}urlID={#$urlID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.urlshort.special.add{/lang}</span></a></li>
                    {/if}
                {/if}
                
                {event name='contentHeaderNavigation'}
            </ul>
        </nav>
    {/if}
</header>


{if URLSHORT_ACTIVE}

    {include file='formError'}

    {if $removeUrlsPrefix && !$htaccessRuleExists && $detectedWebserver}
        <woltlab-core-notice class="info" type="info">
            <p><strong>{lang}urlshort.acp.url.removeUrlsPrefix.info.title{/lang}</strong></p>
            <p>{lang}urlshort.acp.url.removeUrlsPrefix.info.description{/lang}</p>
            <p><small>{lang}urlshort.acp.url.removeUrlsPrefix.info.htaccess.generate.description{/lang}</small></p>
        </woltlab-core-notice>
        
        {if !$htaccessRuleExists}
            <script data-relocate="true">
                require(['WoltLabSuite/Core/Ajax', 'WoltLabSuite/Core/Language', 'WoltLabSuite/Core/Ui/Dialog'], (Ajax, Language, Dialog) => {
                    Language.addObject({
                        'urlshort.acp.rewrite.title': '{jslang}urlshort.acp.rewrite.title{/jslang}',
                    });
            
                    const rewriteRulesDialog = {
                        _dialogSetup() {
                            return {
                                id: 'dialogUrlshortRewriteRules',
                                options: {
                                    title: Language.get('urlshort.acp.rewrite.title'),
                                },
                                source: null,
                            };
                        },
                        _ajaxSetup() {
                            return {
                                data: {
                                    actionName: 'generateRewriteRules',
                                    className: 'urlshort\\data\\option\\OptionAction',
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
            <p class="success">{lang}wcf.global.success.{$action}{/lang} {lang}urlshort.acp.url.success.theShortUrlIs{/lang} <kbd>{$shortUrl}</kbd> <button class="copyUrlButton" data-copy-link="{$shortUrl}" data-tooltip="{lang}wcf.urlshort.copyUrl{/lang}" aria-label="{lang}wcf.urlshort.copyUrl{/lang}">{icon name='copy'}</button></p>
                    
            <script data-relocate="true">
                WCF.Language.addObject({
                    'wcf.urlshort.copyUrl.success': '{jslang}wcf.urlshort.copyUrl.success{/jslang}',
                    'wcf.urlshort.copyUrl.error': '{jslang}wcf.urlshort.copyUrl.error{/jslang}'
                });

                require(["JulianPfeil/Urlshort/Ui/CopyLinkButton"], (CopyLinkButton) => {
                    CopyLinkButton.setup();
                });
            </script>
    {/if}

    <form method="post" action="{if $action == 'add'}{link application='urlshort' controller='UrlAdd'}{/link}{else}{link application='urlshort' controller='UrlEdit' id=$urlID}{/link}{/if}">
        <div class="section">
            <dl{if $errorField == 'hash'} class="formError"{/if}>
                <dt><label for="hash">{lang}wcf.urlshort.url.hash{/lang}</label></dt>
                <dd>
                    <input type="text" id="hash" name="hash" placeholder="Hash" value="{$hash}" required autofocus maxlength="64" class="long" pattern="{URLSHORT_PATTERN}">
                    <small class="formFieldDescription">{lang}wcf.urlshort.url.hash.description{/lang}</small>
                    {if $errorField == 'hash'}
                        <small class="innerError">
                            {if $errorType == 'empty'}
                                {lang}wcf.global.form.error.empty{/lang}
                            {else}
                                {lang}urlshort.acp.url.hash.error.{$errorType}{/lang}
                            {/if}
                        </small>
                    {/if}
                </dd>
            </dl>
            
            <dl{if $errorField == 'url'} class="formError"{/if}>
                <dt><label for="url">{lang}wcf.urlshort.urlGoal{/lang}</label></dt>
                <dd>
                    <input type="text" id="url" name="url" placeholder="http{if URLSHORT_ONLY_HTTPS}s{/if}://" value="{$url}" required maxlength="255" class="long">
                    <small class="formFieldDescription">{lang}wcf.urlshort.urlGoal.description{/lang}</small>
                    {if $errorField == 'url'}
                        <small class="innerError">
                            {if $errorType == 'empty'}
                                {lang}wcf.global.form.error.empty{/lang}
                            {else}
                                {lang}urlshort.acp.url.url.error.{$errorType}{/lang}
                            {/if}
                        </small>
                    {/if}
                </dd>
            </dl>
            
            {if $action == 'edit' && URLSHORT_COUNTER_ACTIVE}
                <dl>
                    <dt><label for="resetCounter">{lang}wcf.urlshort.resetCounter{/lang}</label></dt>
                    <dd>
                        <input type="checkbox" id="resetCounter" name="resetCounter" value="reset">
                    </dd>
                </dl>
            {/if}
            
            {* URL Title Field (from template listener) *}
            <dl{if $errorField == 'urlTitle'} class="formError"{/if}>
                <dt><label for="urlTitle">{lang}wcf.urlshort.url.urlTitle{/lang}</label></dt>
                <dd>
                    <input type="text" id="urlTitle" name="urlTitle" placeholder="{lang}wcf.urlshort.url.urlTitle.description{/lang}" value="{$urlTitle}" maxlength="255" class="long">
                    {if $errorField == 'urlTitle'}
                        <small class="innerError">
                            {if $errorType == 'empty'}
                                {lang}wcf.global.form.error.empty{/lang}
                            {else}
                                {lang}urlshort.acp.url.urlTitle.error.{$errorType}{/lang}
                            {/if}
                        </small>
                    {/if}
                </dd>
            </dl>
            
            {event name='dataFields'}
        </div>
        
        {* URL Edit Featured Links Section (from template listener) *}
        {if $action == 'edit' && $urlID|isset}
            <section class="section">
                <header class="sectionHeader">
                    <h2 class="sectionTitle">
                        {lang}wcf.urlshort.featuredLink.section{/lang}
                        {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                            <span class="badge">{#$urlFeaturedLinks|count}</span>
                        {/if}
                    </h2>
                    <p class="sectionDescription">{lang}wcf.urlshort.featuredLink.section.description{/lang}</p>
                </header>

                {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                    <div class="formSubmit">
                        <a href="{link controller='FeaturedLinkList' application='urlshort'}urlID={#$urlID}{/link}" 
                           class="button">{icon size=16 name='list'} <span>{lang}urlshort.acp.menu.link.featuredLink.list{/lang}</span></a>
                    </div>
                {/if}

                {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                    <section id="featuredLinksList" class="sortableListContainer">
                        <ol class="sortableList jsObjectActionContainer" data-object-action-class-name="urlshort\data\featuredlink\FeaturedLinkAction" data-object-id="0" start="1">
                            {foreach from=$urlFeaturedLinks item=featuredLink}
                                <li class="sortableNode sortableNoNesting jsObjectActionObject" data-object-id="{#$featuredLink->linkID}">
                                    <span class="sortableNodeLabel">
                                        <a href="{link controller='FeaturedLinkEdit' application='urlshort' id=$featuredLink->linkID}{/link}">{$featuredLink->title}</a>
                                        <small class="badge">{$featuredLink->getHost()}</small>
                                        
                                        <span class="statusDisplay sortableButtonContainer">
                                            <span class="sortableNodeHandle">
                                                {icon size=16 name='arrows-up-down-left-right'}
                                            </span>
                                            <a href="{link controller='FeaturedLinkEdit' application='urlshort' id=$featuredLink->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">
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
                                className: 'urlshort\\data\\featuredlink\\FeaturedLinkAction',
                                offset: 0
                            });
                        });
                    </script>
                {else}
                    <p class="info">{lang}wcf.urlshort.featuredLink.noItemsInUrl{/lang}</p>
                {/if}
            </section>
        {/if}

        {* URL Edit Custom Buttons Section (from template listener) *}
        {if $action == 'edit' && $urlID|isset}
            <section class="section">
                <header class="sectionHeader">
                    <h2 class="sectionTitle">
                        {lang}wcf.urlshort.customButton.section{/lang}
                        {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                            <span class="badge">{#$urlCustomButtons|count}</span>
                        {/if}
                    </h2>
                    <p class="sectionDescription">{lang}wcf.urlshort.customButton.section.description{/lang}</p>
                </header>

                {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                    <div class="formSubmit">
                        <a href="{link controller='CustomButtonList' application='urlshort'}urlID={#$urlID}{/link}" 
                           class="button">{icon size=16 name='list'} <span>{lang}urlshort.acp.menu.link.customButton.list{/lang}</span></a>
                    </div>
                {/if}

                {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                    <section id="customButtonsList" class="sortableListContainer">
                        <ol class="sortableList jsObjectActionContainer" data-object-action-class-name="urlshort\data\custombutton\CustomButtonAction" data-object-id="0" start="1">
                            {foreach from=$urlCustomButtons item=customButton}
                                <li class="sortableNode sortableNoNesting jsObjectActionObject" data-object-id="{#$customButton->customButtonID}">
                                    <span class="sortableNodeLabel">
                                        <a href="{link controller='CustomButtonEdit' application='urlshort' id=$customButton->customButtonID}{/link}">{$customButton->title}</a>
                                        <small class="badge">{$customButton->targetUrl|truncate:50}</small>
                                        
                                        <span class="statusDisplay sortableButtonContainer">
                                            <span class="sortableNodeHandle">
                                                {icon size=16 name='arrows-up-down-left-right'}
                                            </span>
                                            <a href="{link controller='CustomButtonEdit' application='urlshort' id=$customButton->customButtonID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">
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
                                className: 'urlshort\\data\\custombutton\\CustomButtonAction',
                                offset: 0
                            });
                        });
                    </script>
                {else}
                    <p class="info">{lang}wcf.urlshort.customButton.noItemsInUrl{/lang}</p>
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
    <p class="error">{lang}wcf.urlshort.notActive{/lang}</p>
{/if}

{hascontent}
<footer class="contentFooter">
    <nav class="contentFooterNavigation">
        <ul>
            {content}
                <li><a href="{link application='urlshort' controller='UrlList'}{/link}" class="button">{icon name='list'} <span>{lang}urlshort.acp.menu.link.url.list{/lang}</span></a></li>
            {/content}
        </ul>
    </nav>
</footer>
{/hascontent}

{include file='footer'}