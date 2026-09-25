<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->index('artist_id');
            $table->index('label_id');
            $table->index('platform_id');
            $table->index(['selling', 'sold']);
        });

        Schema::table('price_history', function (Blueprint $table) {
            $table->index('record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropIndex(['artist_id']);
            $table->dropIndex(['label_id']);
            $table->dropIndex(['platform_id']);
            $table->dropIndex(['selling', 'sold']);
        });

        Schema::table('price_history', function (Blueprint $table) {
            $table->dropIndex(['record_id']);
        });
    }
};
