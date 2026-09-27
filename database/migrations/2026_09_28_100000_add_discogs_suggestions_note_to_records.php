<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('records', 'discogs_suggestions_note')) {
            Schema::table('records', function (Blueprint $table) {
                // Why there are no price suggestions (e.g. missing seller settings), shown on the record page
                $table->string('discogs_suggestions_note')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn('discogs_suggestions_note');
        });
    }
};
