/**
 * Handles copy link buttons.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ui/Notification", "WoltLabSuite/Core/Clipboard", "WoltLabSuite/Core/Language"], function (require, exports, tslib_1, UiNotification, Clipboard, Language) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = exports.copyTextToClipboard = void 0;
    UiNotification = tslib_1.__importStar(UiNotification);
    Clipboard = tslib_1.__importStar(Clipboard);
    Language = tslib_1.__importStar(Language);
    const copyTextToClipboard = async (str) => {
        try {
            await Clipboard.copyTextToClipboard(str);
            UiNotification.show(Language.get('wcf.shrinkr.copyUrl.success'), null, "success");
        }
        catch (error) {
            UiNotification.show(Language.get('wcf.shrinkr.copyUrl.error'), null, "error");
            console.log(error.toString());
        }
    };
    exports.copyTextToClipboard = copyTextToClipboard;
    /**
     * Initializes trigger.
     */
    function setup() {
        document.querySelectorAll(".copyUrlButton").forEach((button) => {
            button.addEventListener("click", (ev) => click(ev));
        });
    }
    exports.setup = setup;
    /**
     * Copies link
     */
    function click(event) {
        const button = event.currentTarget;
        (0, exports.copyTextToClipboard)(button.dataset.copyLink);
    }
});
