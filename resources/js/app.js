import './bootstrap';
import 'jquery-ui/dist/jquery-ui';
import { Toast } from 'bootstrap';

const autocompleteUrl = document.body.dataset.autocompleteUrl;

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Autocomplete for a name field that stores the id of the selected entry in a hidden field.
function autocompleteWithId(input, search, idField) {
    $(input).autocomplete({
        source: function(request, response) {
            $.getJSON(autocompleteUrl, { search: search, term: request.term }, function(data) {
                response(data.map(row => ({ label: row.name, id: row.id })));
            });
        },
        minLength: 1,
        delay: 200,
        select: function(event, ui) {
            $(idField).val(ui.item.id);
        },
        change: function(event, ui) {
            if (ui.item == null) {
                $(idField).val('');
            }
        }
    });
}

$(function() {
    // Messages (session flash) are shown as toasts.
    document.querySelectorAll('.toast').forEach(element => new Toast(element).show());

    // One confirmation dialog for all delete buttons.
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('deleteModalForm').action = button.dataset.deleteAction;
            document.getElementById('deleteModalText').textContent = button.dataset.deleteMessage;
        });
    }

    // Sale details are only needed for sold records.
    $('#sold').on('change', function() {
        $('#sold-fields').toggleClass('d-none', !this.checked);
    });

    // Filters in the record list are applied immediately.
    $('[data-auto-submit]').on('change', function() {
        this.form.requestSubmit();
    });

    // Preview of a new cover image.
    $('#cover').on('change', function() {
        const file = this.files[0];
        if (file) {
            $('#cover-preview').attr('src', URL.createObjectURL(file)).removeClass('d-none');
        }
    });

    const detailRoutes = {
        record: '/records/',
        artist: '/artists/',
        label: '/labels/',
    };

    $('#search').autocomplete({
        source: function(request, response) {
            $.getJSON(autocompleteUrl, { search: 'all', term: request.term }, function(data) {
                response(data.map(row => ({ label: row.name, type: row.type, id: row.id })));
            });
        },
        minLength: 2,
        delay: 200,
        select: function(event, ui) {
            if (detailRoutes[ui.item.type]) {
                window.location.href = detailRoutes[ui.item.type] + encodeURIComponent(ui.item.id);
            }
        }
    });

    autocompleteWithId('#artist_name', 'artist', '#artist_id');
    autocompleteWithId('#label_name', 'label', '#label_id');
    autocompleteWithId('#country_name', 'country', '#country_id');
    autocompleteWithId('#platform', 'platform', '#platform_id');

    $('#title').autocomplete({
        source: function(request, response) {
            $.getJSON(autocompleteUrl, {
                search: 'title',
                term: request.term,
                artist_id: $('#artist_id').val()
            }, function(data) {
                response(data.map(row => ({
                    label: row.archive_number ? row.title + ' - Archiv-Nr.: ' + row.archive_number : row.title,
                    value: row.title
                })));
            });
        },
        minLength: 1,
        delay: 200
    });
});

