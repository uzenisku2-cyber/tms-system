<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverAvailabilityDay;
use App\Modules\Drivers\Models\DriverAvailabilityDayEvent;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\OrganizationMembership;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class DriverAvailabilityCalendarService
{
    public function __construct(
        private readonly DriverSupervisoryAuthorizationService $supervisory,
    ) {}

    public function month(User $actor, int $organizationId, string $month): array
    {
        $first = CarbonImmutable::createFromFormat('!Y-m-d', $month.'-01');
        if ($first->format('Y-m') !== $month) {
            abort(422, 'Invalid month.');
        }

        $supervisor = $actor->can(DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION);
        $this->assertMembership($actor, $organizationId, now()->toDateString());
        $driverIds = Driver::query()->where('user_id', $actor->getKey())->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        if ($supervisor) {
            $assignmentIds = $this->supervisory->visibleDriverOrganizationAssignmentIds(
                $actor, $organizationId, DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION,
            );
            $visible = DriverOrganizationAssignment::query()->whereIn('id', $assignmentIds)
                ->pluck('driver_id')->map(static fn ($id): int => (int) $id)->all();
            $ownUsers = OrganizationMembership::query()->where('organization_id', $organizationId)
                ->where('status', OrganizationMembership::STATUS_ACTIVE)
                ->whereDate('valid_from', '<=', now()->toDateString())
                ->where(static function (Builder $q): void {
                    $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', now()->toDateString());
                })->pluck('user_id');
            $ownDrivers = Driver::query()->whereIn('user_id', $ownUsers)
                ->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            $driverIds = array_merge($driverIds, $visible, $ownDrivers);
        }

        $driverIds = array_values(array_unique($driverIds));
        $drivers = Driver::query()->whereIn('id', $driverIds)->orderBy('last_name')
            ->orderBy('first_name')->get(['id', 'user_id', 'first_name', 'last_name']);
        $days = DriverAvailabilityDay::query()->where('organization_id', $organizationId)
            ->whereIn('driver_id', $driverIds)
            ->whereBetween('date', [$first->toDateString(), $first->endOfMonth()->toDateString()])
            ->orderBy('date')->get();

        return [
            'month' => $month,
            'timezone' => 'Europe/Prague',
            'can_confirm' => $supervisor,
            'drivers' => $drivers->map(static fn (Driver $driver): array => [
                'id' => (int) $driver->getKey(),
                'name' => $driver->full_name,
                'is_self' => (int) $driver->user_id === (int) $actor->getKey(),
            ])->values()->all(),
            'days' => $days->map(fn (DriverAvailabilityDay $day): array => $this->resource($day))->values()->all(),
        ];
    }

    public function submit(User $actor, int $organizationId, array $input): array
    {
        $driver = $this->authorizeDriver($actor, $organizationId, (int) $input['driver_id'], (string) $input['date']);
        if (! $driver->canOperate()) {
            abort(409, 'Driver is not active.');
        }
        $date = (string) $input['date'];
        $reason = trim((string) ($input['reason'] ?? ''));
        if ($input['availability'] === 'unavailable' && $reason === '') {
            abort(422, 'Reason is required for unavailability.');
        }

        try {
            return DB::transaction(function () use ($actor, $organizationId, $driver, $date, $input, $reason): array {
                $day = DriverAvailabilityDay::query()->where('organization_id', $organizationId)
                    ->where('driver_id', $driver->getKey())->whereDate('date', $date)
                    ->lockForUpdate()->first();
                $revision = $day === null ? 0 : (int) $day->revision;
                if ($revision !== (int) $input['expected_revision']) {
                    abort(409, 'Availability revision changed. Refresh the calendar.');
                }
                if ($day === null) {
                    $day = new DriverAvailabilityDay;
                    $day->organization_id = $organizationId;
                    $day->driver_id = (int) $driver->getKey();
                    $day->date = $date;
                    $day->timezone = 'Europe/Prague';
                }
                $day->availability = (string) $input['availability'];
                $day->decision = 'pending';
                $day->reason = $reason !== '' ? $reason : null;
                $day->revision = $revision + 1;
                $day->submitted_by_user_id = (int) $actor->getKey();
                $day->decided_by_user_id = null;
                $day->decided_at = null;
                $day->save();
                $this->event($day, 'submitted', $actor, $reason);

                return $this->resource($day);
            });
        } catch (QueryException $e) {
            abort(409, 'Availability changed concurrently. Refresh the calendar.');
        }
    }

    public function decide(User $actor, int $organizationId, int $id, array $input): array
    {
        if (! $actor->can(DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION)) {
            abort(403);
        }
        $this->assertMembership($actor, $organizationId, now()->toDateString());

        return DB::transaction(function () use ($actor, $organizationId, $id, $input): array {
            $day = DriverAvailabilityDay::query()->whereKey($id)
                ->where('organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            $this->authorizeDriver($actor, $organizationId, (int) $day->driver_id,
                CarbonImmutable::parse($day->date)->toDateString());
            if ($day->decision !== 'pending' || (int) $day->revision !== (int) $input['expected_revision']) {
                abort(409, 'Availability revision changed. Refresh the calendar.');
            }
            if ((int) $day->submitted_by_user_id === (int) $actor->getKey()) {
                abort(403, 'A submission requires a different confirming user.');
            }
            $day->decision = (string) $input['decision'];
            $day->revision = (int) $day->revision + 1;
            $day->decided_by_user_id = (int) $actor->getKey();
            $day->decided_at = now()->toDateTimeString();
            $day->save();
            $this->event($day, $day->decision, $actor, trim((string) $input['reason']));

            return $this->resource($day);
        });
    }

    private function authorizeDriver(User $actor, int $organizationId, int $driverId, string $date): Driver
    {
        $this->assertMembership($actor, $organizationId, now()->toDateString());
        $driver = Driver::query()->findOrFail($driverId);
        if ((int) $driver->user_id === (int) $actor->getKey()) {
            $this->assertMembership($actor, $organizationId, $date);

            return $driver;
        }
        $this->supervisory->findVisibleDriver($actor, $organizationId, $driverId);
        $this->assertMembership($actor, $organizationId, $date);
        $ownMembership = OrganizationMembership::query()->where('organization_id', $organizationId)
            ->where('user_id', $driver->user_id)->where('status', OrganizationMembership::STATUS_ACTIVE)
            ->whereDate('valid_from', '<=', $date)
            ->where(static function (Builder $q) use ($date): void {
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date);
            })->exists();
        $assignmentIds = $this->supervisory->visibleDriverOrganizationAssignmentIds(
            $actor, $organizationId, DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION,
        );
        $assigned = DriverOrganizationAssignment::query()->whereIn('id', $assignmentIds)
            ->where('driver_id', $driverId)->whereDate('valid_from', '<=', $date)
            ->where(static function (Builder $q) use ($date): void {
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date);
            })->exists();
        if (! $ownMembership && ! $assigned) {
            abort(404);
        }

        return $driver;
    }

    private function assertMembership(User $actor, int $organizationId, string $date): void
    {
        $exists = OrganizationMembership::query()->where('organization_id', $organizationId)
            ->where('user_id', $actor->getKey())->where('status', OrganizationMembership::STATUS_ACTIVE)
            ->whereDate('valid_from', '<=', $date)
            ->where(static function (Builder $q) use ($date): void {
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $date);
            })->exists();
        if (! $exists) {
            abort(403, 'Active organization membership required.');
        }
    }

    private function event(DriverAvailabilityDay $day, string $action, User $actor, string $reason): void
    {
        DriverAvailabilityDayEvent::query()->create([
            'availability_day_id' => (int) $day->getKey(),
            'revision' => (int) $day->revision,
            'action' => $action,
            'availability' => (string) $day->availability,
            'decision' => (string) $day->decision,
            'reason' => $reason !== '' ? $reason : null,
            'actor_user_id' => (int) $actor->getKey(),
            'created_at' => now(),
        ]);
    }

    private function resource(DriverAvailabilityDay $day): array
    {
        return [
            'id' => (int) $day->getKey(),
            'driver_id' => (int) $day->driver_id,
            'date' => CarbonImmutable::parse($day->date)->toDateString(),
            'availability' => (string) $day->availability,
            'decision' => (string) $day->decision,
            'reason' => $day->reason,
            'revision' => (int) $day->revision,
            'submitted_by_user_id' => (int) $day->submitted_by_user_id,
            'decided_by_user_id' => $day->decided_by_user_id === null ? null : (int) $day->decided_by_user_id,
        ];
    }
}
