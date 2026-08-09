<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use Illuminate\Validation\ValidationException;

final class DailyReportFormFieldDefinitionService
{
    public const MAX_FIELDS = 40;

    /**
     * `system=true` means the field is locked visible + required.
     *
     * @var array<string, array{
     *     label:string,
     *     type:string,
     *     system:bool
     * }>
     */
    private const CANONICAL_FIELDS = [
        'service_date' => [
            'label' => 'Datum',
            'type' => 'date',
            'system' => true,
        ],
        'route_number' => [
            'label' => 'Trasa č.',
            'type' => 'text',
            'system' => true,
        ],
        'departure_time' => [
            'label' => 'Čas odjezdu',
            'type' => 'time',
            'system' => false,
        ],
        'arrival_time' => [
            'label' => 'Čas příjezdu',
            'type' => 'time',
            'system' => false,
        ],
        'actual_km' => [
            'label' => 'Trasa naměřená',
            'type' => 'number',
            'system' => false,
        ],
        'planned_km' => [
            'label' => 'Trasa plánovaná',
            'type' => 'number',
            'system' => false,
        ],
        'loaded_parcels' => [
            'label' => 'Naloženo ks',
            'type' => 'number',
            'system' => false,
        ],
        'delivered_parcels' => [
            'label' => 'Doručeno na adresu',
            'type' => 'number',
            'system' => false,
        ],
        'redirected_parcels' => [
            'label' => 'Doručeno na výdejní místo',
            'type' => 'number',
            'system' => false,
        ],
        'undelivered_parcels' => [
            'label' => 'Odmítnuté ks',
            'type' => 'number',
            'system' => false,
        ],
        'surcharge_amount' => [
            'label' => 'Příplatek',
            'type' => 'money',
            'system' => false,
        ],
        'operational_notes' => [
            'label' => 'Poznámka',
            'type' => 'text',
            'system' => false,
        ],
    ];

    /** @var list<string> */
    private const CUSTOM_TYPES = [
        'text',
        'number',
        'time',
        'money',
        'boolean',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return list<array{
     *     key:string,
     *     label:string,
     *     type:string,
     *     order:int,
     *     visible:bool,
     *     required:bool,
     *     system:bool,
     *     custom:bool
     * }>
     */
    public function normalize(array $fields): array
    {
        $fieldCount = count($fields);

        if (
            $fieldCount < count(self::CANONICAL_FIELDS)
            || $fieldCount > self::MAX_FIELDS
        ) {
            throw ValidationException::withMessages([
                'fields' => [
                    'Konfigurace musí obsahovat všech 12 systémových provozních položek a nejvýše 40 položek celkem.',
                ],
            ]);
        }

        $keys = array_map(
            static fn (array $field): string =>
                trim((string) ($field['key'] ?? '')),
            $fields,
        );

        if (
            count(array_unique($keys))
            !== count($keys)
        ) {
            throw ValidationException::withMessages([
                'fields' => [
                    'Každá položka musí mít jedinečný technický klíč.',
                ],
            ]);
        }

        $missingCanonical = array_diff(
            array_keys(self::CANONICAL_FIELDS),
            $keys,
        );

        if ($missingCanonical !== []) {
            throw ValidationException::withMessages([
                'fields' => [
                    'Konfigurace musí zachovat všech 12 systémových provozních položek.',
                ],
            ]);
        }

        $orders = array_map(
            static fn (array $field): int =>
                (int) ($field['order'] ?? 0),
            $fields,
        );

        $expectedOrders = range(1, $fieldCount);
        $sortedOrders = $orders;
        sort($sortedOrders);

        if ($sortedOrders !== $expectedOrders) {
            throw ValidationException::withMessages([
                'fields' => [
                    'Pořadí položek musí být souvislé od 1 do počtu položek.',
                ],
            ]);
        }

        $normalized = [];

        foreach ($fields as $field) {
            $key = trim((string) ($field['key'] ?? ''));

            if (array_key_exists($key, self::CANONICAL_FIELDS)) {
                $catalog = self::CANONICAL_FIELDS[$key];
                $system = (bool) $catalog['system'];
                $visible = $system
                    ? true
                    : (bool) ($field['visible'] ?? false);
                $required = $system
                    ? true
                    : (bool) ($field['required'] ?? false);

                $this->assertRequiredVisible(
                    $required,
                    $visible,
                );

                $normalized[] = [
                    'key' => $key,
                    'label' => $catalog['label'],
                    'type' => $catalog['type'],
                    'order' => (int) $field['order'],
                    'visible' => $visible,
                    'required' => $required,
                    'system' => $system,
                    'custom' => false,
                ];

                continue;
            }

            if (
                preg_match(
                    '/^custom_[a-z0-9]{12,56}$/',
                    $key,
                ) !== 1
            ) {
                throw ValidationException::withMessages([
                    'fields' => [
                        'Vlastní položka má neplatný technický klíč.',
                    ],
                ]);
            }

            $label = trim(
                (string) ($field['label'] ?? ''),
            );

            if (
                $label === ''
                || mb_strlen($label, 'UTF-8') > 100
            ) {
                throw ValidationException::withMessages([
                    'fields' => [
                        'Vlastní položka musí mít název o délce 1 až 100 znaků.',
                    ],
                ]);
            }

            $type = trim(
                (string) ($field['type'] ?? ''),
            );

            if (! in_array($type, self::CUSTOM_TYPES, true)) {
                throw ValidationException::withMessages([
                    'fields' => [
                        'Vlastní položka má nepodporovaný typ hodnoty.',
                    ],
                ]);
            }

            $visible = (bool) ($field['visible'] ?? false);
            $required = (bool) ($field['required'] ?? false);

            $this->assertRequiredVisible(
                $required,
                $visible,
            );

            $normalized[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'order' => (int) $field['order'],
                'visible' => $visible,
                'required' => $required,
                'system' => false,
                'custom' => true,
            ];
        }

        usort(
            $normalized,
            static fn (array $left, array $right): int =>
                $left['order'] <=> $right['order'],
        );

        return $normalized;
    }

    private function assertRequiredVisible(
        bool $required,
        bool $visible,
    ): void {
        if ($required && ! $visible) {
            throw ValidationException::withMessages([
                'fields' => [
                    'Povinná položka musí být současně zobrazena.',
                ],
            ]);
        }
    }
}