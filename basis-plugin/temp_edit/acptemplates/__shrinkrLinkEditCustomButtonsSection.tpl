{* Template wird von TemplateFormNode gerendert, daher kein äußerer section-Wrapper nötig *}
<header class="sectionHeader">
    <p class="sectionDescription">{lang}wcf.shrinkr.customButton.section.description{/lang}</p>
</header>

{if $urlCustomButtons|isset && $urlCustomButtons|count > 0}
    <section id="customButtonsList" class="sortableListContainer">
        <ol class="sortableList jsObjectActionContainer" data-object-action-class-name="shrinkr\data\custombutton\CustomButtonAction" data-object-id="0" start="1">
            {foreach from=$urlCustomButtons item=customButton}
                <li class="sortableNode sortableNoNesting jsObjectActionObject" data-object-id="{#$customButton->customButtonID}">
                    <span class="sortableNodeLabel">
                        <a href="{link controller='CustomButtonEdit' application='shrinkr' id=$customButton->customButtonID}{/link}">{$customButton->title}</a>
                        <small class="badge">{$customButton->targetUrl|truncate:50}</small>
                        
                        <span class="statusDisplay sortableButtonContainer">
                            <span class="sortableNodeHandle">
                                {icon size=16 name='arrows-up-down-left-right'}
                            </span>
                            <a href="{link controller='CustomButtonEdit' application='shrinkr' id=$customButton->customButtonID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">
                                {icon size=16 name='pencil'}
                            </a>
                            {objectAction action="delete" objectTitle=$customButton->getTitle()}
                            {event name='rowButtons'}
                        </span>
                    </span>
                    <ol class="sortableList" data-object-id="{#$customButton->customButtonID}"></ol>
                </li>
            {/foreach}
        </ol>
    </section>
    
    <div class="formSubmit">
        <button type="button" class="button buttonPrimary" data-type="submit">{icon size=16 name='check'} <span>{lang}wcf.global.button.saveSorting{/lang}</span></button>
    </div>
    
    <script data-relocate="true">
        require(['WoltLabSuite/Core/Ui/Sortable/List'], function(UiSortableList) {
            new UiSortableList({
                containerId: 'customButtonsList',
                className: 'shrinkr\\data\\custombutton\\CustomButtonAction',
                offset: 0
            });
        });
    </script>
{else}
    <p class="info">{lang}wcf.shrinkr.customButton.noItemsInUrl{/lang}</p>
{/if}

