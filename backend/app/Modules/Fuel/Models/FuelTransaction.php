<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use Illuminate\Database\Eloquent\Model;

final class FuelTransaction extends Model
{
    protected $fillable = ['public_id', 'owner_organization_id', 'provider', 'provider_transaction_identifier', 'transaction_fingerprint', 'occurred_at', 'posting_date', 'provider_card_identifier', 'fuel_card_id', 'fuel_card_assignment_id', 'responsible_organization_id', 'driver_id', 'vehicle_id', 'match_status', 'match_method', 'station_identifier', 'station_name', 'station_address', 'product_code', 'product_name', 'quantity', 'unit_of_measure', 'unit_price', 'net_amount', 'tax_amount', 'gross_amount', 'discount_amount', 'tax_rate', 'currency', 'vehicle_registration', 'odometer', 'invoice_reference', 'source_description', 'fuel_import_batch_id', 'source_row'];

    protected $casts = ['occurred_at' => 'datetime', 'posting_date' => 'date', 'quantity' => 'decimal:6', 'unit_price' => 'decimal:6', 'net_amount' => 'decimal:6', 'tax_amount' => 'decimal:6', 'gross_amount' => 'decimal:6', 'discount_amount' => 'decimal:6', 'tax_rate' => 'decimal:4', 'odometer' => 'decimal:2', 'source_row' => 'integer'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
