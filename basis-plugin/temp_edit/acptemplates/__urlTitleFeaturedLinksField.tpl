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

{* Featured Links & Specials werden über Header-Buttons verwaltet - keine Listen hier nötig *}
