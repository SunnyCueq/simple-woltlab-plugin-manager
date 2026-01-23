{*
 * Template-Zweck: Custom Forward Button Option Type
 * 
 * Zeigt Checkbox zum Aktivieren/Deaktivieren des 3D-Button-Stils und
 * eine statische Vorschau des Buttons. Enthält Inline-Styles für
 * Button-Vorschau (75% der Originalgröße).
 * 
 * Variablen:
 * @var \wcf\data\option\Option $option - Option-Objekt
 * @var string $option->optionName - Option-Name
 * @var int $value - Aktueller Wert (0 oder 1)
 * 
 * Logik:
 * - Zeigt Radio-Buttons für Ja/Nein
 * - Zeigt statische Button-Vorschau mit 3D-Effekt
 * - Styles sind inline (kein Zugriff auf ACP-Stylesheets)
 * 
 * @author Sunny C
 * @copyright 2026 Sunny C
 * @package de.sunnyc.wsc.shrinkr
 * @see https://sunnyc.de
 *}
<style>
/* Custom Forward Button Preview Styles (75% of original size) */
/* WICHTIG: Diese Styles sind NUR für ACP-Vorschau, haben keinen Einfluss auf Frontend-Button */
.forwardButtonPreview__example {
    margin-top: 0.5rem;
}

.forwardButtonPreview__example .forwardButtonEnhanced {
    position: relative;
    display: inline-block;
    border: none;
    background: transparent;
    padding: 0;
    outline: none;
    cursor: pointer;
    text-decoration: none;
    font-family: var(--wcfFontFamily);
    text-transform: uppercase;
    font-size: 0.75rem;
}

.forwardButtonPreview__example .forwardButtonEnhanced__shadow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.25);
    border-radius: var(--wcfBorderRadius);
    transform: translateY(0);
    transition: transform 600ms cubic-bezier(0.3, 0.7, 0.4, 1);
}

.forwardButtonPreview__example .forwardButtonEnhanced__content {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.46875rem 0.9375rem;
    color: #ffffff;
    border-radius: var(--wcfBorderRadius);
    transform: translateY(-3px);
    background: linear-gradient(to right, #e94057, #f27121);
    gap: 0.5625rem;
    transition: transform 600ms cubic-bezier(0.3, 0.7, 0.4, 1),
                filter 600ms cubic-bezier(0.3, 0.7, 0.4, 1);
    filter: brightness(1);
    min-height: 1.875rem;
}

.forwardButtonPreview__example .forwardButtonEnhanced__text {
    user-select: none;
    white-space: nowrap;
}

.forwardButtonPreview__example .forwardButtonEnhanced__icon {
    transition: transform 250ms;
    display: inline-flex;
    align-items: center;
    font-size: 0.75em;
}

.forwardButtonPreview__example .forwardButtonEnhanced:hover {
    text-decoration: none;
}

.forwardButtonPreview__example .forwardButtonEnhanced:hover .forwardButtonEnhanced__shadow {
    transform: translateY(1.5px);
    transition-duration: 250ms;
}

.forwardButtonPreview__example .forwardButtonEnhanced:hover .forwardButtonEnhanced__content {
    transform: translateY(-4.5px);
    transition-duration: 250ms;
    filter: brightness(1.1);
}

.forwardButtonPreview__example .forwardButtonEnhanced:hover .forwardButtonEnhanced__icon {
    transform: translateX(3px);
}
</style>

{* WoltLab-Standard-Button-Gruppe für Boolean-Optionen *}
<ol class="flexibleButtonGroup optionTypeBoolean">
    <li>
        <input type="radio" id="{$option->optionName}" name="values[{$option->optionName}]" value="1"{if $value == 1} checked{/if}>
        <label for="{$option->optionName}" class="green">{icon name='check'} {lang}wcf.acp.option.type.boolean.yes{/lang}</label>
    </li>
    <li>
        <input type="radio" id="{$option->optionName}_no" name="values[{$option->optionName}]" value="0"{if $value == 0} checked{/if}>
        <label for="{$option->optionName}_no" class="red">{icon name='xmark'} {lang}wcf.acp.option.type.boolean.no{/lang}</label>
    </li>
</ol>

{* Button Preview *}
<div class="forwardButtonPreview__example">
    <small>{lang}wcf.acp.option.{$option->optionName}.preview{/lang}</small>
    <a href="#" class="forwardButtonEnhanced" onclick="return false;">
        <span class="forwardButtonEnhanced__shadow"></span>
        <div class="forwardButtonEnhanced__content">
            <span class="forwardButtonEnhanced__text">Empfehlung ansehen</span>
            <fa-icon name="arrow-right" size="16" class="forwardButtonEnhanced__icon"></fa-icon>
        </div>
    </a>
</div>

