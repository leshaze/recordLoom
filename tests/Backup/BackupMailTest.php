<?php

use App\Mail\DatabaseBackupMail;
use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;
use App\Services\Backup\DatabaseBackup;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');
    config(['backup.mail_to' => 'owner@example.com']);
});

function backupRecord(string $title = 'Autobahn'): Record
{
    $record = new Record;
    $record->kind = 'LP';
    $record->artist_id = Artist::firstOrCreate(['name' => 'Kraftwerk'])->id;
    $record->label_id = Label::firstOrCreate(['name' => 'Kling Klang'])->id;
    $record->title = $title;
    $record->save();

    return $record;
}

test('the backup is mailed with the database as attachment', function () {
    backupRecord();

    $this->artisan('backup:mail')->assertSuccessful();

    Mail::assertSent(DatabaseBackupMail::class, function (DatabaseBackupMail $mail) {
        expect($mail->hasTo('owner@example.com'))->toBeTrue()
            ->and($mail->counts[__('Platten')])->toBe(1)
            ->and($mail->attachments())->toHaveCount(1);

        return true;
    });
});

test('no mail is sent when nothing has changed', function () {
    backupRecord();
    $this->artisan('backup:mail')->assertSuccessful();
    $this->artisan('backup:mail')->expectsOutputToContain('No changes')->assertSuccessful();

    Mail::assertSentCount(1);
});

test('changes and deletions lead to a new backup', function () {
    $record = backupRecord();
    $this->artisan('backup:mail');

    $record->title = 'Autobahn (Remaster)';
    $record->save();
    $this->artisan('backup:mail');
    Mail::assertSentCount(2);

    $record->delete();
    $this->artisan('backup:mail');
    Mail::assertSentCount(3);
});

test('sessions and cache do not count as changes', function () {
    backupRecord();
    $this->artisan('backup:mail');

    DB::table('cache')->insert(['key' => 'x', 'value' => 'y', 'expiration' => time() + 60]);
    $this->artisan('backup:mail');

    Mail::assertSentCount(1);
});

test('the backup can be forced and needs a recipient', function () {
    backupRecord();
    $this->artisan('backup:mail');
    $this->artisan('backup:mail --force')->assertSuccessful();
    Mail::assertSentCount(2);

    config(['backup.mail_to' => null]);
    $this->artisan('backup:mail')->assertFailed();
});

test('the attachment is a readable copy of the database', function () {
    backupRecord('Radio-Aktivität');

    $path = app(DatabaseBackup::class)->createCompressedCopy();
    $copy = tempnam(sys_get_temp_dir(), 'db');
    file_put_contents($copy, gzdecode(file_get_contents($path)));

    $pdo = new PDO('sqlite:'.$copy);
    expect($pdo->query('select title from records')->fetchColumn())->toBe('Radio-Aktivität');

    unlink($copy);
    unlink($path);
});
