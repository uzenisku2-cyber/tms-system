<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class DepotDriverRecordReviewUiTest extends TestCase
{
    public function test_routes_navigation_exposes_audited_depot_driver_resolutions(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach (
            [
                'S030-01A DEPOT VERSUS DRIVER RECORD REVIEW UI',
                'data-drayvia-page="record-review"',
                'Trasy / provozní kontrola',
                'Kontrola zápisů',
                'Depo × Řidič',
                'Importovaná dávka depa',
                'drayviaDepotDriverReviewBatch',
                'drayviaDepotDriverReviewComparisonStatus',
                'drayviaDepotDriverReviewDriver',
                'drayviaDepotDriverReviewDateFrom',
                'drayviaDepotDriverReviewDateTo',
                'drayviaDepotDriverReviewRoute',
                '/api/v1/daily-reports/depot-imports/drafts',
                '/api/v1/daily-reports/record-review/depot-driver/${batch}?',
                'data?.filter_options?.drivers',
                "'record-review': recordReview",
                "if (page === 'record-review')",
                'bindDepotDriverRecordReview();',
                'Zdroj depa je pouze ke čtení.',
                'Opravit depo na',
                'Ignorovat nulový záznam',
                'Vrátit rozhodnutí',
                '/correct-driver',
                '/ignore-zero',
                '/resolution',
                "method: 'PATCH'",
                "method: 'POST'",
                "method: 'DELETE'",
                "'ignored'",
                'DRAYVIA · Provozní kontrola',
                'drayvia-record-review-dialog',
                'Důvod rozhodnutí',
                'Potvrdit opravu',
                'Potvrdit ignorování',
                'Potvrdit vrácení',
                'Důvod je povinný kvůli auditní stopě.',
            ] as $marker
        ) {
            self::assertStringContainsString($marker, $source);
        }

        $block = $this->reviewBlock($source);

        foreach (
            [
                "method: 'PUT'",
                'Rychle přijmout',
                'Rozdělit trasu',
                'Upravit číselné hodnoty depa',
                'window.prompt(',
            ] as $forbiddenMarker
        ) {
            self::assertStringNotContainsString(
                $forbiddenMarker,
                $block,
            );
        }
    }

    public function test_review_displays_kilometres_as_whole_numbers_only(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        $block = $this->reviewBlock($source);
        $formatterStart = strpos(
            $block,
            'const depotDriverReviewWholeKm = (value) =>',
        );
        $formatterEnd = strpos(
            $block,
            'const depotDriverReviewMoney = (value) =>',
            $formatterStart === false ? 0 : $formatterStart,
        );

        if (! is_int($formatterStart) || ! is_int($formatterEnd)) {
            self::fail('The whole-kilometre formatter was not found.');
        }

        $formatter = substr(
            $block,
            $formatterStart,
            $formatterEnd - $formatterStart,
        );

        self::assertStringContainsString(
            'minimumFractionDigits: 0',
            $formatter,
        );
        self::assertStringContainsString(
            'maximumFractionDigits: 0',
            $formatter,
        );
        self::assertStringContainsString(
            '}).format(numeric)} km`',
            $formatter,
        );
        self::assertStringNotContainsString('toFixed(', $formatter);
        self::assertStringNotContainsString(
            'maximumFractionDigits: 2',
            $formatter,
        );

        self::assertStringContainsString(
            "field === 'actual_km' || field === 'planned_km'",
            $block,
        );
        self::assertStringContainsString(
            'return depotDriverReviewWholeKm(value);',
            $block,
        );
        self::assertStringContainsString(
            'kilometry se zde zobrazují jako celá čísla',
            $block,
        );
    }

    private function reviewBlock(string $source): string
    {
        $start = strpos(
            $source,
            '// S030-01A DEPOT VERSUS DRIVER RECORD REVIEW UI',
        );
        $end = strpos(
            $source,
            'const settings = () =>',
            $start === false ? 0 : $start,
        );

        if (! is_int($start) || ! is_int($end)) {
            self::fail(
                'The isolated depot-driver review block was not found.',
            );
        }

        return substr($source, $start, $end - $start);
    }
}
