<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('user:create {--name=} {--email=} {--admin : Give the user admin rights}', function () {
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

    $user = User::create($validator->safe()->only(['name', 'email', 'password']));
    $user->role = $this->option('admin') ? User::ROLE_ADMIN : User::ROLE_USER;
    $user->save();

    $this->info(($user->isAdmin() ? 'Admin' : 'User').' '.$user->email.' created.');

    return 0;
})->purpose('Create a user that can log in to RecordLoom');

Artisan::command('user:role {email} {role : admin or user}', function (string $email, string $role) {
    if (! in_array($role, [User::ROLE_ADMIN, User::ROLE_USER], true)) {
        $this->error('The role must be "admin" or "user".');

        return 1;
    }

    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error('There is no user with the email '.$email.'.');

        return 1;
    }

    $user->role = $role;
    $user->save();

    $this->info($user->email.' is now '.$role.'.');

    return 0;
})->purpose('Change the role of a user (admin or user)');
