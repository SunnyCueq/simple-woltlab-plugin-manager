define(["require", "exports", "tslib", "qr-creator"], function (require, exports, tslib_1, qr_creator_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    qr_creator_1 = tslib_1.__importDefault(qr_creator_1);
    /**
     * Renders a QR code for a single table cell.
     *
     * @param cell The table cell containing the URL data
     */
    function renderQrCode(cell) {
        const url = cell.dataset.url;
        if (!url) {
            return;
        }
        // Create canvas element (if not already present)
        let canvas = cell.querySelector("canvas");
        if (!canvas) {
            canvas = document.createElement("canvas");
            canvas.width = 200;
            canvas.height = 200;
            canvas.style.display = "none"; // Hidden, only for download
            cell.insertBefore(canvas, cell.firstChild);
        }
        // Render QR code to canvas
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
        // Attach download handler to button
        const downloadButton = cell.querySelector(".qrDownloadLink");
        if (downloadButton && !downloadButton.dataset.qrInitialized) {
            downloadButton.dataset.qrInitialized = "true";
            downloadButton.addEventListener("click", (e) => {
                e.preventDefault();
                // Convert canvas to blob and trigger download
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
     * Public API
     */
    exports.default = {
        /**
         * Renders all QR codes in the current page.
         */
        renderAll() {
            document.querySelectorAll(".columnQR[data-url]").forEach(renderQrCode);
        },
        /**
         * Renders a QR code for a specific cell.
         *
         * @param cell The table cell
         */
        render(cell) {
            renderQrCode(cell);
        },
    };
});
