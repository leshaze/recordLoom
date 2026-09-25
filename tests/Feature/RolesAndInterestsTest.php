<?php

use App\Models\Artist;
use App\Models\Label;
use App\Models\Platform;
use App\Models\Record;
use App\Models\User;

function recordFor(string $title = 'Autobahn'): Record
{
    $record = new Record;
    $record->kind = 'LP';
    $record->artist_id = Artist::firstOrCreate(['name' => 'Kraftwerk'])->id;
    $record->label_id = Label::firstOrCreate(['name' => 'Kling Klang'])->id;
    $record->title = $title;
    $record->current_price = '20';
    $record->buy_price = '7.77';
    $record->sold = true;
    $record->sold_to = 'Secret Buyer';
    $record->note = 'Secret note';
    $record->save();

    return $record;
}

test('users can read everything but not create, change or delete', function () {
    $record = recordFor();
    $platform = Platform::create(['name' => 'Discogs']);
    $this->actingAs(User::factory()->create());

    foreach ([route('records.index'), route('records.selling'), route('records.show', $record), route('artists.index'),
        route('artists.show', $record->artist_id), route('labels.index'), route('labels.show', $record->label_id),
        route('platforms.index'), route('platforms.show', $platform)] as $url) {
        $this->get($url)->assertOk();
    }

    foreach ([route('records.create'), route('records.edit', $record), route('artists.create'), route('artists.edit', $record->artist_id),
        route('labels.create'), route('labels.edit', $record->label_id), route('platforms.create'), route('platforms.edit', $platform),
        route('interests.overview')] as $url) {
        $this->get($url)->assertForbidden();
    }

    $this->post(route('records.store'), ['kind' => 'LP', 'artist_name' => 'X', 'title' => 'Y', 'label_name' => 'Z'])->assertForbidden();
    $this->put(route('records.update', $record), ['kind' => 'LP', 'artist_name' => 'X', 'title' => 'Y', 'label_name' => 'Z'])->assertForbidden();
    $this->delete(route('records.destroy', $record))->assertForbidden();
    $this->post(route('artists.store'), ['artist_name' => 'New'])->assertForbidden();
    $this->delete(route('artists.destroy', $record->artist_id))->assertForbidden();
    $this->delete(route('platforms.destroy', $platform))->assertForbidden();

    expect(Record::count())->toBe(1)->and($record->fresh()->title)->toBe('Autobahn');
});

test('users do not see the buy price, sale details and notes', function () {
    $record = recordFor();

    $this->actingAs(User::factory()->create())
        ->get(route('records.show', $record))
        ->assertOk()
        ->assertSee('20 €')
        ->assertDontSee('Kaufpreis')
        ->assertDontSee('7.77')
        ->assertDontSee('Secret Buyer')
        ->assertDontSee('Secret note')
        ->assertDontSee(route('records.edit', $record));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('records.show', $record))
        ->assertSee('Kaufpreis')
        ->assertSee('7.77')
        ->assertSee('Secret Buyer')
        ->assertSee('Secret note');
});

test('users can mark and unmark their interest in a record', function () {
    $record = recordFor();
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->put(route('interests.update', $record), ['interested' => '1'])->assertRedirect();
    $this->put(route('interests.update', $record), ['interested' => '1'])->assertRedirect();
    expect($user->interests()->pluck('records.id')->all())->toBe([$record->id]);

    $this->get(route('interests.index'))->assertOk()->assertSee('Autobahn');
    $this->get(route('records.show', $record))->assertSee('checked', false);

    $this->put(route('interests.update', $record), ['interested' => '0'])->assertRedirect();
    expect($user->interests()->count())->toBe(0);
    $this->get(route('interests.index'));
    $this->get(route('interests.index'))->assertDontSee('Autobahn')->assertSee('noch keine Platten');
});

test('users only see their own interests', function () {
    $record = recordFor('Autobahn');
    $other = recordFor('Radio-Aktivität');
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);
    $alice->interests()->attach($record);
    $bob->interests()->attach($other);

    $this->actingAs($alice)->get(route('interests.index'))
        ->assertSee('Autobahn')
        ->assertDontSee('Radio-Aktivität');

    // Other users' names are never shown to users.
    $this->actingAs($alice)->get(route('records.show', $other))->assertDontSee('Bob');
});

test('admins see all interests and can filter them by user', function () {
    $record = recordFor('Autobahn');
    $other = recordFor('Radio-Aktivität');
    recordFor('Nobody wants this');
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);
    $alice->interests()->attach([$record->id, $other->id]);
    $bob->interests()->attach($record);

    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('interests.overview'))
        ->assertOk()
        ->assertSee('Autobahn')
        ->assertSee('Radio-Aktivität')
        ->assertDontSee('Nobody wants this')
        ->assertSee('Alice')
        ->assertSee('Bob');

    $this->get(route('interests.overview', ['user' => $bob->id]))
        ->assertOk()
        ->assertSee('Autobahn')
        ->assertDontSee('Radio-Aktivität');

    $this->get(route('records.show', $record))->assertSee('Interesse von')->assertSee('Alice')->assertSee('Bob');
});

test('admins can not mark interests themselves', function () {
    $record = recordFor();
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('interests.update', $record), ['interested' => '1'])->assertForbidden();
    $this->get(route('interests.index'))->assertForbidden();
});

test('deleting a record removes its interests', function () {
    $record = recordFor();
    User::factory()->create()->interests()->attach($record);

    $this->actingAs(User::factory()->admin()->create())->delete(route('records.destroy', $record));

    expect(DB::table('record_interests')->count())->toBe(0);
});

test('roles can be managed with artisan commands', function () {
    $this->artisan('user:create', ['--name' => 'Admin', '--email' => 'admin@example.com', '--admin' => true])
        ->expectsQuestion('Password', 'a-long-password')
        ->expectsQuestion('Confirm password', 'a-long-password')
        ->assertSuccessful();
    expect(User::firstWhere('email', 'admin@example.com')->isAdmin())->toBeTrue();

    $user = User::factory()->create(['email' => 'user@example.com']);
    expect($user->isAdmin())->toBeFalse();

    $this->artisan('user:role', ['email' => 'user@example.com', 'role' => 'admin'])->assertSuccessful();
    expect($user->fresh()->isAdmin())->toBeTrue();

    $this->artisan('user:role', ['email' => 'user@example.com', 'role' => 'superuser'])->assertFailed();
    $this->artisan('user:role', ['email' => 'nobody@example.com', 'role' => 'user'])->assertFailed();
});

test('the role can not be changed through the profile form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Hacker',
        'email' => $user->email,
        'role' => 'admin',
    ]);

    expect($user->fresh()->isAdmin())->toBeFalse();
});
