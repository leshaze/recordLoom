import { Modal } from 'bootstrap';

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

/**
 * Buttons with data-barcode-scan open the scanner dialog (or the camera app without live camera).
 * data-barcode-scan="collection": open the record with this barcode in the collection.
 * data-barcode-scan="<name>": dispatch the event "barcode:<name>" with the code, e.g. for the Discogs search.
 */
export function initBarcodeButtons() {
    const modalElement = document.getElementById('barcode-modal');
    const buttons = document.querySelectorAll('[data-barcode-scan]');
    if (!modalElement || !buttons.length) return;

    const texts = JSON.parse(modalElement.dataset.texts || '{}');
    const modal = Modal.getOrCreateInstance(modalElement);
    const video = document.getElementById('barcode-video');
    const photo = document.getElementById('barcode-photo');
    const error = document.getElementById('barcode-error');
    let controls = null;
    let target = null;

    const showError = text => {
        error.textContent = text || '';
        error.classList.toggle('d-none', !text);
    };

    const stop = () => {
        controls?.stop();
        controls = null;
    };

    const found = code => {
        navigator.vibrate?.(80);
        stop();
        modal.hide();
        if (target === 'collection') {
            window.location.href = modalElement.dataset.collectionUrl + '?' + new URLSearchParams({ code });
        } else {
            document.dispatchEvent(new CustomEvent('barcode:' + target, { detail: { code } }));
        }
    };

    buttons.forEach(button => button.addEventListener('click', () => {
        target = button.dataset.barcodeScan;
        showError('');
        if (canScanLive()) {
            modal.show();
        } else {
            // No live camera (e.g. plain http): take a photo with the camera app instead.
            photo.click();
        }
    }));

    modalElement.addEventListener('shown.bs.modal', async () => {
        if (!canScanLive()) return;
        try {
            controls = await startScanner(video, found);
        } catch {
            showError(texts.noCamera);
        }
    });
    modalElement.addEventListener('hidden.bs.modal', stop);

    photo.addEventListener('change', async () => {
        const file = photo.files[0];
        photo.value = '';
        if (!file) return;
        stop();
        if (!modalElement.classList.contains('show')) modal.show();
        showError('');
        error.classList.remove('d-none', 'text-danger');
        error.textContent = texts.reading;
        const code = await scanImageFile(file);
        error.classList.add('text-danger');
        if (code) {
            found(code);
        } else {
            showError(texts.notFound);
        }
    });
}
