<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\DailyReports;

use App\Modules\DailyReports\Services\DepotCarrierAliasNormalizer;
use App\Modules\DailyReports\Services\DepotImportHeaderDetector;
use App\Modules\DailyReports\Services\DepotWorkbookReader;
use Tests\Support\DepotWorkbookFactory;
use Tests\TestCase;

final class DepotWorkbookReaderTest extends TestCase
{
    public function test_values_only_reader_detects_shifted_two_row_header_without_exposing_formula_text(): void
    {
        $path = DepotWorkbookFactory::create(
            [
                ['Měsíční přehled depa'],
                [
                    'Rok',
                    'Měsíc',
                    'Datum',
                    'Trasa',
                    'Dopravce',
                    'Jméno řidiče',
                    'Poznámka',
                    'Čas odjezdu',
                    'Čas příjezdu',
                    'Trasa km Naměřená Position',
                    'Trasa km Plánovaná Position',
                    'Naloženo',
                    'Doručeno na adresu ks',
                    'Doručeno na VM ks',
                    'Odmítnuté',
                    'Nerozvezeno',
                    'Výpočet',
                ],
                [
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    '(důvod příplatku)',
                    null,
                    null,
                    null,
                    null,
                    'ks',
                    null,
                    null,
                    'ks',
                    null,
                    null,
                ],
                [
                    2025,
                    6,
                    '02.06.2025',
                    35,
                    'Kökörčený',
                    'Hrůza Vít',
                    null,
                    '08:00',
                    '16:00',
                    164,
                    136,
                    78,
                    72,
                    6,
                    0,
                    0,
                    78,
                ],
            ],
            [
                'Q4' => [
                    'formula' => 'SUM(M4:O4)',
                    'value' => 78,
                ],
            ],
        );

        try {
            $workbook = (new DepotWorkbookReader)->read($path);
            $detected = (new DepotImportHeaderDetector)->detect(
                $workbook['sheets'],
            );

            self::assertSame(1, $workbook['formula_cell_count']);
            self::assertStringNotContainsString(
                'SUM(M4:O4)',
                json_encode($workbook, JSON_THROW_ON_ERROR),
            );
            self::assertSame('Depot', $detected['sheet_name']);
            self::assertSame(2, $detected['header_start_row']);
            self::assertSame(3, $detected['header_end_row']);
            self::assertSame(
                'C',
                $detected['columns']['service_date']['letter'],
            );
            self::assertSame(
                'L',
                $detected['columns']['loaded_parcels']['letter'],
            );
            self::assertArrayNotHasKey(
                'formula',
                $detected['columns'],
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_semantic_detection_survives_reordered_columns(): void
    {
        $path = DepotWorkbookFactory::create([
            [
                'Řidič',
                'Dopravce',
                'Datum trasy',
                'Naložené zásilky',
                'Trasa č.',
                'Doručeno na adresu',
            ],
            [
                'Kökörčený D.',
                'Kökörčeny',
                '2025-07-01',
                100,
                'ALLPLZ',
                95,
            ],
        ]);

        try {
            $workbook = (new DepotWorkbookReader)->read($path);
            $detected = (new DepotImportHeaderDetector)->detect(
                $workbook['sheets'],
            );

            self::assertSame(
                'A',
                $detected['columns']['source_driver_name']['letter'],
            );
            self::assertSame(
                'B',
                $detected['columns']['carrier_name']['letter'],
            );
            self::assertSame(
                'E',
                $detected['columns']['route_number']['letter'],
            );
            self::assertSame(2, $detected['data_start_row']);
        } finally {
            @unlink($path);
        }
    }

    public function test_real_depot_header_does_not_confuse_financial_surcharges_with_operational_surcharge(): void
    {
        $path = DepotWorkbookFactory::create([
            [
                'Rok',
                'Měsíc',
                'Datum',
                'Trasa',
                'Dopravce',
                'Jméno řidiče',
                'Poznámka',
                'Čas odjezdu',
                'Čas příjezdu',
                'Trasa km Naměřená Position ',
                'Trasa km Plánovaná Position',
                'Naloženo',
                'Doručeno na adresu ks',
                'Doručeno na VM ks',
                'Odmítnuté',
                'Příplatky',
                'Nerozvezeno',
                '∑ Doručeno',
                'Dílčí kvalita ',
                'Pracovní čas',
                'Najeté km',
                'Rozdíl nájezdu %',
                'Doručeno Kč',
                'Přesměr',
                'Nájezd km Kč',
                'Příplatek palivo Kč',
                'Měsíční kvalita',
                'Příplatek Kvalita Kč',
                'Náklady celkem Kč',
            ],
            [
                null,
                null,
                null,
                null,
                null,
                null,
                '(důvod příplatku, větší nájezd nad 10%,..)',
                null,
                null,
                null,
                null,
                'ks',
                null,
                null,
                'ks',
                null,
                null,
                '+odmítnuto',
                null,
                null,
                null,
                null,
                null,
                'Kč',
            ],
            [
                2025,
                6,
                '02.06.2025',
                16,
                'Kökörčený',
                'Kökörčený D.',
                null,
                '09:31',
                '16:55',
                231,
                198,
                74,
                69,
                5,
                0,
                0,
                0,
                74,
                1,
                '07:24',
                231,
                0.17,
                2277,
                75,
                924,
                0,
                1,
                444,
                3720,
            ],
        ]);

        try {
            $workbook = (new DepotWorkbookReader)->read($path);
            $detected = (new DepotImportHeaderDetector)->detect(
                $workbook['sheets'],
            );

            self::assertSame(1, $detected['header_start_row']);
            self::assertSame(2, $detected['header_end_row']);
            self::assertSame(3, $detected['data_start_row']);
            self::assertSame(
                'P',
                $detected['columns']['surcharge_amount']['letter'],
            );
            self::assertSame(
                'Příplatky',
                $detected['columns']['surcharge_amount']['header'],
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_carrier_alias_normalization_is_exact_after_diacritic_and_spacing_normalization(): void
    {
        $normalizer = new DepotCarrierAliasNormalizer;

        self::assertSame(
            'kokorceny',
            $normalizer->normalize(' Kökörčený '),
        );
        self::assertSame(
            'kokorceny',
            $normalizer->normalize('Kökörčeny'),
        );
        self::assertNotSame(
            $normalizer->normalize('Kökörčený'),
            $normalizer->normalize('Kokořín'),
        );
    }
}
