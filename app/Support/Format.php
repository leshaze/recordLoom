<?php

namespace App\Support;

class Format
{
    /**
     * German price format, e.g. "1.234,50 €". Returns an empty string for empty values.
     */
    public static function euro(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2, ',', '.').' €';
    }
}
