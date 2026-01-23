define(["require", "exports", "tslib", "qr-creator"], function (require, exports, tslib_1, qr_creator_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    qr_creator_1 = tslib_1.__importDefault(qr_creator_1);
    /**
     * Renders a QR code for a single table cell.
     *
     * Creates a hidden canvas element, renders the QR code using qr-creator,
     * and sets up download functionality for the QR code image.
     *
     * @param   {HTMLElement}  cell  The table cell containing the URL data (must have data-url attribute)
     * @returns {void}
     */
    function renderQrCode(cell) {
        const url = cell.dataset.url;
        if (!url) {
            return;
        }
        let canvas = cell.querySelector("canvas");
        if (!canvas) {
            canvas = document.createElement("canvas");
            canvas.width = 200;
            canvas.height = 200;
            canvas.style.display = "none";
            cell.insertBefore(canvas, cell.firstChild);
        }
        try {
            qr_creator_1.default.render({
                text: url,
                size: 200,
            }, canvas);
        }
        catch (e) {
            console.error("Failed to render QR code:", e);
            return;
        }
        const downloadButton = cell.querySelector(".qrDownloadLink");
        if (downloadButton && !downloadButton.dataset.qrInitialized) {
            downloadButton.dataset.qrInitialized = "true";
            downloadButton.addEventListener("click", (e) => {
                e.preventDefault();
                canvas.toBlob((blob) => {
                    if (!blob) {
                        console.error("Failed to create blob from canvas");
                        return;
                    }
                    const downloadUrl = URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = downloadUrl;
                    a.download = `qr-code-${Date.now()}.png`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(downloadUrl);
                }, "image/png");
            });
        }
    }
    /**
     * Public API for QR code rendering.
     */
    exports.default = {
        /**
         * Renders all QR codes in the current page.
         *
         * Finds all table cells with class "columnQR" and data-url attribute,
         * then renders QR codes for each of them.
         *
         * @returns {void}
         */
        renderAll() {
            document.querySelectorAll(".columnQR[data-url]").forEach(renderQrCode);
        },
        /**
         * Renders a QR code for a specific cell.
         *
         * Renders a QR code for a single table cell element.
         *
         * @param   {HTMLElement}  cell  The table cell element to render QR code for
         * @returns {void}
         */
        render(cell) {
            renderQrCode(cell);
        },
    };
});
