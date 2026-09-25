<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('user:create {--name=} {--email=}', function () {
    $data = [
        'name' => $this->option('name') ?? text('Name', required: true),
        'email' => $this->option('email') ?? text('Email', required: true),
        'password' => password('Password', required: true),
    ];
    $data['password_confirmation'] = password('Confirm password', required: true);

    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'confirmed', Password::defaults()],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    User::create($validator->safe()->only(['name', 'email', 'password']));

    $this->info('User '.$data['email'].' created.');

    return 0;
})->purpose('Create a user that can log in to RecordLoom');
