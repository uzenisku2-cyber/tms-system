<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Services\DailyReportFormFieldDefinitionService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class DailyReportFormFieldDefinitionServiceTest extends TestCase
{
    public function test_it_normalizes_canonical_fields_and_adds_metadata(): void
    {
        $fields = $this->canonicalFields();

        $normalized = (new DailyReportFormFieldDefinitionService)
            ->normalize($fields);

        self::assertCount(12, $normalized);
        self::assertSame('date', $normalized[0]['type']);
        self::assertTrue($normalized[0]['system']);
        self::assertFalse($normalized[0]['custom']);

        self::assertSame('money', $normalized[10]['type']);
        self::assertFalse($normalized[10]['custom']);
    }

    public function test_it_accepts_custom_field_with_stable_key_label_and_type(): void
    {
        $fields = $this->canonicalFields();

        $fields[] = [
            'key' => 'custom_0123456789abcdef0123456789abcdef',
            'label' => 'Počet svozů',
            'type' => 'number',
            'order' => 13,
            'visible' => true,
            'required' => false,
        ];

        $normalized = (new DailyReportFormFieldDefinitionService)
            ->normalize($fields);

        self::assertCount(13, $normalized);
        self::assertSame(
            'custom_0123456789abcdef0123456789abcdef',
            $normalized[12]['key'],
        );
        self::assertSame('Počet svozů', $normalized[12]['label']);
        self::assertSame('number', $normalized[12]['type']);
        self::assertTrue($normalized[12]['custom']);
        self::assertFalse($normalized[12]['system']);
    }

    public function test_it_rejects_missing_canonical_field(): void
    {
        $fields = $this->canonicalFields();
        array_pop($fields);

        $this->expectException(ValidationException::class);

        (new DailyReportFormFieldDefinitionService)
            ->normalize($fields);
    }

    public function test_it_rejects_invalid_custom_key(): void
    {
        $fields = $this->canonicalFields();

        $fields[] = [
            'key' => 'my-field',
            'label' => 'Moje pole',
            'type' => 'text',
            'order' => 13,
            'visible' => true,
            'required' => false,
        ];

        $this->expectException(ValidationException::class);

        (new DailyReportFormFieldDefinitionService)
            ->normalize($fields);
    }

    public function test_required_custom_field_must_be_visible(): void
    {
        $fields = $this->canonicalFields();

        $fields[] = [
            'key' => 'custom_abcdefabcdefabcdefabcdefabcdefab',
            'label' => 'Kontrola',
            'type' => 'boolean',
            'order' => 13,
            'visible' => false,
            'required' => true,
        ];

        $this->expectException(ValidationException::class);

        (new DailyReportFormFieldDefinitionService)
            ->normalize($fields);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function canonicalFields(): array
    {
        $keys = [
            'service_date',
            'route_number',
            'departure_time',
            'arrival_time',
            'actual_km',
            'planned_km',
            'loaded_parcels',
            'delivered_parcels',
            'redirected_parcels',
            'undelivered_parcels',
            'surcharge_amount',
            'operational_notes',
        ];

        return array_map(
            static fn (string $key, int $index): array => [
                'key' => $key,
                'order' => $index + 1,
                'visible' => true,
                'required' => ! in_array(
                    $key,
                    [
                        'surcharge_amount',
                        'operational_notes',
                    ],
                    true,
                ),
            ],
            $keys,
            array_keys($keys),
        );
    }
}
