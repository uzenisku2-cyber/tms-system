<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Fuel\Requests\IndexFuelTransactionRequest;
use App\Modules\Fuel\Services\FuelTransactionAdministrationService;
use App\Modules\Fuel\Services\FuelTransactionCsvExportService;
use App\Modules\Fuel\Services\FuelTransactionExportAuditService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FuelTransactionController
{
    public function export(
        IndexFuelTransactionRequest $request,
        OrganizationContext $context,
        FuelTransactionAdministrationService $administration,
        FuelTransactionCsvExportService $export,
        FuelTransactionExportAuditService $audit,
    ): StreamedResponse {
        $organizationId = $context->requireId();
        $filters = $request->validated();
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401, 'Unauthenticated.');
        }
        $actorId = (int) $actor->getAuthIdentifier();
        $filename = $export->filename();

        return response()->streamDownload(
            static function () use ($administration, $export, $audit, $organizationId, $actorId, $filters, $filename): void {
                $output = fopen('php://output', 'wb');
                if ($output === false) {
                    throw new \RuntimeException('Unable to open the CSV output stream.');
                }

                try {
                    $rowCount = $export->write($administration->exportRows($organizationId, $filters), $output);
                    $audit->recordSuccessful($organizationId, $actorId, $filters, $rowCount, $filename);
                } finally {
                    fclose($output);
                }
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function exportHistory(
        IndexFuelTransactionRequest $request,
        OrganizationContext $context,
        FuelTransactionExportAuditService $audit,
    ): JsonResponse {
        return response()->json(['data' => $audit->history($context->requireId(), $request->validated())]);
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
