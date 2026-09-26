<?php

use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;

function languageRecord(): Record
{
    $record = new Record;
    $record->kind = 'LP';
    $record->artist_id = Artist::create(['name' => 'Kraftwerk'])->id;
    $record->label_id = Label::create(['name' => 'Kling Klang'])->id;
    $record->title = 'Autobahn';
    $record->current_price = '1234.5';
    $record->save();

    return $record;
}

test('the language follows the browser', function () {
    languageRecord();

    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')->get(route('records.index'))
        ->assertOk()
        ->assertSee('All records')
        ->assertSee('€1,234.50')
        ->assertSee('<html lang="en"', false);

    $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')->get(route('records.index'))
        ->assertSee('Alle Platten')
        ->assertSee('1.234,50 €');

    // Other languages fall back to the first supported one in the browser list, else German.
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')->get(route('records.index'))->assertSee('Alle Platten');
});

test('the language can be switched and is remembered', function () {
    $this->withHeader('Accept-Language', 'de-DE')->get(route('language', 'en'))->assertRedirect();
    $this->withHeader('Accept-Language', 'de-DE')->get('/')->assertSee('Welcome to RecordLoom');

    $this->get(route('language', 'de'));
    $this->get('/')->assertSee('Willkommen bei RecordLoom');

    $this->get('/language/fr')->assertNotFound();
});

test('messages and validation errors are translated', function () {
    $this->withHeader('Accept-Language', 'en')
        ->post(route('records.store'), ['kind' => 'LP', 'title' => 'X'])
        ->assertSessionHasErrors(['artist_name' => 'The Artist field is required.']);

    $this->withHeader('Accept-Language', 'de')
        ->post(route('records.store'), ['kind' => 'LP', 'title' => 'X'])
        ->assertSessionHasErrors(['artist_name' => 'Künstler muss ausgefüllt werden.']);

    $this->withHeader('Accept-Language', 'en')
        ->post(route('records.store'), ['kind' => 'LP', 'title' => 'X', 'artist_name' => 'Can', 'label_name' => 'UA'])
        ->assertSessionHas('info', 'Record “X” by Can was created.');
});

test('every text used in the app has an english translation', function () {
    $english = json_decode(file_get_contents(lang_path('en.json')), true);
    $german = json_decode(file_get_contents(lang_path('de.json')), true);

    $files = array_merge(
        glob(resource_path('views/*.blade.php')),
        glob(resource_path('views/*/*.blade.php')),
        glob(app_path('Http/Controllers/*.php')),
        glob(app_path('Http/Requests/*.php')),
    );

    $missing = [];
    foreach ($files as $file) {
        preg_match_all("/(?:__|trans_choice)\(\s*'((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file), $matches);
        foreach ($matches[1] as $key) {
            $key = str_replace("\\'", "'", $key);
            // Keys of language files (validation.required ...) and English keys translated in de.json.
            if (preg_match('/^[a-z_]+\.[a-z_.]+$/', $key) || isset($english[$key]) || isset($german[$key])) {
                continue;
            }
            $missing[] = basename($file).': '.$key;
        }
    }

    expect($missing)->toBe([]);
});

test('all pages render in both languages', function () {
    $record = languageRecord();

    foreach (['de', 'en'] as $locale) {
        foreach (['/', route('records.index'), route('records.create'), route('records.show', $record), route('records.edit', $record),
            route('artists.index'), route('artists.show', $record->artist_id), route('labels.index'), route('editions.index'),
            route('records.import'), route('platforms.index')] as $url) {
            $this->withHeader('Accept-Language', $locale)->get($url)->assertOk();
        }
    }
});

test('error pages follow the browser language', function () {
    $this->withHeader('Accept-Language', 'en-GB')->get('/does-not-exist')->assertNotFound()->assertSee('Page not found');
    $this->withHeader('Accept-Language', 'de-DE')->get('/does-not-exist')->assertNotFound()->assertSee('Seite nicht gefunden');
});
