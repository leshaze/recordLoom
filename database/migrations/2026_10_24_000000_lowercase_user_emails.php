<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The login compares e-mail addresses in lower case. Addresses stored before with capitals are converted,
 * unless that would clash with another account.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('users')->get(['id', 'email']) as $user) {
            $email = strtolower($user->email);
            if ($email !== $user->email && DB::table('users')->where('email', $email)->doesntExist()) {
                DB::table('users')->where('id', $user->id)->update(['email' => $email]);
            }
        }
    }

    public function down(): void {}
};
