<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DriverQualityProfileComponent extends Model
{
    public const UPDATED_AT = null;

    public const SOURCE_DELIVERED = 'delivered_parcels';

    public const SOURCE_REDIRECTED = 'redirected_parcels';

    public const SOURCE_CUSTOMER_REJECTED =
        'customer_rejected_parcels';

    /** @var list<string> */
    public const SOURCES = [
        self::SOURCE_DELIVERED,
        self::SOURCE_REDIRECTED,
        self::SOURCE_CUSTOMER_REJECTED,
    ];

    protected $table = 'driver_quality_profile_components';

    /** @var list<string> */
    protected $fillable = [
        'driver_quality_profile_version_id',
        'source_code',
        'position',
        'created_at',
    ];

    /** @return BelongsTo<DriverQualityProfileVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(
            DriverQualityProfileVersion::class,
            'driver_quality_profile_version_id',
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'driver_quality_profile_version_id' => 'integer',
            'position' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
