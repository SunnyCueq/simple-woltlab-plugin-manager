{*
 * Template-Zweck: Rewrite Rules Ausgabe-Dialog
 * 
 * Zeigt generierte Rewrite Rules für Apache (.htaccess) und/oder nginx
 * in einem Dialog an. Enthält Anweisungen zur Installation und die
 * vollständigen Rewrite Rules zum Kopieren.
 * 
 * Variablen:
 * @var array $rewriteRules - Array mit Rewrite Rules pro Webserver
 * @var string $rewriteRules['apache']['.htaccess'] - Apache .htaccess Regeln
 * @var string $rewriteRules['nginx']['nginx.conf'] - nginx location-Blöcke
 * @var string $detectedWebserver - Erkannte Webserver-Typ ('apache', 'nginx', null)
 * 
 * Logik:
 * - Zeigt Installations-Anweisungen
 * - Zeigt Rewrite Rules für erkannten Webserver (oder beide wenn unbekannt)
 * - Zeigt Dateiname und Inhalt für jede Regel
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<p>{lang}shrinkr.acp.rewrite.description{/lang}</p>

<ol>
	<li>{lang}shrinkr.acp.url.removeShrinkrPrefix.info.htaccess.step1{/lang}</li>
	<li>{lang}shrinkr.acp.url.removeShrinkrPrefix.info.htaccess.step2{/lang}</li>
	<li>{lang}shrinkr.acp.url.removeShrinkrPrefix.info.htaccess.step3{/lang}</li>
</ol>

{foreach from=$rewriteRules key=$webserver item=$rules}
	<section class="section">
		<h2 class="sectionTitle">{lang}shrinkr.acp.url.removeShrinkrPrefix.info.htaccess.{$webserver}{/lang}</h2>
		
		{foreach from=$rules key=$path item=content}
			<dl>
				<dt>{lang}wcf.acp.rewrite.filename{/lang}</dt>
				<dd>
					<kbd>{$path}</kbd>
				</dd>
				
				<dt>{lang}wcf.acp.rewrite.fileContent{/lang}</dt>
				<dd>
					<textarea rows="10" readonly>{$content|trim}</textarea>
				</dd>
		</dl>
	{/foreach}
</section>
{/foreach}

{* Menu Badge JavaScript *}
{if $menuBadgeText}
<script>
	window.SHRINKR_MENU_BADGE_TEXT = {$menuBadgeText|json};
	require(['Shrinkr/Acp/Ui/MenuBadge'], function(MenuBadge) {
		new MenuBadge();
	});
</script>
{/if}
