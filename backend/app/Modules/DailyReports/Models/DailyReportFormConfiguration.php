<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DailyReportFormConfiguration extends Model
{
    protected $fillable = [
        'organization_id',
        'version',
        'valid_from',
        'valid_until',
        'fields',
        'created_by_user_id',
        'ended_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'fields' => 'array',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
        );
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id',
        );
    }

    /** @return BelongsTo<User, $this> */
    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ended_by_user_id',
        );
    }
}
