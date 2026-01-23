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
     * Initializes trigger.
     */
    function setup() {
        document.querySelectorAll(".copyUrlButton").forEach((button) => {
            button.addEventListener("click", (ev) => click(ev));
        });
    }
    /**
     * Copies link
     */
    function click(event) {
        const button = event.currentTarget;
        if (button.dataset.copyLink) {
            copyTextToClipboard(button.dataset.copyLink);
        }
    }
});
