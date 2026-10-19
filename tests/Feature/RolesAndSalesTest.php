<?php

use App\Enums\Role;
use App\Models\Artist;
use App\Models\Label;
use App\Models\Record;
use App\Models\User;
use App\Notifications\Invitation;
use App\Notifications\RecordSold;
use App\Notifications\RecordWithdrawn;
use Illuminate\Support\Facades\Notification;

function ownedRecord(User $owner, array $attributes = []): Record
{
    $record = new Record;
    $record->kind = 'LP';
    $record->artist_id = Artist::firstOrCreate(['name' => 'Kraftwerk'])->id;
    $record->label_id = Label::firstOrCreate(['name' => 'Kling Klang'])->id;
    $record->title = 'Autobahn';
    $record->user_id = $owner->id;
    foreach ($attributes as $key => $value) {
        $record->{$key} = $value;
    }
    $record->save();

    return $record;
}

test('guests are sent to the login and there is no registration', function () {
    $this->app['auth']->forgetGuards();

    $this->get('/')->assertRedirect(route('login'));
    $this->get(route('records.index'))->assertRedirect(route('login'));
    $this->get('/register')->assertNotFound();
    $this->get(route('login'))->assertOk()->assertSee('Anmelden')->assertDontSee('Registrieren');
});

test('users only see and change their own collection', function () {
    $anna = User::factory()->create();
    $ben = User::factory()->create();
    $own = ownedRecord($anna, ['title' => 'Annas Platte', 'current_price' => '10']);
    $other = ownedRecord($ben, ['title' => 'Bens Platte', 'current_price' => '99']);

    $this->actingAs($anna);
    $this->get(route('records.index'))->assertSee('Annas Platte')->assertDontSee('Bens Platte');
    $this->get('/')->assertSee('10,00 €')->assertDontSee('99,00 €');
    $this->get(route('records.show', $own))->assertOk()->assertSee('Kaufpreis');
    $this->get(route('records.show', $other))->assertForbidden();
    $this->get(route('records.edit', $other))->assertForbidden();
    $this->put(route('records.update', $other), ['kind' => 'LP', 'title' => 'X', 'artist_name' => 'A', 'label_name' => 'B'])->assertForbidden();
    $this->delete(route('records.destroy', $other))->assertForbidden();
    expect(Record::find($other->id))->not->toBeNull();

    // New records belong to the user who adds them.
    $this->post(route('records.store'), ['kind' => 'LP', 'title' => 'Neu', 'artist_name' => 'Can', 'label_name' => 'UA']);
    expect(Record::firstWhere('title', 'Neu')->user_id)->toBe($anna->id);

    // Users can not reach the administration.
    $this->get(route('admin.users.index'))->assertForbidden();
    $this->get(route('editions.index'))->assertForbidden();
});

test('admins see all records and can filter them by owner', function () {
    $anna = User::factory()->create(['name' => 'Anna']);
    ownedRecord($anna, ['title' => 'Annas Platte']);
    ownedRecord(User::factory()->create(), ['title' => 'Bens Platte']);

    $this->get(route('records.index'))->assertSee(['Annas Platte', 'Bens Platte']);
    $this->get(route('records.index', ['owner' => $anna->id]))->assertSee('Annas Platte')->assertDontSee('Bens Platte');
});

test('sellers only see assigned records without buy price and note', function () {
    $owner = User::factory()->create();
    $seller = User::factory()->seller()->create();
    $assigned = ownedRecord($owner, ['title' => 'Zugewiesen', 'buy_price' => '12.34', 'note' => 'Geheime Notiz', 'current_price' => '40']);
    $hidden = ownedRecord($owner, ['title' => 'Nicht zugewiesen']);
    $assigned->sellers()->attach($seller);

    $this->actingAs($seller);
    $this->get(route('records.index'))->assertSee('Zugewiesen')->assertDontSee('Nicht zugewiesen');
    $this->get(route('records.show', $assigned))->assertOk()
        ->assertSee('40,00 €')->assertSee('Interesse anmelden')
        ->assertDontSee('12,34 €')->assertDontSee('Geheime Notiz')->assertDontSee('Bearbeiten');
    $this->get(route('records.show', $hidden))->assertForbidden();
    $this->get(route('records.edit', $assigned))->assertForbidden();
    $this->get(route('records.create'))->assertForbidden();
    $this->get(route('artists.index'))->assertForbidden();
    $this->get('/')->assertOk()->assertSee('Für dich verfügbare Platten')->assertSee('Zugewiesen');
});

test('the owner assigns sellers, sellers register interest and the owner sells to one of them', function () {
    Notification::fake();
    $owner = User::factory()->create();
    [$eva, $tom, $ida] = User::factory()->seller()->count(3)->sequence(['name' => 'Eva'], ['name' => 'Tom'], ['name' => 'Ida'])->create();
    $record = ownedRecord($owner, ['current_price' => '30', 'selling' => true]);

    // Only the owner (or an admin) assigns sellers; only sellers can be assigned.
    $this->actingAs($owner)->put(route('records.sellers', $record), ['sellers' => [$eva->id, $tom->id, $ida->id]])->assertSessionHas('info');
    $this->actingAs($owner)->put(route('records.sellers', $record), ['sellers' => [$owner->id]])->assertSessionHasErrors();
    expect($record->sellers()->pluck('name')->all())->toBe(['Eva', 'Ida', 'Tom']);

    // Eva and Tom are interested, Ida not; Tom withdraws and registers again.
    $this->actingAs($eva)->post(route('records.interest', $record), ['interested' => 1])->assertSessionHas('info');
    $this->actingAs($tom)->post(route('records.interest', $record), ['interested' => 1]);
    $this->actingAs($tom)->post(route('records.interest', $record), ['interested' => 0]);
    $this->actingAs($tom)->post(route('records.interest', $record), ['interested' => 1]);
    expect($owner->notifications()->count())->toBe(0); // faked; checked below

    // The owner sees the interest; selling to Ida (no interest) is not possible.
    $this->actingAs($owner)->get(route('records.show', $record))->assertSee('Verkaufen an Eva')->assertSee('Verkaufen an Tom')->assertDontSee('Verkaufen an Ida');
    $this->actingAs($owner)->post(route('records.sell', $record), ['seller_id' => $ida->id, 'sold_on' => '2026-10-20'])->assertStatus(422);

    $this->actingAs($owner)->post(route('records.sell', $record), ['seller_id' => $eva->id, 'sold_price' => '27,50', 'sold_on' => '2026-10-20'])
        ->assertRedirect(route('records.show', $record))->assertSessionHas('info');

    $record->refresh();
    expect($record->sold)->toBeTrue()
        ->and($record->selling)->toBeFalse()
        ->and($record->sold_price)->toBe('27.50')
        ->and($record->sold_on->toDateString())->toBe('2026-10-20')
        ->and($record->sold_to)->toBe('Eva')
        ->and($record->sold_to_user_id)->toBe($eva->id);

    Notification::assertSentTo($eva, RecordSold::class, fn ($notification) => $notification->toArray($eva)['kind'] === 'sold_to_you');
    Notification::assertSentTo($tom, RecordSold::class, fn ($notification) => $notification->toArray($tom)['kind'] === 'sold_to_other');
    Notification::assertNotSentTo($ida, RecordSold::class);

    // Afterwards: no second sale, no more interest, the buyer sees it.
    $this->actingAs($owner)->post(route('records.sell', $record), ['seller_id' => $tom->id, 'sold_on' => '2026-10-21'])->assertStatus(422);
    $this->actingAs($tom)->post(route('records.interest', $record), ['interested' => 0])->assertForbidden();
    $this->actingAs($eva)->get(route('records.show', $record))->assertSee('an dich verkauft');
});

test('notifications are stored and shown with a counter', function () {
    $owner = User::factory()->create();
    $seller = User::factory()->seller()->create(['name' => 'Eva']);
    $record = ownedRecord($owner, ['title' => 'Radio-Aktivität']);
    $record->sellers()->attach($seller);

    $this->actingAs($seller)->post(route('records.interest', $record), ['interested' => 1])->assertSessionHas('info');
    $this->actingAs($owner)->post(route('records.sell', $record), ['seller_id' => $seller->id, 'sold_on' => '2026-10-20'])->assertSessionHas('info');
    expect(Record::find($record->id)->sold_price)->toBeNull();

    expect($owner->unreadNotifications()->count())->toBe(1)->and($seller->unreadNotifications()->count())->toBe(1);
    $this->actingAs($owner)->get('/')->assertSee('bi-bell-fill', false);
    $this->actingAs($owner)->get(route('notifications.index'))->assertSee('Eva hat Interesse an „Radio-Aktivität“ von Kraftwerk angemeldet.');
    $this->actingAs($seller)->get(route('notifications.index'))->assertSee('„Radio-Aktivität“ von Kraftwerk wurde an dich verkauft.');
    expect($owner->unreadNotifications()->count())->toBe(0);
});

test('admins invite users, who set their password through the link', function () {
    Notification::fake();

    $this->post(route('admin.users.store'), ['name' => 'Vera', 'email' => 'Vera@Example.com', 'role' => 'seller'])
        ->assertRedirect(route('admin.users.index'))->assertSessionHas('info');
    $vera = User::firstWhere('email', 'vera@example.com');
    expect($vera->role)->toBe(Role::Seller);

    $token = null;
    Notification::assertSentTo($vera, Invitation::class, function (Invitation $invitation) use ($vera, &$token) {
        $token = str($invitation->toMail($vera)->actionUrl)->after('reset-password/')->before('?')->toString();

        return true;
    });

    $this->app['auth']->forgetGuards();
    $this->post(route('password.store'), ['token' => $token, 'email' => 'vera@example.com', 'password' => 'geheim123', 'password_confirmation' => 'geheim123'])
        ->assertRedirect(route('login'));
    $this->post(route('login'), ['email' => 'vera@example.com', 'password' => 'geheim123'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($vera);
});

test('the last admin keeps his role and nobody deletes himself or users with records', function () {
    $admin = auth()->user();
    $owner = User::factory()->create(['name' => 'Olga']);
    ownedRecord($owner);

    $this->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'user'])->assertSessionHas('error');
    expect($admin->fresh()->isAdmin())->toBeTrue();

    $this->put(route('admin.users.update', $owner), ['name' => 'Olga', 'email' => $owner->email, 'role' => 'seller'])->assertSessionHas('error');
    $this->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');
    $this->delete(route('admin.users.destroy', $owner))->assertSessionHas('error');

    $this->put(route('admin.users.update', $owner), ['name' => 'Olga M.', 'email' => $owner->email, 'role' => 'admin'])->assertSessionHas('info');
    expect($owner->fresh()->isAdmin())->toBeTrue();
});

test('the first admin gets the records from before the login', function () {
    $record = ownedRecord(User::factory()->create());
    $record->user_id = null;
    $record->save();

    $this->artisan('user:admin', ['email' => 'chef@example.com', '--name' => 'Chef'])
        ->expectsQuestion('Password (at least 8 characters)', 'geheim123')
        ->expectsQuestion('Repeat the password', 'geheim123')
        ->assertSuccessful();

    $chef = User::firstWhere('email', 'chef@example.com');
    expect($chef->isAdmin())->toBeTrue()->and($record->fresh()->user_id)->toBe($chef->id);
});

test('forgot password does not reveal whether an address exists', function () {
    $this->app['auth']->forgetGuards();

    $this->post(route('password.email'), ['email' => 'niemand@example.com'])
        ->assertSessionHasNoErrors()->assertSessionHas('status');
});

test('records marked for selling are visible to all sellers, who can register interest', function () {
    Notification::fake();
    $owner = User::factory()->create();
    [$eva, $tom] = User::factory()->seller()->count(2)->sequence(['name' => 'Eva'], ['name' => 'Tom'])->create();
    $record = ownedRecord($owner, ['title' => 'Computerwelt']);
    $assigned = ownedRecord($owner, ['title' => 'Nur für Tom']);
    $assigned->sellers()->attach($tom);

    // Not marked for selling: only assigned sellers see it.
    $this->actingAs($eva)->get(route('records.show', $record))->assertForbidden();

    // The owner switches "Zum Verkauf vormerken" on the page of the record.
    $this->actingAs($owner)->get(route('records.show', $record))->assertSee('Zum Verkauf vormerken')->assertSee('id="selling-switch"', false);
    $this->actingAs($owner)->put(route('records.selling', $record), ['selling' => '1'])->assertSessionHas('info');
    expect($record->fresh()->selling)->toBeTrue();

    // Now every seller sees it; Eva does not see the record that is only assigned to Tom.
    $this->actingAs($eva)->get(route('records.index'))->assertSee('Computerwelt')->assertDontSee('Nur für Tom');
    $this->actingAs($eva)->get('/')->assertSee('Computerwelt');
    $this->actingAs($eva)->get(route('records.show', $record))->assertOk()->assertSee('Interesse anmelden');

    // Interest without assignment; the owner sees it and can sell.
    $this->actingAs($eva)->post(route('records.interest', $record), ['interested' => 1])->assertSessionHas('info');
    $pivot = $record->sellers()->whereKey($eva->id)->first()->pivot;
    expect($pivot->assigned)->toBeFalse()->and($pivot->interested_at)->not->toBeNull();
    $this->actingAs($owner)->get(route('records.show', $record))->assertSee('Verkaufen an Eva')->assertSee('über „Zum Verkauf“');

    // Withdrawing removes the row again.
    $this->actingAs($eva)->post(route('records.interest', $record), ['interested' => 0]);
    expect($record->sellers()->count())->toBe(0);

    // Switched off: Eva no longer sees it, Tom still sees his assigned record.
    $this->actingAs($owner)->put(route('records.selling', $record), ['selling' => '0']);
    $this->actingAs($eva)->get(route('records.show', $record))->assertForbidden();
    $this->actingAs($tom)->get(route('records.show', $assigned))->assertOk();

    // Sold records can not be marked for selling again.
    $this->actingAs($owner)->put(route('records.selling', $record), ['selling' => '1']);
    $this->actingAs($eva)->post(route('records.interest', $record), ['interested' => 1]);
    $this->actingAs($owner)->post(route('records.sell', $record), ['seller_id' => $eva->id, 'sold_on' => '2026-10-21'])->assertSessionHas('info');
    expect($record->fresh()->selling)->toBeFalse();
    $this->actingAs($owner)->put(route('records.selling', $record), ['selling' => '1'])->assertStatus(422);

    // The buyer keeps seeing the record, the others not.
    $this->actingAs($eva)->get(route('records.show', $record))->assertOk()->assertSee('an dich verkauft');
    $this->actingAs($tom)->get(route('records.show', $record))->assertForbidden();
});

test('unassigning keeps a registered interest, the edit form does not change "for selling"', function () {
    $owner = User::factory()->create();
    [$eva, $tom] = User::factory()->seller()->count(2)->create();
    $record = ownedRecord($owner, ['selling' => true]);

    $this->actingAs($owner)->put(route('records.sellers', $record), ['sellers' => [$eva->id, $tom->id]]);
    $this->actingAs($eva)->post(route('records.interest', $record), ['interested' => 1]);
    $this->actingAs($owner)->put(route('records.sellers', $record), ['sellers' => []]);

    expect($record->sellers()->pluck('users.id')->all())->toBe([$eva->id])
        ->and($record->sellers()->first()->pivot->assigned)->toBeFalse();

    $this->actingAs($owner)->put(route('records.update', $record), ['kind' => 'LP', 'title' => 'Autobahn', 'artist_name' => 'Kraftwerk', 'label_name' => 'Kling Klang'])
        ->assertRedirect(route('records.show', $record));
    expect($record->fresh()->selling)->toBeTrue();
});

test('sellers can offer a price that the owner sees when choosing the buyer', function () {
    $owner = User::factory()->create();
    [$eva, $tom] = User::factory()->seller()->count(2)->sequence(['name' => 'Eva'], ['name' => 'Tom'])->create();
    $record = ownedRecord($owner, ['selling' => true, 'current_price' => '20']);

    // Eva offers a price with a decimal comma, Tom registers without one.
    $this->actingAs($eva)->get(route('records.show', $record))->assertSee('Dein Preisvorschlag');
    $this->actingAs($eva)->post(route('records.interest', $record), ['interested' => 1, 'offer_price' => '25,50'])->assertSessionHas('info');
    $this->actingAs($tom)->post(route('records.interest', $record), ['interested' => 1]);

    $pivot = fn (User $seller) => $record->sellers()->whereKey($seller->id)->first()->pivot;
    expect($pivot($eva)->offer_price)->toBe('25.50')->and($pivot($tom)->offer_price)->toBeNull();
    $this->actingAs($eva)->get(route('records.show', $record))->assertSee('value="25,50"', false);
    $this->actingAs($tom)->post(route('records.interest', $record), ['interested' => 1, 'offer_price' => 'viel'])->assertSessionHasErrors('offer_price');
    $this->actingAs($tom)->get(route('records.show', $record));

    // Tom offers later; the date of his interest stays and the owner is told about the offer.
    $since = $pivot($tom)->interested_at;
    $this->travel(1)->days();
    $this->actingAs($tom)->post(route('records.interest', $record), ['interested' => 1, 'offer_price' => '30'])
        ->assertSessionHas('info', 'Dein Preisvorschlag wurde gespeichert.');
    expect($pivot($tom)->interested_at->equalTo($since))->toBeTrue()->and($pivot($tom)->offer_price)->toBe('30.00');
    $this->actingAs($owner)->get(route('notifications.index'))
        ->assertSee('Eva hat Interesse an „Autobahn“ von Kraftwerk angemeldet und bietet 25,50 €.')
        ->assertSee('Tom bietet jetzt 30,00 € für „Autobahn“ von Kraftwerk.');

    // The owner sees both offers, the highest marked, and the sale dialog starts with the offer.
    $this->actingAs($owner)->get(route('records.show', $record))
        ->assertSee('bietet 25,50 €')->assertSee('bietet 30,00 €')->assertSee('höchstes Gebot')
        ->assertSee('value="30,00"', false)->assertSee('Preisvorschlag von Tom');

    // Withdrawing clears the offer.
    $this->actingAs($eva)->post(route('records.interest', $record), ['interested' => 0]);
    expect($record->sellers()->whereKey($eva->id)->exists())->toBeFalse();

    // Sellers do not see the offers of others.
    $this->actingAs($tom)->get(route('records.show', $record))->assertDontSee('25,50');
});

test('switching "for selling" off deletes all interest and tells the interested sellers', function () {
    Notification::fake();
    $owner = User::factory()->create();
    [$eva, $tom, $ida] = User::factory()->seller()->count(3)->sequence(['name' => 'Eva'], ['name' => 'Tom'], ['name' => 'Ida'])->create();
    $record = ownedRecord($owner, ['selling' => true]);
    $record->sellers()->attach($tom);
    $record->sellers()->attach($ida);

    $this->actingAs($eva)->post(route('records.interest', $record), ['interested' => 1, 'offer_price' => '20']);
    $this->actingAs($tom)->post(route('records.interest', $record), ['interested' => 1, 'offer_price' => '25']);
    $this->actingAs($owner)->get(route('records.show', $record))->assertSee('data-confirm-off', false);

    $this->actingAs($owner)->put(route('records.selling', $record), ['selling' => '0'])
        ->assertSessionHas('info', 'Die Platte ist nicht mehr zum Verkauf vorgemerkt. Die Interessenten wurden benachrichtigt.');

    // Eva (only via "for selling") is removed; Tom and Ida stay assigned, without interest or offer.
    expect($record->sellers()->pluck('users.id')->sort()->values()->all())->toBe([$tom->id, $ida->id]);
    $pivot = $record->sellers()->whereKey($tom->id)->first()->pivot;
    expect($pivot->interested_at)->toBeNull()->and($pivot->offer_price)->toBeNull();

    Notification::assertSentTo([$eva, $tom], RecordWithdrawn::class);
    Notification::assertNotSentTo($ida, RecordWithdrawn::class);
    $this->actingAs($eva)->get(route('records.show', $record))->assertForbidden();
});

test('the withdrawn notification is shown to the seller', function () {
    $owner = User::factory()->create();
    $eva = User::factory()->seller()->create();
    $record = ownedRecord($owner, ['selling' => true]);
    $this->actingAs($eva)->post(route('records.interest', $record), ['interested' => 1]);
    $this->actingAs($owner)->put(route('records.selling', $record), ['selling' => '0']);

    $this->actingAs($eva)->get(route('notifications.index'))
        ->assertSee('„Autobahn“ von Kraftwerk wird nicht mehr verkauft. Dein Interesse wurde gelöscht.');
});
