<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The first version of 2026_09_29_000000_store_prices_as_decimals left empty text ('') in price columns
 * instead of null, which can not be read as a decimal. Empty prices become null, empty price history
 * entries are removed (they have no price to show).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['current_price', 'buy_price', 'sold_price'] as $column) {
            DB::table('records')->whereNotNull($column)->whereRaw("TRIM({$column}) = ''")->update([$column => null]);
        }

        DB::table('price_history')->whereRaw("TRIM(price) = ''")->delete();
    }
};
