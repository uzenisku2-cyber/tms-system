<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DepotImportEvent extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_DRAFT_CREATED = 'draft_created';

    public const TYPE_SOURCE_DRIVER_MAPPED = 'source_driver_mapped';

    public const TYPE_IMPORT_FINALIZED = 'import_finalized';

    public const TYPE_IMPORT_CANCELLED = 'import_cancelled';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_DRAFT_CREATED,
        self::TYPE_SOURCE_DRIVER_MAPPED,
        self::TYPE_IMPORT_FINALIZED,
        self::TYPE_IMPORT_CANCELLED,
    ];

    protected $table = 'depot_import_events';

    /** @var list<string> */
    protected $fillable = [
        'depot_import_batch_id',
        'depot_import_row_id',
        'organization_id',
        'event_type',
        'acted_by_user_id',
        'reason',
        'before_payload',
        'after_payload',
        'protected_totals_sha256_before',
        'protected_totals_sha256_after',
        'created_at',
    ];

    /** @return BelongsTo<DepotImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            DepotImportBatch::class,
            'depot_import_batch_id',
        );
    }

    /** @return BelongsTo<DepotImportRow, $this> */
    public function row(): BelongsTo
    {
        return $this->belongsTo(
            DepotImportRow::class,
            'depot_import_row_id',
        );
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'depot_import_batch_id' => 'integer',
            'depot_import_row_id' => 'integer',
            'organization_id' => 'integer',
            'acted_by_user_id' => 'integer',
            'before_payload' => 'array',
            'after_payload' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
