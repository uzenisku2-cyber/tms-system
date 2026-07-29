<?php

namespace App\Modules\DailyReports\Exceptions;

use DomainException;

final class InvalidDailyReportTransition extends DomainException
{
    public static function between(
        string $fromStatus,
        string $toStatus,
    ): self {
        return new self(
            sprintf(
                'Daily report transition from "%s" to "%s" is not allowed.',
                $fromStatus,
                $toStatus,
            ),
        );
    }
}
