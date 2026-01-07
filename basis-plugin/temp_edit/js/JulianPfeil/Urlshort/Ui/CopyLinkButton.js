/**
 * Handles copyLinkButtons.
 *
 * @author  Julian Pfeil <https://julian-pfeil.de>
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Ui/Notification", "WoltLabSuite/Core/Clipboard", "WoltLabSuite/Core/Language"], function (require, exports, tslib_1, UiNotification, Clipboard, Language) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = exports.tkCopyTextToClipboard = void 0;
    UiNotification = tslib_1.__importStar(UiNotification);
    Clipboard = tslib_1.__importStar(Clipboard);
    Language = tslib_1.__importStar(Language);
    const tkCopyTextToClipboard = async (str) => {
        try {
            await Clipboard.copyTextToClipboard(str);
            UiNotification.show(Language.get('wcf.urlshort.copyUrl.success'), null, "success");
        }
        catch (error) {
            UiNotification.show(Language.get('wcf.urlshort.copyUrl.error'), null, "error");
            console.log(error.toString());
        }
    };
    exports.tkCopyTextToClipboard = tkCopyTextToClipboard;
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
        (0, exports.tkCopyTextToClipboard)(button.dataset.copyLink);
    }
});
