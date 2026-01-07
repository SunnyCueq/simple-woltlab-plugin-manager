{* Reload-Button nur wenn URL-Shortener gerade aktiviert wurde *}
{* Bedingungen: Hauptkategorie 'shrinkr' + Erfolg + nur 1 sichtbare Unterkategorie (Allgemein) *}
{if $success|isset && $category|isset && $category->categoryName === 'shrinkr' && $optionTree|count === 1}
<script data-relocate="true">
document.addEventListener('DOMContentLoaded', function() {
	// Prüfe ob shrinkr_active aktiviert ist
	var activeCheckbox = document.getElementById('shrinkr_active');
	if (!activeCheckbox || !activeCheckbox.checked) return;
	
	// Suche die Success-Notice
	var notice = document.querySelector('woltlab-core-notice[type="success"]');
	if (!notice) return;
	
	// Füge Reload-Button hinzu
	var btn = document.createElement('a');
	btn.href = window.location.href;
	btn.className = 'button buttonSmall';
	btn.style.marginLeft = '10px';
	btn.textContent = '{jslang}shrinkr.acp.option.reload{/jslang}';
	notice.appendChild(btn);
});
</script>
{/if}
