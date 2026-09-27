<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('records', 'discogs_release_id')) {
            return;
        }

        Schema::table('records', function (Blueprint $table) {
            $table->unsignedBigInteger('discogs_release_id')->nullable()->index();
            // Market data from Discogs (price suggestions per condition, lowest offer)
            $table->json('discogs_price_suggestions')->nullable();
            $table->decimal('discogs_lowest_price', 10, 2)->nullable();
            $table->string('discogs_currency', 3)->nullable();
            $table->unsignedInteger('discogs_num_for_sale')->nullable();
            $table->timestamp('discogs_prices_updated_at')->nullable();
            // Set when "no matching release" was chosen on the matching page
            $table->timestamp('discogs_ignored_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropIndex(['discogs_release_id']);
            $table->dropColumn([
                'discogs_release_id', 'discogs_price_suggestions', 'discogs_lowest_price', 'discogs_currency',
                'discogs_num_for_sale', 'discogs_prices_updated_at', 'discogs_ignored_at',
            ]);
        });
    }
};
