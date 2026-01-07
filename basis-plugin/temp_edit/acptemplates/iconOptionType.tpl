<span id="{$option->optionName}_icon">
	{if $icon}
		{unsafe:$icon->toHtml(64)}
	{/if}
</span>
<button type="button" class="button small" id="{$option->optionName}_openIconDialog">{lang}wcf.global.button.edit{/lang}</button>
<button type="button" class="button small" id="{$option->optionName}_removeIcon"{if !$icon} hidden{/if}>{lang}wcf.global.button.delete{/lang}</button>
<input type="hidden" id="{$option->optionName}" name="values[{$option->optionName}]" value="{$value}">

{if $__iconFormFieldIncludeJavaScript}
	{include file='shared_fontAwesomeJavaScript'}
{/if}

<script data-relocate="true">
	require(['WoltLabSuite/Core/Ui/Style/FontAwesome'], (UiStyleFontAwesome) => {
		const iconContainer = document.getElementById('{unsafe:$option->optionName|encodeJS}_icon');
		const input = document.getElementById('{unsafe:$option->optionName|encodeJS}');
		const buttonRemoveIcon = document.getElementById('{unsafe:$option->optionName|encodeJS}_removeIcon');
		
		const callback = (iconName, forceSolid) => {
			input.value = `${ iconName };${ forceSolid }`;

			let icon = iconContainer.querySelector("fa-icon");
			if (icon) {
				icon.setIcon(iconName, forceSolid);
			} else {
				icon = document.createElement("fa-icon");
				icon.size = 64;
				icon.setIcon(iconName, forceSolid);
				iconContainer.append(icon);
			}

			buttonRemoveIcon.hidden = false;
		};
		
		const button = document.getElementById('{unsafe:$option->optionName|encodeJS}_openIconDialog');
		button.addEventListener('click', () => UiStyleFontAwesome.open(callback));
		buttonRemoveIcon.addEventListener("click", () => {
			input.value = "";
			iconContainer.querySelector("fa-icon")?.remove();

			buttonRemoveIcon.hidden = true;
		});
	});
</script>

