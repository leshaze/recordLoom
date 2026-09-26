<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Facades\App;

/**
 * Locale aware formatting of prices and dates (German and English).
 */
class Format
{
    /**
     * "1.234,50 €" in German, "€1,234.50" in English. Returns an empty string for empty values.
     */
    public static function euro(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (App::getLocale() === 'de') {
            return number_format((float) $value, 2, ',', '.').' €';
        }

        return '€'.number_format((float) $value, 2, '.', ',');
    }

    /**
     * "24.12.2025" in German, "2025-12-24" in English.
     */
    public static function date(?DateTimeInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->format(App::getLocale() === 'de' ? 'd.m.Y' : 'Y-m-d');
    }
}
