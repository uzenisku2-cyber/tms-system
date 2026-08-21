<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Models\DriverQualityProfile;
use App\Modules\DailyReports\Models\DriverQualityProfileBinding;
use App\Modules\DailyReports\Models\DriverQualityProfileVersion;

final readonly class DriverQualityProfileResolution
{
    public const REASON_RESOLVED = 'resolved';

    public const REASON_UNCONFIGURED = 'unconfigured';

    public const REASON_PROFILE_UNAVAILABLE = 'profile_unavailable';

    public const REASON_VERSION_UNAVAILABLE = 'version_unavailable';

    public function __construct(
        public string $reason,
        public ?string $scopeType = null,
        public ?DriverQualityProfileBinding $binding = null,
        public ?DriverQualityProfile $profile = null,
        public ?DriverQualityProfileVersion $version = null,
    ) {}

    public function hasEffectiveVersion(): bool
    {
        return $this->reason === self::REASON_RESOLVED
            && $this->version instanceof DriverQualityProfileVersion;
    }
}
