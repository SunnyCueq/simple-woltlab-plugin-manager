/**
 * Rewrite rule generation for Shr1nkr.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 */
define(["require", "exports", "tslib", "../../../../WoltLabSuite/Core/Ajax", "../../../../WoltLabSuite/Core/Language", "../../../../WoltLabSuite/Core/Ui/Dialog"], function (require, exports, tslib_1, Ajax, Language, Dialog_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.init = init;
    Ajax = tslib_1.__importStar(Ajax);
    Language = tslib_1.__importStar(Language);
    Dialog_1 = tslib_1.__importDefault(Dialog_1);
    
    class RewriteGenerator {
        buttonGenerate;
        container;
        
        /**
         * Initializes the generator for rewrite rules
         */
        constructor() {
            const removeUrlsPrefixOption = document.getElementById("shrinkr_remove_urls_prefix");
            if (removeUrlsPrefixOption === null) {
                return;
            }
            
            this.container = document.createElement("dl");
            const dt = document.createElement("dt");
            dt.classList.add("jsOnly");
            const dd = document.createElement("dd");
            
            this.buttonGenerate = document.createElement("a");
            this.buttonGenerate.className = "button";
            this.buttonGenerate.href = "#";
            this.buttonGenerate.textContent = Language.get("shrinkr.acp.rewrite.generate");
            this.buttonGenerate.addEventListener("click", (ev) => this._onClick(ev));
            dd.appendChild(this.buttonGenerate);
            
            const description = document.createElement("small");
            description.textContent = Language.get("shrinkr.acp.rewrite.description");
            dd.appendChild(description);
            
            this.container.appendChild(dt);
            this.container.appendChild(dd);
            
            const insertAfter = removeUrlsPrefixOption.closest("dl");
            insertAfter.insertAdjacentElement("afterend", this.container);
        }
        
        /**
         * Fires an AJAX request and opens the dialog
         */
        _onClick(event) {
            event.preventDefault();
            Ajax.api(this);
        }
        
        _dialogSetup() {
            return {
                id: "dialogShrinkrRewriteRules",
                source: null,
                options: {
                    title: Language.get("shrinkr.acp.rewrite.title"),
                },
            };
        }
        
        _ajaxSetup() {
            return {
                data: {
                    actionName: "generateRewriteRules",
                    className: "shrinkr\\data\\option\\OptionAction",
                },
            };
        }
        
        _ajaxSuccess(data) {
            Dialog_1.default.open(this, data.returnValues);
        }
    }
    
    let rewriteGenerator;
    
    function init() {
        if (!rewriteGenerator) {
            rewriteGenerator = new RewriteGenerator();
        }
    }
    
    exports.init = init;
});
