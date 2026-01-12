/**
 * Guest reaction handler for URL shortener.
 *
 * Extends WoltLab's UiReactionHandler to support guest reactions via GuestReactionAction.
 * Handles data conversion and Web Component updates for reaction summaries.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @module      Shrinkr/Ui/GuestReactionHandler
 * @since       2.3.6
 */
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
define(["require", "exports", "WoltLabSuite/Core/Ui/Reaction/Handler"], function (require, exports, Handler_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    exports.createHandler = createHandler;
    Handler_1 = __importDefault(Handler_1);
    /**
     * Converts reaction data from server format to Map format required by Web Component.
     * Handles both object format ({reactionCount: number}) and direct number format.
     *
     * @param {Record<string, number | ReactionDataValue>} reactions - Reaction data from server
     * @returns {Map<number, number>} Map of reaction type ID to count
     */
    function convertReactionsToMap(reactions) {
        const reactionsMap = new Map();
        Object.entries(reactions).forEach((entry) => {
            const key = entry[0];
            const value = entry[1];
            // Extract reactionCount from object (if present), otherwise use value directly
            const reactionCount = (typeof value === 'object' && value !== null && 'reactionCount' in value)
                ? value.reactionCount || 0
                : (typeof value === 'number' ? value : 0);
            reactionsMap.set(parseInt(key, 10), reactionCount);
        });
        return reactionsMap;
    }
    /**
     * Updates the Web Component with new reaction data.
     *
     * @param {string} objectType - Object type identifier
     * @param {number} objectId - Object ID
     * @param {Map<number, number>} reactionsMap - Map of reaction type IDs to counts
     * @param {number} reactionTypeID - Selected reaction type ID
     * @returns {void}
     */
    function updateReactionComponent(objectType, objectId, reactionsMap, reactionTypeID) {
        const selector = `woltlab-core-reaction-summary[object-type="${objectType}"][object-id="${objectId}"]`;
        const component = document.querySelector(selector);
        if (component && typeof component.setData === 'function') {
            component.setData(reactionsMap, reactionTypeID);
        }
    }
    /**
     * Setup function for backwards compatibility.
     * Called by template to ensure module is loaded.
     * For guests, use createHandler() instead.
     */
    function setup() {
        console.log('[GuestReactionHandler] setup() called - use createHandler() for guest reactions');
    }
    /**
     * Creates and configures a guest reaction handler.
     *
     * The handler extends WoltLab's standard UiReactionHandler to:
     * - Use GuestReactionAction instead of standard ReactionAction
     * - Convert reaction data format for Web Component compatibility
     * - Update both Web Component and button state
     *
     * @param {GuestReactionHandlerOptions} options - Configuration options
     * @returns {UiReactionHandler} Configured reaction handler instance
     */
    function createHandler(options) {
        console.log('[GuestReactionHandler] createHandler() called with:', options);
        const handler = new Handler_1.default(options.objectType, {
            containerSelector: options.containerSelector,
            buttonSelector: options.buttonSelector,
            isSingleItem: false
        });
        // Override _ajaxSetup to use GuestReactionAction
        // This ensures guest reactions are included in the response
        handler._ajaxSetup = function () {
            return {
                data: {
                    actionName: 'react',
                    className: 'shrinkr\\data\\reaction\\GuestReactionAction'
                }
            };
        };
        // Override _ajaxSuccess to handle data conversion and component updates
        // The server may return reactions as objects with reactionCount property
        // or as direct numbers, so we need to normalize the format
        const originalAjaxSuccess = handler._ajaxSuccess;
        handler._ajaxSuccess = function (data) {
            console.log('[GuestReactionHandler] _ajaxSuccess called with:', data);
            if (data.returnValues && data.returnValues.reactions) {
                const objectId = ~~data.returnValues.objectID;
                const reactionsMap = convertReactionsToMap(data.returnValues.reactions);
                // Update Web Component
                updateReactionComponent(options.objectType, objectId, reactionsMap, data.returnValues.reactionTypeID);
                // Update React button state
                if (typeof this._updateReactButton === 'function') {
                    this._updateReactButton(objectId, data.returnValues.reactionTypeID);
                }
            }
            else {
                // Fallback: Call original function if data format is unexpected
                if (originalAjaxSuccess) {
                    return originalAjaxSuccess.call(this, data);
                }
            }
        };
        console.log('[GuestReactionHandler] handler created successfully');
        return handler;
    }
    exports.default = { setup, createHandler };
});
