<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create(['name' => 'Erika']);

    $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Erika')->assertSee('Benutzer');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile', ['name' => 'Test User', 'email' => 'Test@Example.com'])
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
