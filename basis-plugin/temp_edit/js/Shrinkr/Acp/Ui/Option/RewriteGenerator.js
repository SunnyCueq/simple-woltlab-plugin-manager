define(["require", "exports", "tslib", "WoltLabSuite/Core/Ajax", "WoltLabSuite/Core/Language", "WoltLabSuite/Core/Ui/Dialog"], function (require, exports, tslib_1, Ajax, Language, Dialog_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.init = init;
    Ajax = tslib_1.__importStar(Ajax);
    Language = tslib_1.__importStar(Language);
    Dialog_1 = tslib_1.__importDefault(Dialog_1);
    /**
     * Class for generating and displaying rewrite rules.
     */
    class RewriteGenerator {
        /**
         * The generate button element.
         *
         * @type {HTMLAnchorElement | null}
         */
        buttonGenerate = null;
        /**
         * The container element for the button and description.
         *
         * @type {HTMLElement | null}
         */
        container = null;
        /**
         * Initializes the generator for rewrite rules.
         *
         * Creates a button and description element, inserts them after the
         * "remove_urls_prefix" option in the ACP options form.
         *
         * @returns {void}
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
            if (insertAfter) {
                insertAfter.insertAdjacentElement("afterend", this.container);
            }
        }
        /**
         * Handles click events on the generate button.
         *
         * Prevents default link behavior and fires an AJAX request to generate
         * rewrite rules. The response is displayed in a dialog.
         *
         * @param   {Event}  event  The click event
         * @returns {void}
         */
        _onClick(event) {
            event.preventDefault();
            Ajax.api(this);
        }
        /**
         * Sets up the dialog configuration.
         *
         * Implements DialogCallbackObject interface. Returns dialog configuration
         * for displaying generated rewrite rules.
         *
         * @returns {ReturnType<DialogCallbackSetup>} Dialog configuration object
         */
        _dialogSetup() {
            return {
                id: "dialogShrinkrRewriteRules",
                source: null,
                options: {
                    title: Language.get("shrinkr.acp.rewrite.title"),
                },
            };
        }
        /**
         * Sets up the AJAX request configuration.
         *
         * Implements AjaxCallbackObject interface. Returns AJAX request configuration
         * for calling the generateRewriteRules action.
         *
         * @returns {ReturnType<AjaxCallbackSetup>} AJAX configuration object
         */
        _ajaxSetup() {
            return {
                data: {
                    actionName: "generateRewriteRules",
                    className: "shrinkr\\data\\option\\OptionAction",
                },
            };
        }
        /**
         * Handles successful AJAX response.
         *
         * Opens a dialog with the generated rewrite rules from the server response.
         *
         * @param   {AjaxResponse}  data  The AJAX response containing rewrite rules
         * @returns {void}
         */
        _ajaxSuccess(data) {
            Dialog_1.default.open(this, data.returnValues);
        }
    }
    /**
     * Singleton instance of RewriteGenerator.
     *
     * @type {RewriteGenerator | null}
     */
    let rewriteGenerator = null;
    /**
     * Initializes the rewrite rule generator.
     *
     * Creates a new RewriteGenerator instance if one doesn't exist yet.
     * This function should be called when the ACP options page loads.
     *
     * @returns {void}
     */
    function init() {
        if (!rewriteGenerator) {
            rewriteGenerator = new RewriteGenerator();
        }
    }
});
