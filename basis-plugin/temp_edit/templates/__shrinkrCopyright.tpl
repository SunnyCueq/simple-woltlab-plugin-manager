{*
 * Template-Zweck: Copyright-Anzeige für Shr1nkr Plugin
 * 
 * Zeigt Copyright-Text im Frontend-Footer an, wenn Copyright-Anzeige
 * aktiviert ist. Wird über Template Listener im 'copyright' Event von
 * pageFooterCopyright.tpl eingefügt, VOR dem WoltLab-Copyright.
 * 
 * Variablen:
 * @var bool $copyrightEnabled - Ob Copyright-Anzeige aktiviert ist
 * 
 * Logik:
 * - Prüft ob Plugin aktiv ist und Copyright aktiviert
 * - Zeigt Copyright-Text mit Link wenn beide Bedingungen erfüllt
 * - Wird VOR dem WoltLab-Copyright angezeigt (durch Event-Reihenfolge)
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{if $__shrinkr->isActiveApplication() && $copyrightEnabled|isset && $copyrightEnabled}
<div class="copyright">
	<a href="https://sunnyc.de" rel="nofollow">Shr1nkr - Affiliate Url-Shortener</a>
</div>
{/if}
