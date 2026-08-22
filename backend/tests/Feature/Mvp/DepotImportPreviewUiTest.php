<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class DepotImportPreviewUiTest extends TestCase
{
    public function test_imports_page_requires_alias_confirmation_and_uses_read_only_preview_endpoints(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach (
            [
                'S028-01A DEPOT IMPORT READ-ONLY PREVIEW',
                'drayviaDepotImportInspectForm',
                'drayviaDepotImportWorkbook',
                'drayviaDepotImportPreviewHost',
                'Potvrdit dopravce před náhledem',
                'Alias hlavního dopravce',
                'carrier_alias_confirmed',
                '/api/v1/daily-reports/depot-imports/inspect',
                '/api/v1/daily-reports/depot-imports/preview',
                'mapped_formula_cell_count',
                'Zdrojový soubor nebyl uložen ani změněn.',
                "sessionStorage.getItem('tms_mvp_token')",
                'if (depotImportToken)',
                'Bearer ${depotImportToken}',
                'const depotImportFormatDate = (value) =>',
                '`${match[3]}.${match[2]}.${match[1]}`',
                'depotImportFormatDate(row.service_date)',
                'bindDepotImportPreview();',
            ] as $marker
        ) {
            self::assertStringContainsString($marker, $source);
        }

        $start = strpos(
            $source,
            '// S028-01A DEPOT IMPORT READ-ONLY PREVIEW',
        );
        $end = strpos(
            $source,
            'const settings = () =>',
            $start === false ? 0 : $start,
        );

        if (! is_int($start) || ! is_int($end)) {
            self::fail(
                'The isolated depot-import preview block was not found.',
            );
        }

        $block = substr($source, $start, $end - $start);

        self::assertStringNotContainsString(
            'daily-reports/depot-imports/commit',
            $block,
        );
        self::assertStringNotContainsString(
            'method: \'DELETE\'',
            $block,
        );
        self::assertStringNotContainsString(
            'method: \'PUT\'',
            $block,
        );
        self::assertStringNotContainsString(
            'if (token)',
            $block,
        );
        self::assertStringNotContainsString(
            "row.service_date || '—'",
            $block,
        );
    }

    public function test_imports_page_persists_only_exact_depot_records_and_bulk_driver_name_mapping(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach (
            [
                'S028-04A DEPOT IMPORT AUDITED DRAFT ADMINISTRATION',
                'drayviaDepotImportDraftListHost',
                'drayviaDepotImportDraftHost',
                'Importy z depa',
                'Otevřít import',
                'Vytvořit auditovaný koncept',
                '/api/v1/daily-reports/depot-imports/drafts',
                '/source-driver',
                "method: 'PATCH'",
                '/finalize',
                '/cancel',
                "method: 'POST'",
                'expected_lock_version: batch.lock_version',
                'Hromadné přiřazení jmen z depa',
                'Přiřadit záznamy řidiči',
                'Přiřazení uloženo',
                'Záznamy z depa byly úspěšně přiřazeny zvolenému řidiči.',
                'Záznamy připravené k přiřazení',
                'záznamů',
                'Přesný obsah zápisu depa',
                'Dokončit import depa',
                'Dokončit import zápisů z depa?',
                'Zpět ke kontrole',
                'Potvrdit a dokončit import',
                'Import úspěšně uložen',
                'Stornovat import',
                'Stornovat import zápisů z depa?',
                'Důvod storna',
                'Potvrdit storno importu',
                'Import stornován',
                'Zdrojové hodnoty zůstaly zachovány.',
                'Nevzniknou trasy, denní výkazy ani párování.',
                'Nevytvoří se denní výkazy, párování ani rozdělení tras',
                'Trasy → Kontrola zápisů',
                'Nebyl vytvořen žádný denní výkaz ani párování.',
            ] as $marker
        ) {
            self::assertStringContainsString($marker, $source);
        }

        $start = strpos(
            $source,
            '// S028-04A DEPOT IMPORT AUDITED DRAFT ADMINISTRATION',
        );
        $end = strpos(
            $source,
            'const settings = () =>',
            $start === false ? 0 : $start,
        );

        if (! is_int($start) || ! is_int($end)) {
            self::fail(
                'The audited depot-import draft administration block was not found.',
            );
        }

        $block = substr($source, $start, $end - $start);

        self::assertStringNotContainsString(
            'window.confirm(',
            $block,
        );

        self::assertStringNotContainsString(
            '/rows/${row.public_id}/driver',
            $block,
        );
        self::assertStringNotContainsString(
            'DepotReconciliation',
            $block,
        );
        self::assertStringNotContainsString(
            '/comparisons',
            $block,
        );
        self::assertStringNotContainsString(
            '/allocations',
            $block,
        );
        self::assertStringNotContainsString(
            'Rychle přijmout hodnoty depa',
            $block,
        );
        self::assertStringNotContainsString(
            'Ruční přeřazení',
            $block,
        );
    }
}
