define(["require", "exports", "tslib", "WoltLabSuite/Core/Clipboard", "WoltLabSuite/Core/Ui/Notification", "WoltLabSuite/Core/Language"], function (require, exports, tslib_1, Clipboard, UiNotification, Language) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    Clipboard = tslib_1.__importStar(Clipboard);
    UiNotification = tslib_1.__importStar(UiNotification);
    Language = tslib_1.__importStar(Language);
    /**
     * Copies text to clipboard and shows notification
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
    function setup() {
        // Setup copyableCode elements - click on <kbd class="copyableCode"> to copy
        document.querySelectorAll(".copyableCode").forEach((element) => {
            // Skip if already has event listener
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
