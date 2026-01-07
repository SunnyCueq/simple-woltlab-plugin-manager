{if $effect|isset}
	<script data-relocate="true">
		require(['Benjaro/Urlshort/Ui/Snow', 'WoltLabSuite/Core/Environment'], function(module, Environment) {
			if (!module || !module.Snow) {
				return;
			}

			const Snow = module.Snow;
			const isMobile = Environment.platform() !== 'desktop';

			new Snow({
				num: isMobile ? {$effect.mobile.num} : {$effect.desktop.num},
				speed: isMobile ? {$effect.mobile.speed} : {$effect.desktop.speed},
				minScale: isMobile ? {$effect.mobile.minScale} : {$effect.desktop.minScale},
				maxScale: isMobile ? {$effect.mobile.maxScale} : {$effect.desktop.maxScale},
				opacity: {$effect.opacity},
				fadeScroll: {if $effect.fadeScroll}true{else}false{/if},
				enableMobile: {if $effect.enableMobile}true{else}false{/if},
				enableInteraction: {if $effect.enableInteraction}true{else}false{/if}
			}, document.querySelectorAll('{$effect.selector|encodeJS}'));
		});
	</script>
{/if}

