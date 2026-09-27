## RecordLoom

A small CRM project to archive records.

### Features

- Record list with search, filters (LP/CD, status, label, country, Zusatzinfo), sortable columns and page size
- Cover images per record (stored in `storage/app/private/covers`, not publicly reachable)
- "Zusatzinfos" like Boxset or Erstpressung, maintained under *Platten → Zusatzinfos verwalten*
- CSV export (of the filtered list) and CSV import of new records
- Dashboard with key figures and the latest records

### Discogs

RecordLoom can take over data, covers and prices from [Discogs](https://www.discogs.com):

- **New record:** search by barcode, catalog number or artist and title, pick the pressing and the form is filled
  in (title, artist, label, numbers, country, year, Zusatzinfos, cover). Grading and prices stay manual.
- **Link:** every record can store its Discogs release ID; the record page links to Discogs.
- **Prices:** the record page shows the Discogs price suggestion for the grading of the record and the lowest
  offer; the suggestion can be used as current price (stored in the price history with vendor "Discogs").
  Records marked for sale are updated every night (`php artisan discogs:update-prices`, `--all` for all linked records).
- **Matching:** *Platten → Mit Discogs abgleichen* suggests releases for existing records. Linking only fills in
  empty fields and a missing cover, nothing is overwritten.

Setup:

1. On discogs.com: *Settings → Developers → Generate new token* and add it to the `.env`: `DISCOGS_TOKEN=...`
2. For price suggestions fill in the seller settings of the Discogs account (Discogs requires this).
3. For the nightly update run the Laravel scheduler, e.g. with cron: `* * * * * cd /var/www/recordLoom && php artisan schedule:run`
   (set `DISCOGS_NIGHTLY_PRICES=false` to switch it off).

Discogs allows 60 requests per minute; search results and releases are cached.

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

### Languages

The app is available in German and English. The language follows the browser; it can be switched
with DE/EN in the navigation (remembered for the session). German is used when the browser prefers
neither language. `APP_LOCALE` in the `.env` only sets this fallback.

Texts are written in German in the code (`__('Alle Platten')`), the English translations are in
`lang/en.json`. A test fails when a text has no English translation.
CSV column names stay German in both languages, so exported files can always be imported again.

Data (artists, notes …) is shown as entered. Only the Zusatzinfos have an optional English name,
maintained on their page; without it the German name is shown.

### Open issues

- tbd

## License

The Laravel framework is open-sourced software licensed under the GPL-3.0 license.
