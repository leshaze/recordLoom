<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table => indexed columns. Existing indexes are skipped, so the migration can safely run again.
     */
    private const INDEXES = [
        'records' => [['artist_id'], ['label_id'], ['platform_id'], ['selling', 'sold']],
        'price_history' => [['record_id']],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes) {
                foreach ($indexes as $columns) {
                    if (! Schema::hasIndex($tableName, $columns)) {
                        $table->index($columns);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes) {
                foreach ($indexes as $columns) {
                    if (Schema::hasIndex($tableName, $columns)) {
                        $table->dropIndex($columns);
                    }
                }
            });
        }
    }
};
