<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\IndexFinancialAdministrationRequest;
use App\Modules\Pricing\Services\FinancialSettlementAdministrationReadService;
use App\Modules\Pricing\Services\FinancialSettlementBankPaymentReconciliationCsvExportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FinancialSettlementAdministrationReadController extends Controller
{
    public function mutualCharges(IndexFinancialAdministrationRequest $request, FinancialSettlementAdministrationReadService $service): JsonResponse
    {
        return response()->json(['data' => $service->mutualCharges($request->validated(), $this->organizationId($request), $this->actor($request))]);
    }

    public function mutualCharge(string $financialMutualCharge, IndexFinancialAdministrationRequest $request, FinancialSettlementAdministrationReadService $service): JsonResponse
    {
        return response()->json(['data' => $service->mutualCharge($financialMutualCharge, $this->organizationId($request), $this->actor($request))]);
    }

    public function statements(IndexFinancialAdministrationRequest $request, FinancialSettlementAdministrationReadService $service): JsonResponse
    {
        return response()->json(['data' => $service->statements($request->validated(), $this->organizationId($request), $this->actor($request))]);
    }

    public function statement(string $financialSettlementStatement, IndexFinancialAdministrationRequest $request, FinancialSettlementAdministrationReadService $service): JsonResponse
    {
        return response()->json(['data' => $service->statement($financialSettlementStatement, $this->organizationId($request), $this->actor($request))]);
    }

    public function reconciliationExport(IndexFinancialAdministrationRequest $request, FinancialSettlementBankPaymentReconciliationCsvExportService $export): StreamedResponse
    {
        $organizationId = $this->organizationId($request);
        $actor = $this->actor($request);
        abort_unless($actor->can('compensation.view'), 403);
        $filters = $request->validated();
        $filename = $export->filename();

        return response()->streamDownload(
            static function () use ($export, $organizationId, $actor, $filters): void {
                $output = fopen('php://output', 'wb');
                if ($output === false) {
                    throw new \RuntimeException('Unable to open the CSV output stream.');
                }

                try {
                    $export->write($organizationId, $actor, $filters, $output);
                } finally {
                    fclose($output);
                }
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private function organizationId(IndexFinancialAdministrationRequest $request): int
    {
        return (int) $request->attributes->get('organization_id');
    }

    private function actor(IndexFinancialAdministrationRequest $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        return $actor;
    }
}
