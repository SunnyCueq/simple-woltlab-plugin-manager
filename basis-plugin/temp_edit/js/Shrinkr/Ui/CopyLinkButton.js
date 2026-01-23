define(["require", "exports", "tslib", "WoltLabSuite/Core/Ui/Notification", "WoltLabSuite/Core/Clipboard", "WoltLabSuite/Core/Language"], function (require, exports, tslib_1, UiNotification, Clipboard, Language) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.copyTextToClipboard = copyTextToClipboard;
    exports.setup = setup;
    UiNotification = tslib_1.__importStar(UiNotification);
    Clipboard = tslib_1.__importStar(Clipboard);
    Language = tslib_1.__importStar(Language);
    /**
     * Copies text to clipboard and shows notification.
     *
     * Attempts to copy the given text to the clipboard using WoltLab's Clipboard API.
     * Shows a success notification on success or an error notification on failure.
     *
     * @param   {string}  str  The text to copy to clipboard
     * @returns {Promise<void>} Promise that resolves when the operation completes
     * @throws  {Error}        If clipboard operation fails
     */
    async function copyTextToClipboard(str) {
        try {
            await Clipboard.copyTextToClipboard(str);
            UiNotification.show(Language.get("wcf.shrinkr.copyUrl.success"), null, "success");
        }
        catch (error) {
            UiNotification.show(Language.get("wcf.shrinkr.copyUrl.error"), null, "error");
        }
    }
    /**
     * Initializes copy button functionality.
     *
     * Sets up event listeners for all elements with the "copyUrlButton" class.
     * When clicked, these buttons will copy the URL from their data-copy-link attribute.
     *
     * @returns {void}
     */
    function setup() {
        document.querySelectorAll(".copyUrlButton").forEach((button) => {
            button.addEventListener("click", (ev) => click(ev));
        });
    }
    /**
     * Handles click events on copy buttons.
     *
     * Extracts the URL from the button's data-copy-link attribute and copies it
     * to the clipboard.
     *
     * @param   {Event}  event  The click event
     * @returns {void}
     */
    function click(event) {
        const button = event.currentTarget;
        if (button.dataset.copyLink) {
            copyTextToClipboard(button.dataset.copyLink);
        }
    }
});
