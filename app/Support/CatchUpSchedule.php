<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Remembers when a periodic task last ran, so a run that was missed (e.g. because the Raspberry Pi
 * was switched off) is caught up as soon as the scheduler runs again instead of waiting for the next date.
 */
class CatchUpSchedule
{
    private const FILE = 'schedule/last-runs.json';

    public function lastRun(string $task): ?Carbon
    {
        $runs = $this->runs();

        return isset($runs[$task]) ? Carbon::parse($runs[$task]) : null;
    }

    /**
     * Due when the task never ran or its last run is at least $interval ago. A last run in the future
     * (the clock of the Pi was wrong when it was stored) also counts as due.
     */
    public function isDue(string $task, \DateInterval|string $interval): bool
    {
        $last = $this->lastRun($task);

        return $last === null || $last->isFuture() || $last->add($interval)->lessThanOrEqualTo(now());
    }

    public function markRun(string $task): void
    {
        $runs = $this->runs();
        $runs[$task] = now()->toIso8601String();
        Storage::disk('local')->put(self::FILE, json_encode($runs, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, string>
     */
    private function runs(): array
    {
        return json_decode(Storage::disk('local')->get(self::FILE) ?? '{}', true) ?: [];
    }
}
