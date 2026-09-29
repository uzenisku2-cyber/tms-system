<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Services;

use App\Modules\Drivers\Models\DriverAvailabilityDay;
use App\Modules\Trips\Models\Trip;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

final class DriverAvailabilityConflictService
{
    public const TIMEZONE = 'Europe/Prague';

    /**
     * @param  list<array{start: string, end: string}>|null  $windows
     * @return list<array{start: string, end: string}>
     */
    public function normalize(?array $windows): array
    {
        if ($windows === null) {
            return [['start' => '00:00', 'end' => '24:00']];
        }

        $normalized = [];
        foreach ($windows as $window) {
            $start = (string) ($window['start'] ?? '');
            $end = (string) ($window['end'] ?? '');
            if ($start >= $end) {
                abort(422, 'Availability window end must follow its start.');
            }
            $normalized[] = ['start' => $start, 'end' => $end];
        }
        usort($normalized, static fn (array $a, array $b): int => strcmp($a['start'], $b['start']));
        foreach ($normalized as $index => $window) {
            if ($index > 0 && $normalized[$index - 1]['end'] > $window['start']) {
                abort(422, 'Availability windows must not overlap.');
            }
        }

        return $normalized;
    }

    public function assertNoExistingTripStart(DriverAvailabilityDay $day): void
    {
        $date = CarbonImmutable::parse($day->date)->toDateString();
        $begin = CarbonImmutable::parse($date.' 00:00:00', self::TIMEZONE)->utc();
        $end = $begin->setTimezone(self::TIMEZONE)->addDay()->utc();

        $trips = Trip::query()->where('driver_id', $day->driver_id)
            ->whereIn('status', [Trip::STATUS_ASSIGNED, Trip::STATUS_STARTED])
            ->where(static function (Builder $query) use ($begin, $end): void {
                $query->where(static function (Builder $q) use ($begin, $end): void {
                    $q->where('scheduled_at', '>=', $begin->toDateTimeString())
                        ->where('scheduled_at', '<', $end->toDateTimeString());
                })->orWhere(static function (Builder $q) use ($begin, $end): void {
                    $q->whereNull('scheduled_at')
                        ->where('started_at', '>=', $begin->toDateTimeString())
                        ->where('started_at', '<', $end->toDateTimeString());
                });
            })->get(['id', 'scheduled_at', 'started_at']);

        foreach ($trips as $trip) {
            $start = $this->tripStart($trip);
            if ($start !== null && $this->covers($day->windows, $start)) {
                abort(409, 'An assigned trip starts during this unavailable window.');
            }
        }
    }

    public function assertTripStartAllowed(Trip $trip, int $driverId): void
    {
        $start = $this->tripStart($trip);
        if ($start === null) {
            return;
        }
        $date = $start->toDateString();
        $days = DriverAvailabilityDay::query()->where('driver_id', $driverId)
            ->whereDate('date', $date)->where('availability', 'unavailable')
            ->where('decision', 'confirmed')->get();
        foreach ($days as $day) {
            if ($this->covers($day->windows, $start)) {
                abort(409, 'Driver has confirmed unavailability at trip start.');
            }
        }
    }

    private function tripStart(Trip $trip): ?CarbonImmutable
    {
        $value = $trip->getAttribute('scheduled_at') ?? $trip->getAttribute('started_at');
        if ($value === null) {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->setTimezone(self::TIMEZONE);
        }

        return CarbonImmutable::parse((string) $value, (string) config('app.timezone', 'UTC'))
            ->setTimezone(self::TIMEZONE);
    }

    /** @param list<array{start: string, end: string}>|null $windows */
    private function covers(?array $windows, CarbonImmutable $start): bool
    {
        $time = $start->format('H:i');
        foreach ($this->normalize($windows) as $window) {
            if ($time >= $window['start'] && $time < $window['end']) {
                return true;
            }
        }

        return false;
    }
}
