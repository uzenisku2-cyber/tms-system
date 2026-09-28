<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class CustomerInvoiceBankPaymentEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'customer_invoice_bank_payment_id', 'revision', 'event_type',
        'idempotency_key', 'command_fingerprint', 'reason', 'evidence', 'actor_user_id', 'occurred_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'evidence' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
