import { Modal } from 'bootstrap';

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

const FIELDS = ['kind', 'artist_name', 'title', 'label_name', 'catalog_number', 'barcode', 'matrix_number',
    'country_name', 'release_year', 'reissue_year'];

// Hidden id fields of the autocomplete, they belong to the old name.
const ID_FIELDS = { artist_name: 'artist_id', label_name: 'label_id', country_name: 'country_id' };

function currentValue(field) {
    if (field === 'kind') {
        return document.querySelector('input[name=kind]:checked')?.value || '';
    }
    return (document.getElementById(field)?.value || '').trim();
}

function setField(field, value) {
    if (field === 'kind') {
        const radio = document.getElementById('kind-' + value);
        if (radio) radio.checked = true;
        return;
    }
    setValue(field, value);
    if (ID_FIELDS[field]) setValue(ID_FIELDS[field], '');
}

function hasCover() {
    return !!document.getElementById('remove_cover') || !!document.getElementById('cover')?.files.length;
}

function setCover(url) {
    const coverUrl = document.getElementById('discogs_cover_url');
    const preview = document.getElementById('cover-preview');
    if (!coverUrl) return;
    coverUrl.value = url;
    if (preview) {
        preview.src = url;
        preview.referrerPolicy = 'no-referrer';
        preview.classList.remove('d-none');
    }
}

/**
 * Differences between the form and the Discogs data. Empty form fields are no conflict, they are filled directly.
 */
function conflicts(data) {
    const list = [];
    FIELDS.forEach(field => {
        const discogs = data[field] === undefined || data[field] === null ? '' : String(data[field]);
        const current = currentValue(field);
        if (discogs !== '' && current !== '' && current.toLowerCase() !== discogs.toLowerCase()) {
            list.push({ field, current, discogs });
        }
    });
    if (data.cover_url && hasCover()) {
        list.push({ field: 'cover', current: null, discogs: data.cover_url });
    }
    return list;
}

function applyData(data, keep) {
    FIELDS.forEach(field => {
        const value = data[field];
        if (value !== undefined && value !== null && value !== '' && !keep.has(field)) {
            setField(field, value);
        }
    });
    setValue('discogs_release_id', data.discogs_release_id);

    (data.editions || []).forEach(id => {
        const checkbox = document.getElementById('edition-' + id);
        if (checkbox) checkbox.checked = true;
    });

    if (data.cover_url && !keep.has('cover')) {
        setCover(data.cover_url);
    }
}

function radioCell(name, value, checked, content) {
    const cell = element('td');
    const wrapper = element('div', 'form-check d-flex gap-2 align-items-center');
    const input = element('input', 'form-check-input');
    Object.assign(input, { type: 'radio', name, value, id: name + '-' + value, checked });
    const label = element('label', 'form-check-label text-break');
    label.htmlFor = input.id;
    label.append(content);
    wrapper.append(input, label);
    cell.append(wrapper);
    return cell;
}

function coverImage(src) {
    const img = element('img', 'rounded object-fit-cover');
    Object.assign(img, { src, alt: '', width: 64, height: 64, referrerPolicy: 'no-referrer' });
    return img;
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

    const modalElement = document.getElementById('discogs-compare');
    const modal = modalElement ? Modal.getOrCreateInstance(modalElement) : null;
    let pending = null;

    function showComparison(data, list) {
        const rows = document.getElementById('discogs-compare-rows');
        rows.replaceChildren();
        list.forEach(({ field, current, discogs }) => {
            const row = element('tr');
            const label = field === 'cover' ? texts.cover : (texts.fields || {})[field] || field;
            row.append(element('th', 'fw-semibold', label));
            const name = 'compare-' + field;
            if (field === 'cover') {
                const existing = document.querySelector('form .card img[alt^="Cover"]');
                row.append(radioCell(name, 'keep', true, existing ? coverImage(existing.src) : document.createTextNode(texts.cover)));
                row.append(radioCell(name, 'discogs', false, coverImage(discogs)));
            } else {
                row.append(radioCell(name, 'keep', true, document.createTextNode(current)));
                row.append(radioCell(name, 'discogs', false, document.createTextNode(discogs)));
            }
            rows.append(row);
        });
        pending = { data, list };
        modal.show();
    }

    document.getElementById('discogs-compare-apply')?.addEventListener('click', () => {
        if (!pending) return;
        const keep = new Set(pending.list
            .filter(({ field }) => document.querySelector('input[name="compare-' + field + '"]:checked')?.value !== 'discogs')
            .map(({ field }) => field));
        applyData(pending.data, keep);
        pending = null;
        modal.hide();
        results.replaceChildren();
        showStatus(texts.applied);
    });

    modalElement?.querySelectorAll('[data-compare-all]').forEach(button => {
        button.addEventListener('click', () => {
            modalElement.querySelectorAll('input[type=radio][value="' + button.dataset.compareAll + '"]')
                .forEach(radio => { radio.checked = true; });
        });
    });

    async function apply(id) {
        showStatus(texts.loading);
        try {
            const data = await getJson(card.dataset.releaseUrl + '/' + encodeURIComponent(id));
            const list = conflicts(data);
            if (list.length && modal) {
                showStatus('');
                showComparison(data, list);
                return;
            }
            applyData(data, new Set());
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
    // A scanned barcode (button with data-barcode-scan="discogs") is searched right away.
    document.addEventListener('barcode:discogs', event => {
        query.value = event.detail.code;
        search();
    });
    if (query.hasAttribute('data-autosearch') && query.value) {
        search();
    }
    query.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            search();
        }
    });
}

// Matching page: load suggestions for a record and link one of them.
export function initDiscogsMatching() {
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

                    const review = element('a', 'btn btn-sm btn-info flex-shrink-0', texts.link);
                    review.href = card.dataset.reviewUrl + '?' + new URLSearchParams({ release_id: item.id });
                    row.append(review);

                    results.append(row);
                });
            } catch (error) {
                status.className = 'small mt-2 text-danger';
                status.textContent = error.message || texts.error;
            }
        });
    });
}

// Review page: choose the existing or the Discogs value for all fields at once.
export function initDiscogsReview() {
    document.querySelectorAll('#discogs-review [data-choose-all]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('#discogs-review input[type=radio][value="' + button.dataset.chooseAll + '"]')
                .forEach(radio => { radio.checked = true; });
        });
    });
}
