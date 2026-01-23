{*
 * Template-Zweck: ACP-Formular für Hinzufügen/Bearbeiten von URLs
 * 
 * ACP-Template für das Hinzufügen und Bearbeiten von verkürzten URLs.
 * Zeigt Formular-Felder für URL, Hash, Titel, Featured Links, Custom Buttons
 * und Specials. Enthält Navigation zu verwandten Formularen (Featured Links,
 * Custom Buttons, Specials). Unterstützt Rewrite Rules Generator.
 * 
 * Variablen:
 * @var string $action - Formular-Aktion ('add' oder 'edit')
 * @var int $linkID - Link-ID (bei Edit)
 * @var bool $removeUrlsPrefix - Ob URL-Prefix entfernt werden soll
 * @var bool $htaccessRuleExists - Ob .htaccess-Regel existiert
 * @var string $detectedWebserver - Erkannte Webserver-Typ ('apache', 'nginx')
 * @var array $urlFeaturedLinks - Array von Featured Links für diese URL
 * @var array $urlCustomButtons - Array von Custom Buttons für diese URL
 * @var int $firstActiveSpecialID - ID des ersten aktiven Specials
 * @var bool SHRINKR_ACTIVE - Ob Plugin aktiv ist
 * 
 * Logik:
 * - Zeigt Formular für URL-Hinzufügen/Bearbeiten
 * - Zeigt Navigation zu verwandten Formularen (bei Edit)
 * - Zeigt Rewrite Rules Generator (wenn nötig)
 * - Initialisiert JavaScript für Rewrite Rules Generator
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
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
                <li><a href="{link controller='ShrinkrLinkList'}{/link}" class="button">{icon name='list'} <span>{lang}shrinkr.acp.menu.link.link.list{/lang}</span></a></li>
                
                {* URL Edit Special Hint (from template listener) *}
                {if $action == 'edit' && $linkID|isset}
                    {* Featured Link hinzufügen - immer oben in Navigation *}
                    {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                        <li><a href="{link controller='FeaturedLinkAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.featuredLink.addAnother{/lang}</span></a></li>
                    {else}
                        <li><a href="{link controller='FeaturedLinkAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.featuredLink.add{/lang}</span></a></li>
                    {/if}
                    
                    {* Custom Button hinzufügen *}
                    {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                        <li><a href="{link controller='CustomButtonAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.customButton.addAnother{/lang}</span></a></li>
                    {else}
                        <li><a href="{link controller='CustomButtonAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.customButton.add{/lang}</span></a></li>
                    {/if}
                    
                    {* Special hinzufügen/bearbeiten *}
                    {if $firstActiveSpecialID|isset && $firstActiveSpecialID}
                        <li><a href="{link controller='SpecialEdit' application='shrinkr' id=$firstActiveSpecialID}{/link}" class="button">{icon size=16 name='pencil'} <span>{lang}wcf.shrinkr.special.edit{/lang}</span></a></li>
                    {else}
                        <li><a href="{link controller='SpecialAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.special.add{/lang}</span></a></li>
                    {/if}
                {/if}
                
                {event name='contentHeaderNavigation'}
            </ul>
        </nav>
    {/if}
</header>


{if SHRINKR_ACTIVE}

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
                                id: 'dialogShrinkrRewriteRules',
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

    {if $success|isset && $shortUrl|isset && $shortUrl}
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

    {unsafe:$form->getHtml()}
{else}
    <p class="error">{lang}wcf.shrinkr.notActive{/lang}</p>
{/if}

<footer class="contentFooter">
    <nav class="contentFooterNavigation">
        <ul>
            {if $removeUrlsPrefix && !$htaccessRuleExists && $detectedWebserver}
                <li><button type="button" class="button buttonPrimary jsButtonGenerateRewriteRules">{icon name='code'} <span>{lang}shrinkr.acp.url.removeUrlsPrefix.info.htaccess.generate{/lang}</span></button></li>
            {/if}
            
            <li><a href="{link controller='ShrinkrLinkList'}{/link}" class="button">{icon name='list'} <span>{lang}shrinkr.acp.menu.link.link.list{/lang}</span></a></li>
            
            {if $action == 'edit' && $linkID|isset}
                {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                    <li><a href="{link controller='FeaturedLinkAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.featuredLink.addAnother{/lang}</span></a></li>
                {else}
                    <li><a href="{link controller='FeaturedLinkAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.featuredLink.add{/lang}</span></a></li>
                {/if}
                
                {if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
                    <li><a href="{link controller='CustomButtonAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.customButton.addAnother{/lang}</span></a></li>
                {else}
                    <li><a href="{link controller='CustomButtonAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.customButton.add{/lang}</span></a></li>
                {/if}
                
                {if $firstActiveSpecialID|isset && $firstActiveSpecialID}
                    <li><a href="{link controller='SpecialEdit' application='shrinkr' id=$firstActiveSpecialID}{/link}" class="button">{icon size=16 name='pencil'} <span>{lang}wcf.shrinkr.special.edit{/lang}</span></a></li>
                {else}
                    <li><a href="{link controller='SpecialAdd'}linkID={#$linkID}{/link}" class="button">{icon size=16 name='plus'} <span>{lang}wcf.shrinkr.special.add{/lang}</span></a></li>
                {/if}
            {/if}
            
            {event name='contentFooterNavigation'}
        </ul>
    </nav>
</footer>

{include file='footer'}
