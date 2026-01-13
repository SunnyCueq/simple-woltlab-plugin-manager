{* Reload-Button nur wenn Shr1nkr gerade aktiviert wurde *}
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
	
	// Füge Reload-Button hinzu (WoltLab-Standard-Format)
	var btn = document.createElement('a');
	btn.href = window.location.href;
	btn.className = 'button small';
	btn.style.marginLeft = '10px';
	
	// Erstelle Icon
	var icon = document.createElement('fa-icon');
	icon.setAttribute('size', '16');
	icon.setAttribute('name', 'arrows-rotate');
	icon.setAttribute('aria-hidden', 'true');
	icon.setAttribute('translate', 'no');
	btn.appendChild(icon);
	
	// Füge Text hinzu (direkt neben Icon, kein Abstand)
	var span = document.createElement('span');
	span.textContent = '{jslang}shrinkr.acp.option.reload{/jslang}';
	btn.appendChild(span);
	
	notice.appendChild(btn);
});
</script>
{/if}
