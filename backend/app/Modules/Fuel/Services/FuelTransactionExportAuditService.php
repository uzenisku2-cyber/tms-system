<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Modules\Fuel\Models\FuelTransactionExportEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use LogicException;

final class FuelTransactionExportAuditService
{
    private const FILTER_KEYS = [
        'date_from',
        'date_to',
        'provider',
        'driver_id',
        'reconciliation_status',
        'card',
        'search',
    ];

    /** @param array<string, mixed> $filters */
    public function recordSuccessful(
        int $organizationId,
        int $actorId,
        array $filters,
        int $rowCount,
        string $filename,
    ): FuelTransactionExportEvent {
        return FuelTransactionExportEvent::query()->create([
            'public_id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'exported_by_user_id' => $actorId,
            'format' => 'csv',
            'filename' => basename($filename),
            'filters' => $this->normalizeFilters($filters),
            'row_count' => max(0, $rowCount),
            'exported_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = [];
        foreach (self::FILTER_KEYS as $key) {
            $value = $filters[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            if ($key === 'driver_id') {
                $normalized[$key] = (int) $value;

                continue;
            }
            if ($key === 'card') {
                $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
                if ($digits !== '') {
                    $normalized['card_last_four'] = substr($digits, -4);
                }

                continue;
            }
            $normalized[$key] = trim((string) $value);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function history(int $organizationId, array $filters): array
    {
        $perPage = (int) ($filters['per_page'] ?? 25);
        $page = (int) ($filters['page'] ?? 1);
        $paginator = FuelTransactionExportEvent::query()
            ->where('organization_id', $organizationId)
            ->with('exportedBy:id,name,email')
            ->orderByDesc('exported_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(static function (FuelTransactionExportEvent $event): array {
                $exportedAt = $event->getAttribute('exported_at');
                if ($exportedAt !== null && ! $exportedAt instanceof CarbonInterface) {
                    throw new LogicException('Unexpected export audit timestamp.');
                }

                return [
                    'public_id' => $event->public_id,
                    'format' => $event->format,
                    'filename' => $event->filename,
                    'filters' => $event->filters,
                    'row_count' => $event->row_count,
                    'exported_at' => $exportedAt?->toISOString(),
                    'exported_by' => $event->exportedBy === null ? null : [
                        'id' => (int) $event->exportedBy->getKey(),
                        'name' => (string) $event->exportedBy->name,
                    ],
                ];
            })->values()->all(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
