<?php

use App\Http\Middleware\ResolveOrganizationContext;
use App\Modules\Fleet\Controllers\BankStatementImportController;
use App\Modules\Fleet\Controllers\BankStatementImportDuplicateResolutionController;
use App\Modules\Fleet\Controllers\BankTransactionAmountBreakdownController;
use App\Modules\Fleet\Controllers\BankTransactionEvidenceController;
use App\Modules\Fleet\Controllers\VehicleController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationBankMatchingExecutionController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationBankMatchingHandoffController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationBillingDocumentHandoffController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationDepositOffsetController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationFinancialHandoffController;
use App\Modules\Fleet\Controllers\VehicleCostAllocationRepairFundController;
use App\Modules\Fleet\Controllers\VehicleRegistryAdministrationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware([ResolveOrganizationContext::class, 'perm:vehicle.view'])->group(function (): void {
        Route::get('vehicle-registry-administration', [VehicleRegistryAdministrationController::class, 'index'])->name('vehicle-registry-administration.index');
        Route::post('vehicle-registry-administration', [VehicleRegistryAdministrationController::class, 'store'])->middleware('perm:vehicle.manage')->name('vehicle-registry-administration.store');
        Route::patch('vehicle-registry-administration/{vehicle}', [VehicleRegistryAdministrationController::class, 'update'])->whereUuid('vehicle')->middleware('perm:vehicle.manage')->name('vehicle-registry-administration.update');
        Route::put('vehicle-registry-administration/{vehicle}/field-statuses/{fieldKey}', [VehicleRegistryAdministrationController::class, 'updateFieldStatus'])->whereUuid('vehicle')->middleware('perm:vehicle.manage')->name('vehicle-registry-administration.field-statuses.update');
        Route::get('vehicle-registry-administration/{vehicle}', [VehicleRegistryAdministrationController::class, 'show'])->whereUuid('vehicle')->name('vehicle-registry-administration.show');
    });
    Route::apiResource('vehicles', VehicleController::class);
    Route::post('vehicle-cost-allocations', [VehicleCostAllocationController::class, 'store'])->name('vehicle-cost-allocations.store');
    Route::get('vehicle-cost-allocations/{allocationUid}', [VehicleCostAllocationController::class, 'show'])->name('vehicle-cost-allocations.show');
    Route::post('vehicle-cost-allocations/{allocationUid}/approve', [VehicleCostAllocationController::class, 'approve'])->name('vehicle-cost-allocations.approve');
    Route::post('vehicle-cost-allocations/{allocationUid}/financial-handoff', [VehicleCostAllocationFinancialHandoffController::class, 'prepare'])->name('vehicle-cost-allocations.financial-handoff.prepare');
    Route::get('vehicle-cost-allocations/{allocationUid}/financial-handoff', [VehicleCostAllocationFinancialHandoffController::class, 'show'])->name('vehicle-cost-allocations.financial-handoff.show');
    Route::post('vehicle-cost-allocation-financial-handoff-instructions/{instructionPublicId}/billing-document', [VehicleCostAllocationBillingDocumentHandoffController::class, 'execute'])->name('vehicle-cost-allocation-financial-handoff-instructions.billing-document.execute');
    Route::post('vehicle-cost-allocation-financial-handoff-instructions/{instructionPublicId}/deposit-offset', [VehicleCostAllocationDepositOffsetController::class, 'acknowledge'])->name('vehicle-cost-allocation-financial-handoff-instructions.deposit-offset.acknowledge');
    Route::post('vehicle-cost-allocation-financial-handoff-instructions/{instructionPublicId}/repair-fund', [VehicleCostAllocationRepairFundController::class, 'reserve'])->name('vehicle-cost-allocation-financial-handoff-instructions.repair-fund.reserve');
    Route::post('vehicle-cost-allocation-financial-handoff-instructions/{instructionPublicId}/bank-matching', [VehicleCostAllocationBankMatchingHandoffController::class, 'prepare'])->name('vehicle-cost-allocation-financial-handoff-instructions.bank-matching.prepare');
    Route::middleware(ResolveOrganizationContext::class)->group(function (): void {
        Route::get('bank-statement-imports', [BankStatementImportController::class, 'index'])->name('bank-statement-imports.index');
        Route::post('bank-statement-imports', [BankStatementImportController::class, 'store'])->name('bank-statement-imports.store');
        Route::get('bank-statement-imports/{batch}', [BankStatementImportController::class, 'show'])->name('bank-statement-imports.show');
        Route::post('bank-statement-import-duplicate-candidates/{candidate}/resolution', [BankStatementImportDuplicateResolutionController::class, 'store'])->name('bank-statement-import-duplicate-candidates.resolution.store');
        Route::get('bank-transaction-evidence', [BankTransactionEvidenceController::class, 'index'])->name('bank-transaction-evidence.index');
        Route::get('bank-transaction-evidence/{evidence}', [BankTransactionEvidenceController::class, 'show'])->whereUuid('evidence')->name('bank-transaction-evidence.show');
        Route::get('bank-transaction-evidence/{evidence}/amount-breakdowns', [BankTransactionAmountBreakdownController::class, 'show'])->name('bank-transaction-evidence.amount-breakdowns.show');
        Route::post('bank-transaction-evidence/{evidence}/amount-breakdowns', [BankTransactionAmountBreakdownController::class, 'store'])->name('bank-transaction-evidence.amount-breakdowns.store');
    });
    Route::post('bank-transaction-evidence', [BankTransactionEvidenceController::class, 'store'])->name('bank-transaction-evidence.store');
    Route::post('vehicle-cost-allocation-bank-matching-handoffs/{handoffPublicId}/execute', [VehicleCostAllocationBankMatchingExecutionController::class, 'execute'])->name('vehicle-cost-allocation-bank-matching-handoffs.execute');
});
