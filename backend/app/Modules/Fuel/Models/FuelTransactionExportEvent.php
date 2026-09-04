<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FuelTransactionExportEvent extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'fuel_transaction_export_events';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'exported_by_user_id',
        'format',
        'filename',
        'filters',
        'row_count',
        'exported_at',
        'created_at',
    ];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'exported_by_user_id' => 'integer',
            'filters' => 'array',
            'row_count' => 'integer',
            'exported_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
