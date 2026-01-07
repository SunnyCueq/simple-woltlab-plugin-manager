<th class="columnTitle columnFeaturedLinks{if $sortField == 'featuredLinks'} active {unsafe:$sortOrder}{/if}"><a href="{link application='urlshort' controller='UrlList'}pageNo={unsafe:$pageNo}&sortField=featuredLinks&sortOrder={if $sortField == 'featuredLinks' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.urlshort.featuredLink.section{/lang}</a></th>

