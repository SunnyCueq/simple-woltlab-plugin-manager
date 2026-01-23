{*
 * Template-Zweck: Autumn Leaves-Effekt Initialisierung
 * 
 * Initialisiert den Autumn Leaves-Effekt für Herbst-Themes. Lädt das AutumnLeaves-Modul
 * und konfiguriert es basierend auf Theme-Einstellungen und Gerätetyp
 * (Desktop/Mobile).
 * 
 * Variablen:
 * @var array $effect - Effekt-Konfiguration mit mobile/desktop-Einstellungen
 * @var string $effect.selector - CSS-Selektor für Container
 * @var int $effect.mobile.num - Anzahl Partikel (Mobile)
 * @var int $effect.desktop.num - Anzahl Partikel (Desktop)
 * @var float $effect.speed - Geschwindigkeit
 * @var int $effect.minScale - Minimale Partikel-Größe
 * @var int $effect.maxScale - Maximale Partikel-Größe
 * @var float $effect.opacity - Opazität
 * @var bool $effect.fadeScroll - Ob Effekt beim Scrollen ausblendet
 * @var bool $effect.enableMobile - Ob Effekt auf Mobile aktiviert ist
 * @var bool $effect.enableInteraction - Ob Pointer-Interaktion aktiviert ist
 * 
 * Logik:
 * - Lädt AutumnLeaves-Modul über require
 * - Erkennt Gerätetyp (Desktop/Mobile)
 * - Konfiguriert Effekt mit gerätespezifischen Einstellungen
 * - Initialisiert Effekt für alle Container mit dem Selektor
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
{if $effect|isset}
	<script data-relocate="true">
		require(['Shrinkr/Ui/AutumnLeaves', 'WoltLabSuite/Core/Environment'], function(module, Environment) {
			if (!module || !module.AutumnLeaves) {
				return;
			}

			const AutumnLeaves = module.AutumnLeaves;
			const isMobile = Environment.platform() !== 'desktop';

			new AutumnLeaves({
				num: isMobile ? {$effect.mobile.num} : {$effect.desktop.num},
				speed: isMobile ? {$effect.mobile.speed} : {$effect.desktop.speed},
				minScale: isMobile ? {$effect.mobile.minScale} : {$effect.desktop.minScale},
				maxScale: isMobile ? {$effect.mobile.maxScale} : {$effect.desktop.maxScale},
				enableMobile: {if $effect.enableMobile}true{else}false{/if},
				opacity: {$effect.opacity},
				fadeScroll: {if $effect.fadeScroll}true{else}false{/if},
				enableInteraction: {if $effect.enableInteraction}true{else}false{/if}
			}, document.querySelectorAll('{$effect.selector|encodeJS}'));
		});
	</script>
{/if}

