{if $action == 'edit' && $urlID|isset}
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">
                {lang}wcf.urlshort.featuredLink.section{/lang}
                {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                    <span class="badge">{#$urlFeaturedLinks|count}</span>
                {/if}
            </h2>
            <p class="sectionDescription">{lang}wcf.urlshort.featuredLink.section.description{/lang}</p>
        </header>

        {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
            <div class="formSubmit">
                <a href="{link controller='FeaturedLinkList' application='urlshort'}urlID={#$urlID}{/link}" 
                   class="button">{icon size=16 name='list'} <span>{lang}urlshort.acp.menu.link.featuredLink.list{/lang}</span></a>
            </div>
        {/if}

        {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
            <section id="featuredLinksList" class="sortableListContainer">
                <ol class="sortableList jsObjectActionContainer" data-object-action-class-name="urlshort\data\featuredlink\FeaturedLinkAction" data-object-id="0" start="1">
                    {foreach from=$urlFeaturedLinks item=featuredLink}
                        <li class="sortableNode sortableNoNesting jsObjectActionObject" data-object-id="{#$featuredLink->linkID}">
                            <span class="sortableNodeLabel">
                                <a href="{link controller='FeaturedLinkEdit' application='urlshort' id=$featuredLink->linkID}{/link}">{$featuredLink->title}</a>
                                <small class="badge">{$featuredLink->getHost()}</small>
                                
                                <span class="statusDisplay sortableButtonContainer">
                                    <span class="sortableNodeHandle">
                                        {icon size=16 name='arrows-up-down-left-right'}
                                    </span>
                                    <a href="{link controller='FeaturedLinkEdit' application='urlshort' id=$featuredLink->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">
                                        {icon size=16 name='pencil'}
                                    </a>
                                    {objectAction action="delete" objectTitle=$featuredLink->getTitle()}
                                    {event name='rowButtons'}
                                </span>
                            </span>
                            <ol class="sortableList" data-object-id="{#$featuredLink->linkID}"></ol>
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
                        containerId: 'featuredLinksList',
                        className: 'urlshort\\data\\featuredlink\\FeaturedLinkAction',
                        offset: 0
                    });
                });
            </script>
        {else}
            <p class="info">{lang}wcf.urlshort.featuredLink.noItemsInUrl{/lang}</p>
        {/if}
    </section>
{/if}

