<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Throwable;

final class DriverOperationalWriteEligibilityGuard
{
    public static function assertEligible(
        int $driverId,
        mixed $serviceDate,
    ): void {
        if ($driverId <= 0) {
            throw new LogicException(
                'Driver identifier must be positive.',
            );
        }

        $serviceDateValue =
            self::dateString(
                $serviceDate,
            );

        $today =
            CarbonImmutable::today()
                ->toDateString();

        if (
            ! self::assignmentExists(
                $driverId,
                $today,
            )
        ) {
            throw new AuthorizationException(
                'Řidič nemá k dnešnímu dni aktivní organizační přiřazení. Vlastní zápis nebo změna trasy není povolena.',
            );
        }

        if (
            ! self::assignmentExists(
                $driverId,
                $serviceDateValue,
            )
        ) {
            throw new AuthorizationException(
                'Řidič nemá k datu trasy platné organizační přiřazení.',
            );
        }
    }

    private static function assignmentExists(
        int $driverId,
        string $date,
    ): bool {
        return DriverOrganizationAssignment::query()
            ->where(
                'driver_id',
                $driverId,
            )
            ->whereDate(
                'valid_from',
                '<=',
                $date,
            )
            ->where(
                static function (
                    Builder $query,
                ) use (
                    $date,
                ): void {
                    $query
                        ->whereNull(
                            'valid_until',
                        )
                        ->orWhereDate(
                            'valid_until',
                            '>=',
                            $date,
                        );
                },
            )
            ->exists();
    }

    private static function dateString(
        mixed $value,
    ): string {
        if (
            $value instanceof DateTimeInterface
        ) {
            return CarbonImmutable::instance(
                $value,
            )->toDateString();
        }

        if (
            ! is_string($value)
            || trim($value) === ''
        ) {
            throw new LogicException(
                'Daily report service date is unavailable.',
            );
        }

        try {
            return CarbonImmutable::parse(
                trim($value),
            )->toDateString();
        } catch (Throwable $exception) {
            throw new LogicException(
                'Daily report service date is invalid.',
                0,
                $exception,
            );
        }
    }
}
