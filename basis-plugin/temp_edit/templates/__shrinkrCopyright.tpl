{*
 * Template-Zweck: Copyright-Anzeige für Shr1nkr Plugin
 * 
 * Zeigt Copyright-Text am Ende der Redirect-Seite an, wenn Copyright-Anzeige
 * aktiviert ist. Wird über Template Listener eingefügt.
 * 
 * Variablen:
 * @var bool SHRINKR_COPYRIGHT_ENABLED - Ob Copyright-Anzeige aktiviert ist
 * 
 * Logik:
 * - Prüft ob Plugin aktiv ist und Copyright aktiviert
 * - Zeigt Copyright-Text wenn beide Bedingungen erfüllt
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{if $__shrinkr->isActiveApplication() && 'SHRINKR_COPYRIGHT_ENABLED'|defined && SHRINKR_COPYRIGHT_ENABLED}
	<div class="copyright">Shr1nkr - Affiliate Url-Shortener</div>
{/if}
