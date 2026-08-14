<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use DomainException;
use InvalidArgumentException;

final class FinancialSnapshotParcelMetricResolver
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{
     *   loaded_parcels:int,
     *   delivered_parcels:int,
     *   redirected_parcels:int,
     *   undelivered_parcels:int,
     *   customer_rejected_parcels:int,
     *   not_delivered_parcels:int,
     *   processed_parcels:int
     * }
     */
    public function resolve(array $snapshot): array
    {
        $loaded = $this->value($snapshot, 'loaded_parcels');
        $delivered = $this->value($snapshot, 'delivered_parcels');
        $redirected = $this->value($snapshot, 'redirected_parcels');

        // Historical field semantics:
        // undelivered_parcels = customer rejected after service attempt.
        $rejected = $this->value($snapshot, 'undelivered_parcels');

        $processed = $delivered + $redirected + $rejected;

        if ($processed > $loaded) {
            throw new DomainException(
                'Processed parcel count cannot exceed loaded parcel count.',
            );
        }

        return [
            'loaded_parcels' => $loaded,
            'delivered_parcels' => $delivered,
            'redirected_parcels' => $redirected,
            'undelivered_parcels' => $rejected,
            'customer_rejected_parcels' => $rejected,
            'not_delivered_parcels' => $loaded - $processed,
            'processed_parcels' => $processed,
        ];
    }

    /** @param array<string, mixed> $snapshot */
    private function value(array $snapshot, string $field): int
    {
        $value = $snapshot[$field] ?? null;

        if (
            ! is_int($value)
            && ! (is_string($value) && ctype_digit($value))
        ) {
            throw new InvalidArgumentException(
                sprintf('Parcel metric [%s] must be a non-negative integer.', $field),
            );
        }

        return (int) $value;
    }
}
