<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BillingDocumentCommercialIdentityEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'billing_document_commercial_identity_id', 'revision',
        'event_type', 'reason', 'evidence', 'occurred_at', 'actor_user_id',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'evidence' => 'array', 'occurred_at' => 'immutable_datetime'];
    }

    public function commercialIdentity(): BelongsTo
    {
        return $this->belongsTo(BillingDocumentCommercialIdentity::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
