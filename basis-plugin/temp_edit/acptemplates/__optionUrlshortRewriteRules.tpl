{if $category->categoryName === 'urlshort.target'}
	<script data-relocate="true">
		require(['Language', 'Urlshort/Acp/Ui/Option/RewriteGenerator'], function (Language, UrlshortAcpUiOptionRewriteGenerator) {
			Language.addObject({
				'urlshort.acp.rewrite.title': '{jslang}urlshort.acp.rewrite.title{/jslang}',
				'urlshort.acp.rewrite.generate': '{jslang}urlshort.acp.rewrite.generate{/jslang}',
				'urlshort.acp.rewrite.description': '{jslang}urlshort.acp.rewrite.description{/jslang}'
			});

			UrlshortAcpUiOptionRewriteGenerator.init();
		});
	</script>
{/if}
