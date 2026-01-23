define(["require", "exports", "tslib", "WoltLabSuite/Core/Language"], function (require, exports, tslib_1, Language) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.init = init;
    exports.initList = initList;
    Language = tslib_1.__importStar(Language);
    /**
     * Formats seconds into countdown string (DD:HH:MM:SS).
     *
     * Converts total seconds into a formatted string with days, hours, minutes,
     * and seconds, each zero-padded to 2 digits.
     *
     * @param   {number}  seconds  Total remaining seconds
     * @returns {string}           Formatted countdown string (DD:HH:MM:SS)
     */
    function formatCountdown(seconds) {
        const totalSeconds = Math.floor(seconds);
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const secs = totalSeconds % 60;
        return `${String(days).padStart(2, "0")}:${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
    }
    /**
     * Initializes the countdown timer for a single element.
     *
     * Sets up a countdown timer that updates every second. When the countdown
     * expires, displays an "expired" message and stops the interval.
     *
     * @param   {string}  elementId        ID of the DOM element to update
     * @param   {number}  initialSeconds   Initial remaining seconds from server
     * @returns {void}
     */
    function init(elementId, initialSeconds) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.error(`DiscountCountdown: Element #${elementId} not found`);
            return;
        }
        let remainingSeconds = initialSeconds;
        updateDisplay(element, remainingSeconds);
        const interval = window.setInterval(() => {
            remainingSeconds--;
            if (remainingSeconds <= 0) {
                element.textContent = Language.get("wcf.shrinkr.countdown.expired");
                window.clearInterval(interval);
            }
            else {
                updateDisplay(element, remainingSeconds);
            }
        }, 1000);
    }
    /**
     * Updates the countdown display element.
     *
     * Formats the remaining seconds and updates the element's text content.
     *
     * @param   {HTMLElement}  element  DOM element to update
     * @param   {number}       seconds  Remaining seconds
     * @returns {void}
     */
    function updateDisplay(element, seconds) {
        element.textContent = formatCountdown(seconds);
    }
    /**
     * Initializes all countdowns in a list (e.g., ACP discount list).
     *
     * Finds all elements with class "discount-countdown" and data-end-time attribute.
     * Calculates remaining time from end timestamp and sets up individual countdown
     * timers for each element. Updates visual state (green/red) based on expiration.
     *
     * @returns {void}
     */
    function initList() {
        const countdownElements = document.querySelectorAll(".discount-countdown[data-end-time]");
        countdownElements.forEach((element) => {
            const endTimeStr = element.dataset.endTime;
            if (!endTimeStr) {
                return;
            }
            const endTime = parseInt(endTimeStr, 10);
            if (isNaN(endTime) || endTime <= 0) {
                return;
            }
            const displayElement = element.querySelector(".countdown-display");
            if (!displayElement) {
                return;
            }
            // Calculate initial remaining seconds
            const now = Math.floor(Date.now() / 1000);
            let remainingSeconds = endTime - now;
            // Update immediately
            if (remainingSeconds > 0) {
                updateDisplay(displayElement, remainingSeconds);
            }
            else {
                displayElement.textContent = Language.get("wcf.shrinkr.countdown.expired");
                element.classList.remove("green");
                element.classList.add("red");
                return;
            }
            // Update every second
            const interval = window.setInterval(() => {
                const currentTime = Math.floor(Date.now() / 1000);
                remainingSeconds = endTime - currentTime;
                if (remainingSeconds <= 0) {
                    displayElement.textContent = Language.get("wcf.shrinkr.countdown.expired");
                    element.classList.remove("green");
                    element.classList.add("red");
                    window.clearInterval(interval);
                }
                else {
                    updateDisplay(displayElement, remainingSeconds);
                }
            }, 1000);
        });
    }
});
