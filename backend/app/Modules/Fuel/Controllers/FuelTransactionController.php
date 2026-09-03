<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Fuel\Requests\IndexFuelTransactionRequest;
use App\Modules\Fuel\Services\FuelTransactionAdministrationService;
use App\Modules\Fuel\Services\FuelTransactionCsvExportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FuelTransactionController
{
    public function export(
        IndexFuelTransactionRequest $request,
        OrganizationContext $context,
        FuelTransactionAdministrationService $administration,
        FuelTransactionCsvExportService $export,
    ): StreamedResponse {
        $organizationId = $context->requireId();
        $filters = $request->validated();

        return response()->streamDownload(
            static function () use ($administration, $export, $organizationId, $filters): void {
                $output = fopen('php://output', 'wb');
                if ($output === false) {
                    throw new \RuntimeException('Unable to open the CSV output stream.');
                }

                try {
                    $export->write($administration->exportRows($organizationId, $filters), $output);
                } finally {
                    fclose($output);
                }
            },
            $export->filename(),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function overview(
        IndexFuelTransactionRequest $request,
        OrganizationContext $context,
        FuelTransactionAdministrationService $service,
    ): JsonResponse {
        return response()->json(['data' => $service->overview($context->requireId(), $request->validated())]);
    }

    public function index(
        IndexFuelTransactionRequest $request,
        OrganizationContext $context,
        FuelTransactionAdministrationService $service,
    ): JsonResponse {
        $data = $service->index($context->requireId(), $request->validated());
        $data['capabilities'] = [
            'can_manage_reconciliation' => $request->user()?->can('compensation.manage') ?? false,
        ];

        return response()->json(['data' => $data]);
    }
}
