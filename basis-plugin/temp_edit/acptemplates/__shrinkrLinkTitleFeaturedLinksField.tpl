{*
 * Template-Zweck: URL-Titel Feld für URL-Formular
 * 
 * Zeigt Eingabefeld für URL-Titel im URL-Hinzufügen/Bearbeiten-Formular.
 * Unterstützt Validierung und Fehleranzeige.
 * 
 * Variablen:
 * @var string $linkTitle - Aktueller Titel-Wert
 * @var string $errorField - Feld mit Fehler (falls vorhanden)
 * @var string $errorType - Fehlertyp (falls vorhanden)
 * 
 * Logik:
 * - Zeigt Text-Input für URL-Titel
 * - Zeigt Fehlermeldung wenn Validierung fehlschlägt
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
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
