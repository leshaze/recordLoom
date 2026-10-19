<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * mail_notifications: the user also gets notifications by mail (set in the own profile).
 * sees_all_records: a seller sees all records that are not sold, not only assigned ones and those marked for selling (set by an admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mail_notifications')) {
                $table->boolean('mail_notifications')->default(true);
            }
            if (! Schema::hasColumn('users', 'sees_all_records')) {
                $table->boolean('sees_all_records')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mail_notifications', 'sees_all_records']);
        });
    }
};
