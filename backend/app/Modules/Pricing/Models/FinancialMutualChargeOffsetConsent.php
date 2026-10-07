<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class FinancialMutualChargeOffsetConsent extends Model
{
    use HasUuids;

    public const ACCEPTED = 'accepted';

    public const REJECTED = 'rejected';

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'financial_mutual_charge_id', 'charge_revision', 'charge_fingerprint',
        'decision', 'actor_user_id', 'acting_organization_id', 'idempotency_key',
        'command_fingerprint', 'reason', 'decided_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public static function fingerprint(FinancialMutualCharge $charge): string
    {
        return hash('sha256', json_encode([
            'id' => (string) $charge->public_id,
            'revision' => (int) $charge->revision,
            'party_type' => (string) $charge->counterparty_type,
            'party_organization' => $charge->counterparty_organization_id,
            'party_driver' => $charge->counterparty_driver_id,
            'direction' => (string) $charge->direction,
            'category' => (string) $charge->category,
            'amount_minor' => (int) $charge->amount_minor,
            'currency' => (string) $charge->currency,
            'source_type' => (string) $charge->source_type,
            'source_public_id' => (string) $charge->source_public_id,
            'source_snapshot' => $charge->source_snapshot,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new RuntimeException('Offset decisions are immutable.'));
        self::deleting(static fn (): never => throw new RuntimeException('Offset decisions are immutable.'));
    }
}
