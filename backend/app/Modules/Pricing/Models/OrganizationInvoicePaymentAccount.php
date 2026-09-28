<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;

final class OrganizationInvoicePaymentAccount extends Model
{
    protected $fillable = ['organization_id', 'revision', 'iban', 'account_holder',
        'source_reference', 'reason', 'confirmed_at', 'confirmed_by_user_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'confirmed_at' => 'immutable_datetime'];
    }
}
