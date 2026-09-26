## RecordLoom

A small CRM project to archive records.

### Features

- Record list with search, filters (LP/CD, status, label, country, Zusatzinfo), sortable columns and page size
- Cover images per record (stored in `storage/app/private/covers`, not publicly reachable)
- "Zusatzinfos" like Boxset or Erstpressung, maintained under *Platten → Zusatzinfos verwalten*
- CSV export (of the filtered list) and CSV import of new records
- Dashboard with key figures and the latest records

### Updating an existing installation

The migration `2026_09_26_100000_improve_records_data_model` converts existing data:

- `release_date` / `reissue_date` (text) become the years `release_year` / `reissue_year`
- `sold_date` (text) becomes the date `sold_on`
- the unused column `for_sale` is removed (a set value is carried over to `selling` first)

Nothing is lost: if a value can not be converted exactly (e.g. "12.03.1974" or "ca. 70er"),
the original text is appended to the note of the record.

```
php artisan down
cp database/database.sqlite database/backup.sqlite   # backup first (MySQL: mysqldump)
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan up
```

Set `APP_LOCALE=de` in the `.env` for German validation messages.

### Open issues

- tbd

## License

The Laravel framework is open-sourced software licensed under the GPL-3.0 license.
