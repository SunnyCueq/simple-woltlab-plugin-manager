/**
 * Handles discount code copying functionality.
 *
 * @author Julian Pfeil
 * @module Benjaro/Urlshort/Ui/DiscountCodes
 */
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
define(["require", "exports", "WoltLabSuite/Core/Clipboard", "WoltLabSuite/Core/Ui/Notification", "WoltLabSuite/Core/Language"], function (require, exports, Clipboard, UiNotification, Language) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    Clipboard = __importStar(Clipboard);
    UiNotification = __importStar(UiNotification);
    Language = __importStar(Language);
    /**
     * Copies text to clipboard and shows notification
     */
    async function copyTextToClipboard(text) {
        try {
            await Clipboard.copyTextToClipboard(text);
            UiNotification.show(Language.get("wcf.urlshort.copyUrl.success"), null, "success");
        }
        catch (error) {
            UiNotification.show(Language.get("wcf.urlshort.copyUrl.error"), null, "error");
            console.log(error?.toString());
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
