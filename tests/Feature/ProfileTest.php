<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('profile page is displayed', function () {
    $user = User::factory()->create(['name' => 'Erika']);

    $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Erika')->assertSee('Benutzer');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', ['name' => 'Test User', 'email' => 'Test@Example.com', 'current_password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();
    expect($user->name)->toBe('Test User')->and($user->email)->toBe('test@example.com');
});

test('the e-mail address must be unique', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', ['name' => 'Test', 'email' => 'taken@example.com'])
        ->assertSessionHasErrors('email');
});

test('there is no self service account deletion', function () {
    $this->delete('/profile')->assertStatus(405);
});

test('changing the e-mail address needs the password, changing the name not', function () {
    $user = User::factory()->create(['email' => 'erika@example.com']);

    $this->actingAs($user)->patch('/profile', ['name' => 'Erika', 'email' => 'neu@example.com'])->assertSessionHasErrors('current_password');
    $this->actingAs($user)->patch('/profile', ['name' => 'Erika', 'email' => 'neu@example.com', 'current_password' => 'falsch'])->assertSessionHasErrors('current_password');
    $this->actingAs($user)->patch('/profile', ['name' => 'Erika M.', 'email' => 'Erika@example.com'])->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe('erika@example.com')->and($user->fresh()->name)->toBe('Erika M.');
});

test('changing the password logs out the other browsers', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    DB::table('sessions')->insert([
        ['id' => 'anderer-browser', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
        ['id' => 'fremd', 'user_id' => null, 'payload' => '', 'last_activity' => time()],
    ]);

    $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'password', 'password' => 'neues-passwort-123', 'password_confirmation' => 'neues-passwort-123',
    ])->assertSessionHasNoErrors();

    // The current session stays, the other browser of the user is logged out.
    expect(DB::table('sessions')->pluck('id')->all())->not->toContain('anderer-browser')->toContain('fremd');
});

test('responses carry security headers', function () {
    $this->get(route('login'))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'same-origin');
});
