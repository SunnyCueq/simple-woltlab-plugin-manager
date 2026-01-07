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

{* Featured Links & Specials werden über Header-Buttons verwaltet - keine Listen hier nötig *}
