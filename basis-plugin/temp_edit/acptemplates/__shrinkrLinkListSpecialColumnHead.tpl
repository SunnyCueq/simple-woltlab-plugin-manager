<th class="columnTitle columnSpecial text-center{if $sortField == 'special'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=special&sortOrder={if $sortField == 'special' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.special{/lang}</a></th>

