<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Price a seller offers together with his interest, shown to the owner of the record.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('record_seller', 'offer_price')) {
            Schema::table('record_seller', function (Blueprint $table) {
                $table->decimal('offer_price', 10, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('record_seller', function (Blueprint $table) {
            $table->dropColumn('offer_price');
        });
    }
};
