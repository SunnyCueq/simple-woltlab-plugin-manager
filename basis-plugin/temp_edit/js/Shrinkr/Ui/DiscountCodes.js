define(["require", "exports", "tslib", "WoltLabSuite/Core/Clipboard", "WoltLabSuite/Core/Ui/Notification", "WoltLabSuite/Core/Language"], function (require, exports, tslib_1, Clipboard, UiNotification, Language) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    Clipboard = tslib_1.__importStar(Clipboard);
    UiNotification = tslib_1.__importStar(UiNotification);
    Language = tslib_1.__importStar(Language);
    /**
     * Copies text to clipboard and shows notification.
     *
     * Attempts to copy the given text to the clipboard using WoltLab's Clipboard API.
     * Shows a success notification on success or an error notification on failure.
     *
     * @param   {string}  text  The text to copy to clipboard
     * @returns {Promise<void>} Promise that resolves when the operation completes
     * @throws  {Error}        If clipboard operation fails
     */
    async function copyTextToClipboard(text) {
        try {
            await Clipboard.copyTextToClipboard(text);
            UiNotification.show(Language.get("wcf.shrinkr.copyUrl.success"), null, "success");
        }
        catch (error) {
            UiNotification.show(Language.get("wcf.shrinkr.copyUrl.error"), null, "error");
        }
    }
    /**
     * Initializes discount code copying functionality.
     *
     * Sets up event listeners for all elements with the "copyableCode" class.
     * Prevents duplicate listeners by checking the data-has-copy-listener attribute.
     * When clicked, these elements will copy the code from their data-code attribute.
     *
     * @returns {void}
     */
    function setup() {
        document.querySelectorAll(".copyableCode").forEach((element) => {
            if (element.dataset.hasCopyListener === "true") {
                return;
            }
            element.dataset.hasCopyListener = "true";
            element.addEventListener("click", (event) => {
                event.preventDefault();
                const code = element.dataset.code;
                if (code) {
                    copyTextToClipboard(code);
                }
            });
        });
    }
    exports.default = { setup };
});
