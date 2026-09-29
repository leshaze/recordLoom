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

    /**
     * Due when the task never ran or its last run was at least $days calendar days ago (1 = on an earlier day).
     * Unlike isDue() the time of day does not matter, so a daily task does not drift to a later hour
     * and is not skipped on days the server is only switched on before that hour.
     */
    public function isDueAfterDays(string $task, int $days): bool
    {
        $last = $this->lastRun($task);

        return $last === null || $last->isFuture()
            || $last->setTimezone(now()->getTimezone())->startOfDay()->addDays(max(1, $days))->lessThanOrEqualTo(now());
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
