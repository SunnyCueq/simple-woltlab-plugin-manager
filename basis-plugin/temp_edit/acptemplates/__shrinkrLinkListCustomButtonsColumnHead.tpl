<th class="columnTitle columnCustomButtons text-center{if $sortField == 'customButtons'} active {unsafe:$sortOrder}{/if}"><a href="{link application='shrinkr' controller='ShrinkrLinkList'}pageNo={unsafe:$pageNo}&sortField=customButtons&sortOrder={if $sortField == 'customButtons' && $sortOrder == 'ASC'}DESC{else}ASC{/if}&q={$q}{/link}">{lang}wcf.shrinkr.customButton.section{/lang}</a></th>

