{*
 * Template-Zweck: Filter-Feld für URL-Titel und Featured Links
 * 
 * Filter-Feld für die URL-Liste. Ermöglicht Suche nach URL-Titel
 * oder Featured Links.
 * 
 * Variablen:
 * @var string $qTitle - Suchfilter-Wert
 * 
 * Logik:
 * - Zeigt Text-Input für Titel/Featured Links-Suche
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<dl class="col-xs-12 col-md-6">
    <dt></dt>
    <dd>
        <input class="long" type="text" name="qTitle" value="{$qTitle}" placeholder="{lang}wcf.shrinkr.url.linkTitle{/lang} / {lang}wcf.shrinkr.featuredLinks{/lang}">
    </dd>
</dl>