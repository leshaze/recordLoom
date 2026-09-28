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
- **Barcode scanner:** on phones and tablets the barcode can be scanned with the camera (EAN/UPC), in the
  Discogs search and in the search of the navigation. The navigation opens the record from the collection
  (or lists all records with this barcode); an unknown barcode offers to add the record via Discogs. Browsers only
  allow the live camera over **https** (or localhost); over plain http a photo is taken instead and the barcode is
  read from it.
- **Link:** every record can store its Discogs release ID; the record page links to Discogs.
- **Prices:** the record page shows the lowest offer and the range of the price suggestions (depending on the
  condition) with the suggestion for the grading of the record. Both can be used as current price, they are stored
  in the price history with the vendor "Discogs". The highest offer and sale prices are not available in the API.
  Records marked for sale are updated once a day, missed days are caught up (`php artisan discogs:update-prices`,
  `--all` for all linked records).
- **Matching:** *Platten → Mit Discogs abgleichen* suggests releases for existing records. Before linking a
  comparison shows the existing and the Discogs value of every field; existing values are kept unless chosen
  otherwise. The search in the record form asks the same way when fields differ.

Setup:

1. On discogs.com: *Settings → Developers → Generate new token* and add it to the `.env`: `DISCOGS_TOKEN=...`
2. For price suggestions fill in the seller settings of the Discogs account (Discogs requires this).
3. The automatic daily price update is off by default. To switch it on set `DISCOGS_NIGHTLY_PRICES=true` and run
   the Laravel scheduler, e.g. with cron: `* * * * * cd /var/www/recordLoom && php artisan schedule:run`.
   Without it prices are updated per record or with `php artisan discogs:update-prices --all`.

Discogs allows 60 requests per minute; search results and releases are cached.

### Daily backup by mail

Once a day RecordLoom mails a compressed copy of the SQLite database, but only if the collection has changed
since the last backup (sessions and cache do not count). Cover images are not included.
`BACKUP_INTERVAL_DAYS` changes the interval (e.g. `7` for weekly).

The server does not have to run all the time (e.g. a Raspberry Pi): the scheduler checks every 15 minutes whether
the last check was on an earlier day, so a backup that was missed while the server was off is caught up at most
15 minutes after it is running again. Days are counted in the time zone of the app (`APP_TIMEZONE`, e.g.
`Europe/Berlin`; default UTC). The automatic Discogs price update (if enabled) is caught up the same way after
24 hours (`DISCOGS_PRICE_INTERVAL_HOURS`).

1. Configure sending mails in the `.env` (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
   `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`) and set the recipient: `BACKUP_MAIL_TO=you@example.com`
2. Run the Laravel scheduler with cron: `* * * * * cd /var/www/recordLoom && php artisan schedule:run`
3. Test it: `php artisan backup:mail --force`

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
