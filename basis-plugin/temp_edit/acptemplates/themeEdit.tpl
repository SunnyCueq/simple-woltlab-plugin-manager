{include file='header' pageTitle='shrinkr.acp.menu.link.theme.edit'}

{assign var='theme' value=$formObject}
{assign var='hasCssFile' value=$theme->hasCssFile()}

{if $hasCssFile}
    <div class="section tabMenuContainer">
        <nav class="tabMenu">
            <ul>
                <li><a href="#themeData">{lang}wcf.global.form.data{/lang}</a></li>
                <li><a href="#themeCss">{lang}wcf.shrinkr.theme.css.edit{/lang}</a></li>
            </ul>
        </nav>
        
        <div id="themeData" class="tabMenuContent">
        </div>
        
        <div id="themeCss" class="tabMenuContent">
        </div>
    </div>
    
    {unsafe:$form->getHtml()}
    
    <script data-relocate="true">
        require(['WoltLabSuite/Core/Ui/TabMenu'], function(UiTabMenu) {
            setTimeout(function() {
                var dataContainer = document.getElementById('data');
                var cssContainer = document.getElementById('css');
                var themeDataTab = document.getElementById('themeData');
                var themeCssTab = document.getElementById('themeCss');
                
                if (!dataContainer || !cssContainer || !themeDataTab || !themeCssTab) {
                    return;
                }
                
                if (dataContainer.parentNode) {
                    dataContainer.parentNode.removeChild(dataContainer);
                }
                themeDataTab.appendChild(dataContainer);
                
                if (cssContainer.parentNode) {
                    cssContainer.parentNode.removeChild(cssContainer);
                }
                themeCssTab.appendChild(cssContainer);
                
                UiTabMenu.init();
                
                setTimeout(function() {
                    var cssTextarea = themeCssTab.querySelector('textarea[name="values[cssContent]"]');
                    
                    if (cssTextarea && !cssTextarea.codemirror) {
                        require(['codemirror', 'codemirror/mode/css/css'], function(CodeMirror) {
                            if (!cssTextarea.codemirror) {
                                cssTextarea.codemirror = CodeMirror.fromTextArea(cssTextarea, {
                                    mode: 'css',
                                    lineNumbers: true,
                                    lineWrapping: true,
                                    indentUnit: 2,
                                    tabSize: 2,
                                    indentWithTabs: false
                                });
                            }
                        });
                    } else if (cssTextarea && cssTextarea.codemirror) {
                        cssTextarea.codemirror.refresh();
                    }
                }, 100);
            }, 500);
        });
    </script>
    
    <script data-relocate="true">
        require(['WoltLabSuite/Core/Ui/TabMenu'], function(UiTabMenu) {
            setTimeout(function() {
                var cssTextarea = document.querySelector('#themeCss textarea[name="values[cssContent]"]');
                if (cssTextarea && cssTextarea.codemirror) {
                    cssTextarea.codemirror.refresh();
                }
            }, 1000);
        });
    </script>
{else}
    {unsafe:$form->getHtml()}
{/if}

{hascontent}
	<footer class="contentFooter">
		<nav class="contentFooterNavigation">
			<ul>
				{content}{event name='contentFooterNavigation'}{/content}
			</ul>
		</nav>
	</footer>
{/hascontent}

{include file='footer'}

