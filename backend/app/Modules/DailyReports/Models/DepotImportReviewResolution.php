<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DepotImportReviewResolution extends Model
{
    public const TYPE_DRIVER_ATTRIBUTION_CORRECTED = 'driver_attribution_corrected';

    public const TYPE_ZERO_VALUE_IGNORED = 'zero_value_ignored';

    protected $table = 'depot_import_review_resolutions';

    /** @var list<string> */
    protected $fillable = [
        'depot_import_batch_id', 'depot_import_row_id', 'organization_id',
        'resolution_type', 'corrected_driver_id',
        'corrected_driver_organization_assignment_id', 'reason',
        'resolved_by_user_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DepotImportBatch::class, 'depot_import_batch_id');
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(DepotImportRow::class, 'depot_import_row_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function correctedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'corrected_driver_id');
    }

    public function correctedAssignment(): BelongsTo
    {
        return $this->belongsTo(DriverOrganizationAssignment::class, 'corrected_driver_organization_assignment_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'depot_import_batch_id' => 'integer', 'depot_import_row_id' => 'integer',
            'organization_id' => 'integer', 'corrected_driver_id' => 'integer',
            'corrected_driver_organization_assignment_id' => 'integer',
            'resolved_by_user_id' => 'integer',
        ];
    }
}
