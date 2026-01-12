<ol class="nativeList" start="0">
	{foreach from=$stackTrace item=stackEntry}
		{assign var=__args value=$stackEntry['args']}
		{if $__args|is_array}{assign var=__args value=$__args|json}{/if}
		<li>
			<strong>{$stackEntry['class']}</strong>{$stackEntry['type']}{$stackEntry['function']}({unsafe:$__args})<br>
			<small>{$stackEntry[file]} ({$stackEntry[line]})</small>
		</li>
	{/foreach}
</ol>
