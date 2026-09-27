// Discogs search in the record form: search releases, pick one and fill in the form.
// All texts from Discogs are inserted with textContent, never as HTML.

function element(tag, className, text) {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (text !== undefined && text !== null) el.textContent = text;
    return el;
}

async function getJson(url) {
    const response = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(data.error || data.message || response.statusText);
    }
    return data;
}

function setValue(id, value) {
    const input = document.getElementById(id);
    if (input && value !== undefined && value !== null) {
        input.value = value;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function fillForm(data) {
    ['title', 'artist_name', 'label_name', 'catalog_number', 'barcode', 'matrix_number',
        'country_name', 'release_year', 'reissue_year', 'discogs_release_id'].forEach(field => setValue(field, data[field]));

    // Names changed, the ids of the autocomplete belong to the old names.
    ['artist_id', 'label_id', 'country_id'].forEach(field => setValue(field, ''));

    if (data.kind) {
        const radio = document.getElementById('kind-' + data.kind);
        if (radio) radio.checked = true;
    }

    (data.editions || []).forEach(id => {
        const checkbox = document.getElementById('edition-' + id);
        if (checkbox) checkbox.checked = true;
    });

    const coverUrl = document.getElementById('discogs_cover_url');
    const preview = document.getElementById('cover-preview');
    const upload = document.getElementById('cover');
    if (coverUrl && data.cover_url && !(upload && upload.files.length)) {
        coverUrl.value = data.cover_url;
        if (preview) {
            preview.src = data.cover_url;
            preview.referrerPolicy = 'no-referrer';
            preview.classList.remove('d-none');
        }
    }
}

export function initDiscogs() {
    const card = document.getElementById('discogs-card');
    const button = document.getElementById('discogs-search');
    if (!card || !button) return;

    const texts = JSON.parse(card.dataset.texts || '{}');
    const query = document.getElementById('discogs-query');
    const status = document.getElementById('discogs-status');
    const results = document.getElementById('discogs-results');

    const showStatus = (text, error = false) => {
        status.textContent = text || '';
        status.className = 'small mt-2 ' + (error ? 'text-danger' : 'text-body-secondary');
    };

    async function apply(id) {
        showStatus(texts.loading);
        try {
            const data = await getJson(card.dataset.releaseUrl + '/' + encodeURIComponent(id));
            fillForm(data);
            results.replaceChildren();
            showStatus(texts.applied);
        } catch (error) {
            showStatus(error.message || texts.error, true);
        }
    }

    function renderResults(items) {
        results.replaceChildren();
        items.forEach(item => {
            const row = element('div', 'list-group-item d-flex gap-3 align-items-center');
            if (item.thumb) {
                const img = element('img', 'rounded flex-shrink-0 object-fit-cover');
                img.src = item.thumb;
                img.alt = '';
                img.width = 48;
                img.height = 48;
                img.loading = 'lazy';
                img.referrerPolicy = 'no-referrer';
                row.append(img);
            } else {
                const placeholder = element('div', 'rounded bg-body-tertiary flex-shrink-0');
                placeholder.style.width = placeholder.style.height = '48px';
                row.append(placeholder);
            }

            const info = element('div', 'flex-grow-1');
            info.style.minWidth = '0';
            info.append(element('div', 'fw-semibold', item.title));
            info.append(element('div', 'small text-body-secondary',
                [item.format, item.label, item.catno, item.country, item.year].filter(Boolean).join(' · ')));
            if (item.url) {
                const link = element('a', 'small', texts.show);
                link.href = item.url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                info.append(link);
            }
            row.append(info);

            const choose = element('button', 'btn btn-sm btn-info flex-shrink-0', texts.apply);
            choose.type = 'button';
            choose.addEventListener('click', () => apply(item.id));
            row.append(choose);

            results.append(row);
        });
    }

    async function search() {
        const term = query.value.trim();
        if (!term) return;

        // Only digits: most likely a barcode (EAN/UPC), otherwise a free text search (also finds catalog numbers).
        const params = /^\d{8,14}$/.test(term.replace(/\s/g, '')) ? { barcode: term.replace(/\s/g, '') } : { q: term };
        showStatus(texts.searching);
        results.replaceChildren();
        try {
            const data = await getJson(card.dataset.searchUrl + '?' + new URLSearchParams(params));
            renderResults(data.results || []);
            showStatus((data.results || []).length ? '' : texts.none);
        } catch (error) {
            showStatus(error.message || texts.error, true);
        }
    }

    button.addEventListener('click', search);
    query.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            search();
        }
    });
}

// Matching page: load suggestions for a record and link one of them.
export function initDiscogsMatching() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    document.querySelectorAll('[data-discogs-match]').forEach(card => {
        const texts = JSON.parse(card.dataset.texts || '{}');
        const status = card.querySelector('[data-status]');
        const results = card.querySelector('[data-results]');
        const button = card.querySelector('[data-load-suggestions]');
        if (!button) return;

        button.addEventListener('click', async () => {
            status.className = 'small mt-2 text-body-secondary';
            status.textContent = texts.loading;
            results.replaceChildren();
            try {
                const data = await getJson(card.dataset.suggestionsUrl);
                const items = data.results || [];
                status.textContent = items.length ? '' : texts.none;
                items.forEach(item => {
                    const row = element('div', 'list-group-item d-flex gap-3 align-items-center');
                    if (item.thumb) {
                        const img = element('img', 'rounded flex-shrink-0 object-fit-cover');
                        Object.assign(img, { src: item.thumb, alt: '', width: 40, height: 40, loading: 'lazy', referrerPolicy: 'no-referrer' });
                        row.append(img);
                    }
                    const info = element('div', 'flex-grow-1');
                    info.style.minWidth = '0';
                    info.append(element('div', 'fw-semibold small', item.title));
                    info.append(element('div', 'small text-body-secondary',
                        [item.format, item.label, item.catno, item.country, item.year].filter(Boolean).join(' · ')));
                    if (item.url) {
                        const link = element('a', 'small', texts.show);
                        Object.assign(link, { href: item.url, target: '_blank', rel: 'noopener noreferrer' });
                        info.append(link);
                    }
                    row.append(info);

                    const form = element('form', 'flex-shrink-0');
                    form.method = 'POST';
                    form.action = card.dataset.linkUrl;
                    [['_token', csrf], ['release_id', item.id], ['fill_missing', '1']].forEach(([name, value]) => {
                        const input = element('input');
                        Object.assign(input, { type: 'hidden', name, value });
                        form.append(input);
                    });
                    const submit = element('button', 'btn btn-sm btn-info', texts.link);
                    submit.type = 'submit';
                    form.append(submit);
                    row.append(form);

                    results.append(row);
                });
            } catch (error) {
                status.className = 'small mt-2 text-danger';
                status.textContent = error.message || texts.error;
            }
        });
    });
}
