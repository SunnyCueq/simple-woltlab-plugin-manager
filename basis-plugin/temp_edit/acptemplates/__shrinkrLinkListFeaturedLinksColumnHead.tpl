<th class="columnTitle columnFeaturedLinks{if $sortField == 'featuredLinks'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=featuredLinks&sortOrder={if $sortField == 'featuredLinks' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.featuredLink.section{/lang}</a></th>

