<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Assignment of a record to a seller; interested_at is set while the seller wants the record.
 */
class RecordSeller extends Pivot
{
    protected $table = 'record_seller';

    protected function casts(): array
    {
        return [
            'interested_at' => 'datetime',
            'assigned' => 'boolean',
        ];
    }
}
