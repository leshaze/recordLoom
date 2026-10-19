<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records marked for selling are visible to all sellers. A seller who registers interest in such a record
 * gets a row without being assigned (assigned = false); assigned sellers see the record in any case.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('record_seller', 'assigned')) {
            Schema::table('record_seller', function (Blueprint $table) {
                $table->boolean('assigned')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::table('record_seller', function (Blueprint $table) {
            $table->dropColumn('assigned');
        });
    }
};
