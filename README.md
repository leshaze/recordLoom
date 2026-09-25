## RecordLoom

A small CRM project to archive records.

### Users and roles

All pages require a login. There is no public registration, users are created on the command line:

```
php artisan user:create           # read-only user
php artisan user:create --admin   # admin
php artisan user:role mail@example.com admin   # change the role of an existing user
```

- **Admin**: can create, change and delete all entries, sees all data and the interests of all users.
  Users can mark records they are interested in and see their own list under "Meine Interessen".

- **User**: can only read. All prices (current price, buy price, price history, sums), sale details and notes are hidden.

### Updating an existing installation

The new migrations only add a column, a table and indexes, no existing data is changed or deleted.
PHP 8.3 or newer is required.

```
php artisan down                                  # maintenance mode
cp database/database.sqlite database/backup.sqlite   # backup (MySQL: mysqldump)
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate:status                        # the new migrations are "Pending"
php artisan migrate --force
php artisan user:role your@mail.example admin     # existing users become normal users
php artisan optimize:clear
php artisan up
```

Never run `migrate:fresh`, `migrate:refresh` or `db:seed` on the live database, they delete or overwrite data.

### Open issues

- tbd

## License

The Laravel framework is open-sourced software licensed under the GPL-3.0 license.
