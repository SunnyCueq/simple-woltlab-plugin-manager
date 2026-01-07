/**
 * Creates qr code for links.
 *
 * @author  Julian Pfeil <https://julian-pfeil.de>
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 * @woltlabExcludeBundle  all
 */
define(["require", "exports", "tslib", "qr-creator"], function (require, exports, tslib_1, qr_creator_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.renderAll = exports.render = void 0;
    qr_creator_1 = tslib_1.__importDefault(qr_creator_1);
    function render(container) {
        // Enable container display
        container.setAttribute("style", "display:table-cell");
        
        const url = container.dataset.url;
        const downloadLink = container.querySelector("a.qrDownloadLink");
        if (!url || !downloadLink) {
            return;
        }
        
        // Setup click handler to generate QR code on demand
        downloadLink.addEventListener("click", function(event) {
            event.preventDefault();
            
            // Create a temporary hidden canvas for QR code generation
            const tempCanvas = document.createElement("canvas");
            tempCanvas.width = 550;
            tempCanvas.height = 550;
            tempCanvas.style.display = "none";
            document.body.appendChild(tempCanvas);
            
            try {
                // Render QR code on temporary canvas
                qr_creator_1.default.render({
                    text: url,
                    size: 550,
                }, tempCanvas);
                
                // Create download link
                const dataUrl = tempCanvas.toDataURL("image/png");
                const blob = dataURLtoBlob(dataUrl);
                const blobUrl = URL.createObjectURL(blob);
                
                // Create temporary download link and trigger download
                const tempLink = document.createElement("a");
                tempLink.href = blobUrl;
                tempLink.download = "qr.png";
                tempLink.style.display = "none";
                document.body.appendChild(tempLink);
                tempLink.click();
                
                // Cleanup
                setTimeout(function() {
                    document.body.removeChild(tempLink);
                    document.body.removeChild(tempCanvas);
                    URL.revokeObjectURL(blobUrl);
                }, 100);
            } catch (error) {
                console.error('QR Code generation failed:', error);
            }
        });
    }
    
    // Helper function to convert data URL to Blob
    function dataURLtoBlob(dataurl) {
        const arr = dataurl.split(",");
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new Blob([u8arr], { type: mime });
    }
    
    exports.render = render;
    exports.default = render;
    function renderAll() {
        // Enable table header display
        document.querySelectorAll("th.columnQR").forEach(function(el) {
            el.setAttribute("style", "display:table-cell");
        });
        
        // Render QR codes for all containers
        document.querySelectorAll("td.columnQR").forEach(function(el) {
            render(el);
        });
    }
    exports.renderAll = renderAll;
});
