<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DepotImportBatch extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_READY = 'ready';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_READY,
        self::STATUS_IMPORTED,
        self::STATUS_CANCELLED,
    ];

    protected $table = 'depot_import_batches';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'created_by_user_id',
        'status',
        'lock_version',
        'original_filename',
        'source_sha256',
        'schema_fingerprint',
        'sheet_name',
        'header_start_row',
        'header_end_row',
        'data_start_row',
        'confirmed_carrier_alias',
        'confirmed_carrier_alias_normalized',
        'period_from',
        'period_until',
        'row_count',
        'ready_row_count',
        'no_run_row_count',
        'excluded_carrier_row_count',
        'source_driver_count',
        'unassigned_ready_row_count',
        'source_totals',
        'protected_totals_sha256',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'lock_version' => 1,
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id',
        );
    }

    /** @return HasMany<DepotImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(DepotImportRow::class);
    }

    /** @return HasMany<DepotImportEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(DepotImportEvent::class)
            ->orderBy('id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'created_by_user_id' => 'integer',
            'lock_version' => 'integer',
            'header_start_row' => 'integer',
            'header_end_row' => 'integer',
            'data_start_row' => 'integer',
            'period_from' => 'date',
            'period_until' => 'date',
            'row_count' => 'integer',
            'ready_row_count' => 'integer',
            'no_run_row_count' => 'integer',
            'excluded_carrier_row_count' => 'integer',
            'source_driver_count' => 'integer',
            'unassigned_ready_row_count' => 'integer',
            'source_totals' => 'array',
        ];
    }
}
