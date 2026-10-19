// Warns while a new record is entered when the same record is probably already in the collection
// (same barcode, catalog number on the same label or Discogs release). The server checks again on saving.
const FIELDS = ['barcode', 'catalog_number', 'label_name', 'discogs_release_id'];

export function initDuplicateWarning() {
    const container = document.getElementById('duplicate-warning');
    if (!container) {
        return;
    }

    let timer = null;
    let lastQuery = null;

    const check = async () => {
        const params = new URLSearchParams();
        FIELDS.forEach(id => params.set(id, document.getElementById(id)?.value ?? ''));
        const query = params.toString();
        if (query === lastQuery) {
            return;
        }
        lastQuery = query;

        try {
            const response = await fetch(`${container.dataset.url}?${query}`, { headers: { Accept: 'application/json' } });
            if (!response.ok || query !== lastQuery) {
                return;
            }
            const confirmed = document.getElementById('confirm_duplicate')?.checked ?? false;
            container.innerHTML = (await response.json()).html;
            // Keep a ticked "save anyway" when the same records are shown again.
            const checkbox = document.getElementById('confirm_duplicate');
            if (checkbox) {
                checkbox.checked = confirmed;
            }
        } catch {
            // The warning is a help only; the server checks again when saving.
        }
    };

    FIELDS.forEach(id => {
        const input = document.getElementById(id);
        input?.addEventListener('change', () => {
            clearTimeout(timer);
            timer = setTimeout(check, 300);
        });
        input?.addEventListener('blur', () => {
            clearTimeout(timer);
            timer = setTimeout(check, 300);
        });
    });
}
