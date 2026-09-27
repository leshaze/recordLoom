<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    protected $table = 'price_history';

    public function record()
    {
        return $this->belongsTo(Record::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}
