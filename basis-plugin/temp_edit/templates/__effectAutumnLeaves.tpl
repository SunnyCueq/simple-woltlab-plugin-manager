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

