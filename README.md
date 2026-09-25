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
- **User**: can only read. Buy price, sale details (buyer, date, price) and notes are hidden.
  Users can mark records they are interested in and see their own list under "Meine Interessen".

After updating an existing installation run `php artisan migrate` and give your own account admin rights
with `php artisan user:role`, because existing users become normal users.

### Open issues

- tbd

## License

The Laravel framework is open-sourced software licensed under the GPL-3.0 license.
