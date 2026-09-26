<?php

namespace App\Support;

/**
 * Grading scale used for media and cover (value in percent).
 */
class Grading
{
    /**
     * @var array<int, array{german: string, us: string}>
     */
    public const SCALE = [
        100 => ['german' => 'M-', 'us' => 'NM'],
        85 => ['german' => 'M--', 'us' => 'NM'],
        70 => ['german' => 'VG++', 'us' => 'VG+'],
        50 => ['german' => 'VG+', 'us' => 'VG+'],
        35 => ['german' => 'VG', 'us' => 'VG'],
        25 => ['german' => 'VG-', 'us' => 'VG'],
        15 => ['german' => 'VG--', 'us' => 'VG-'],
        10 => ['german' => 'G+', 'us' => 'VG-'],
        5 => ['german' => 'G', 'us' => 'G'],
    ];

    /**
     * @return array{value: int, german: string, us: string, label: string, color: string}|null
     */
    public static function for(?int $value): ?array
    {
        if (! $value || ! isset(self::SCALE[$value])) {
            return null;
        }

        $grade = self::SCALE[$value];

        return [
            'value' => $value,
            'german' => $grade['german'],
            'us' => $grade['us'],
            'label' => $value.'% - GER: '.$grade['german'].' / US: '.$grade['us'],
            'color' => match (true) {
                $value >= 85 => 'success',
                $value >= 50 => 'primary',
                $value >= 25 => 'warning',
                default => 'danger',
            },
        ];
    }

    /**
     * Options for select fields: value => label.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return collect(array_keys(self::SCALE))
            ->mapWithKeys(fn (int $value) => [$value => self::for($value)['label']])
            ->all();
    }
}
