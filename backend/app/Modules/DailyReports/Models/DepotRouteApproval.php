<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class DepotRouteApproval extends Model
{
    use HasUuids;

    protected $fillable = [
        'public_id', 'organization_id', 'daily_report_id', 'daily_report_version',
        'depot_import_batch_id', 'depot_import_row_id', 'depot_values_sha256',
        'approved_by_user_id', 'reason', 'approved_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected function casts(): array
    {
        return ['approved_at' => 'immutable_datetime'];
    }
}
