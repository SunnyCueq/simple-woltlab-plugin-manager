{if $action == 'edit' && $linkID|isset}
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">
                {lang}wcf.shrinkr.featuredLink.section{/lang}
                {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
                    <span class="badge">{#$urlFeaturedLinks|count}</span>
                {/if}
            </h2>
            <p class="sectionDescription">{lang}wcf.shrinkr.featuredLink.section.description{/lang}</p>
        </header>

        {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
            <div class="formSubmit">
                <a href="{link controller='FeaturedLinkList' application='shrinkr'}linkID={#$linkID}{/link}" 
                   class="button">{icon size=16 name='list'} <span>{lang}shrinkr.acp.menu.link.featuredLink.list{/lang}</span></a>
            </div>
        {/if}

        {if $urlFeaturedLinks|isset && $urlFeaturedLinks|count > 0}
            <section id="featuredLinksList" class="sortableListContainer">
                <ol class="sortableList jsObjectActionContainer" data-object-action-class-name="shrinkr\data\featuredlink\FeaturedLinkAction" data-object-id="0" start="1">
                    {foreach from=$urlFeaturedLinks item=featuredLink}
                        <li class="sortableNode sortableNoNesting jsObjectActionObject" data-object-id="{#$featuredLink->linkID}">
                            <span class="sortableNodeLabel">
                                <a href="{link controller='FeaturedLinkEdit' application='shrinkr' id=$featuredLink->linkID}{/link}">{$featuredLink->title}</a>
                                <small class="badge">{$featuredLink->getHost()}</small>
                                
                                <span class="statusDisplay sortableButtonContainer">
                                    <span class="sortableNodeHandle">
                                        {icon size=16 name='arrows-up-down-left-right'}
                                    </span>
                                    <a href="{link controller='FeaturedLinkEdit' application='shrinkr' id=$featuredLink->linkID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">
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
                        className: 'shrinkr\\data\\featuredlink\\FeaturedLinkAction',
                        offset: 0
                    });
                });
            </script>
        {else}
            <p class="info">{lang}wcf.shrinkr.featuredLink.noItemsInUrl{/lang}</p>
        {/if}
    </section>
{/if}

