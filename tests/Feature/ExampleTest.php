<?php

use App\Models\User;

it('redirects guests to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('shows the dashboard to logged in users', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertOk();
});
