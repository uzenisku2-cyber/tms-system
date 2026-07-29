<?php

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReportEvent extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_CREATED = 'created';

    public const TYPE_DELEGATED_ENTRY_RECORDED = 'delegated_entry_recorded';

    public const TYPE_UPDATED = 'updated';

    public const TYPE_SUBMITTED = 'submitted';

    public const TYPE_REVIEW_STARTED = 'review_started';

    public const TYPE_CORRECTION_REQUESTED = 'correction_requested';

    public const TYPE_CORRECTED = 'corrected';

    public const TYPE_RESUBMITTED = 'resubmitted';

    public const TYPE_APPROVED = 'approved';

    public const TYPE_CLOSED = 'closed';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_CREATED,
        self::TYPE_DELEGATED_ENTRY_RECORDED,
        self::TYPE_UPDATED,
        self::TYPE_SUBMITTED,
        self::TYPE_REVIEW_STARTED,
        self::TYPE_CORRECTION_REQUESTED,
        self::TYPE_CORRECTED,
        self::TYPE_RESUBMITTED,
        self::TYPE_APPROVED,
        self::TYPE_CLOSED,
    ];

    protected $table = 'daily_report_events';

    /** @var list<string> */
    protected $fillable = [
        'daily_report_id',
        'organization_id',
        'event_type',
        'from_status',
        'to_status',
        'acted_by_user_id',
        'reason',
        'affected_fields',
        'metadata',
        'created_at',
    ];

    /** @return BelongsTo<DailyReport, $this> */
    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'acted_by_user_id',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'affected_fields' => 'array',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
