// Barcode scanner for record barcodes (EAN-13, EAN-8, UPC-A, UPC-E) with the camera of phones and tablets.
// ZXing is only loaded when the scanner is used.

async function createReader() {
    const [{ BrowserMultiFormatReader }, { BarcodeFormat, DecodeHintType }] = await Promise.all([
        import('@zxing/browser'),
        import('@zxing/library'),
    ]);
    const hints = new Map([
        [DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.EAN_13, BarcodeFormat.EAN_8, BarcodeFormat.UPC_A, BarcodeFormat.UPC_E]],
        [DecodeHintType.TRY_HARDER, true],
    ]);
    return new BrowserMultiFormatReader(hints, { delayBetweenScanAttempts: 150 });
}

/**
 * Live scanning is only possible in a secure context (https or localhost) with camera support.
 */
export function canScanLive() {
    return window.isSecureContext && !!navigator.mediaDevices?.getUserMedia;
}

/**
 * Starts the camera in the video element. Resolves with the controls, onResult is called once with the code.
 */
export async function startScanner(video, onResult) {
    const reader = await createReader();
    let done = false;
    const controls = await reader.decodeFromConstraints(
        { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } },
        video,
        (result, error, scannerControls) => {
            if (result && !done) {
                done = true;
                scannerControls.stop();
                onResult(result.getText());
            }
        },
    );
    return controls;
}

/**
 * Reads the barcode from a photo (fallback without live camera). Resolves with the code or null.
 */
export async function scanImageFile(file) {
    const reader = await createReader();
    const url = URL.createObjectURL(file);
    try {
        return (await reader.decodeFromImageUrl(url)).getText();
    } catch {
        return null;
    } finally {
        URL.revokeObjectURL(url);
    }
}
