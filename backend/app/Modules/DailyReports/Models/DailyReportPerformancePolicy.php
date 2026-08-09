<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use Illuminate\Database\Eloquent\Model;

final class DailyReportPerformancePolicy extends Model
{
    public const ORGANIZATION_SCOPE =
        '__organization__';

    protected $table =
        'daily_report_performance_policies';

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'scope_key',
        'route_number',
        'route_number_normalized',
        'redirected_max_percent',
        'kilometre_deviation_max_percent',
        'delivered_address_min_percent',
        'rejected_max_percent',
        'not_delivered_max_percent',
        'updated_by_user_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'redirected_max_percent' => 'decimal:2',
            'kilometre_deviation_max_percent' => 'decimal:2',
            'delivered_address_min_percent' => 'decimal:2',
            'rejected_max_percent' => 'decimal:2',
            'not_delivered_max_percent' => 'decimal:2',
            'updated_by_user_id' => 'integer',
        ];
    }
}
