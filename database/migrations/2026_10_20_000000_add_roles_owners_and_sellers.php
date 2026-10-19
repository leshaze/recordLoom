<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login with roles: every record belongs to a user (existing records get the first admin, see
 * "php artisan user:admin"), records can be assigned to sellers, who can register interest.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('user');
            });
        }

        if (! Schema::hasColumn('records', 'user_id')) {
            Schema::table('records', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->index()->constrained()->nullOnDelete();
                // The seller who bought the record (sold through "sell to").
                $table->foreignId('sold_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('record_seller')) {
            Schema::create('record_seller', function (Blueprint $table) {
                $table->foreignId('record_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
                $table->timestamp('interested_at')->nullable();
                $table->timestamps();
                $table->primary(['record_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('record_seller');
        Schema::table('records', function (Blueprint $table) {
            $table->dropForeign(['sold_to_user_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['sold_to_user_id', 'user_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
