<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * English names of the Zusatzinfos that were created by the previous migration.
     */
    private const DEFAULTS = [
        'Boxset' => 'Box set',
        'Erstpressung' => 'First pressing',
        'Limitierte Auflage' => 'Limited edition',
        'Picture Disc' => 'Picture disc',
        'Farbiges Vinyl' => 'Coloured vinyl',
    ];

    public function up(): void
    {
        Schema::table('editions', function (Blueprint $table) {
            $table->string('name_en')->nullable();
        });

        foreach (self::DEFAULTS as $german => $english) {
            DB::table('editions')->where('name', $german)->whereNull('name_en')->update(['name_en' => $english]);
        }
    }

    public function down(): void
    {
        Schema::table('editions', function (Blueprint $table) {
            $table->dropColumn('name_en');
        });
    }
};
