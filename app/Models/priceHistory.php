<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class priceHistory extends Model
{
    /** @use HasFactory<\Database\Factories\PriceHistoryFactory> */
    use HasFactory;

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}
