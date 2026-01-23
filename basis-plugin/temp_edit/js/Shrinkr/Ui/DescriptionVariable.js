/**
 * Description variable inserter - Makes variables clickable to insert into textarea.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 */
define(["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.setup = setup;
    /**
     * Inserts variable at cursor position in textarea.
     *
     * @param textarea - The textarea element
     * @param variable - The variable to insert
     */
    function insertVariable(textarea, variable) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);
        // Insert variable at cursor position
        textarea.value = before + variable + after;
        // Set cursor position after inserted variable
        const newPosition = start + variable.length;
        textarea.setSelectionRange(newPosition, newPosition);
        // Focus textarea
        textarea.focus();
        // Trigger input event for form validation
        textarea.dispatchEvent(new Event("input", { bubbles: true }));
    }
    /**
     * Initializes clickable variable elements.
     */
    function setup() {
        // Find the descriptionText textarea (FormBuilder uses prefixed IDs)
        // Try different possible ID formats
        let textarea = document.getElementById("descriptionText");
        if (!textarea) {
            textarea = document.querySelector('textarea[name="descriptionText"]');
        }
        if (!textarea) {
            textarea = document.querySelector('textarea[id*="descriptionText"]');
        }
        if (!textarea) {
            return;
        }
        // Find all clickable variable elements
        const elements = document.querySelectorAll(".descriptionVariable");
        elements.forEach((element) => {
            const variable = element.dataset.variable;
            if (!variable) {
                console.error("DescriptionVariable: Missing data-variable attribute");
                return;
            }
            // Click event (styling handled by CSS: .descriptionVariable.badge)
            element.addEventListener("click", (event) => {
                event.preventDefault();
                insertVariable(textarea, variable);
            });
        });
    }
});
