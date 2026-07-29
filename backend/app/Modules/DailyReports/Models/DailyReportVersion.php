<?php

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReportVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'daily_report_versions';

    /** @var list<string> */
    protected $fillable = [
        'daily_report_id',
        'version_number',
        'snapshot',
        'changed_fields',
        'created_by_user_id',
        'change_reason',
        'created_at',
    ];

    /** @return BelongsTo<DailyReport, $this> */
    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'snapshot' => 'array',
            'changed_fields' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
