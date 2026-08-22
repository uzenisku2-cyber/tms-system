<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Core\Http\BaseController;
use App\Models\User;
use App\Modules\DailyReports\Exceptions\DepotWorkbookException;
use App\Modules\DailyReports\Requests\CancelDepotImportRequest;
use App\Modules\DailyReports\Requests\CreateDepotImportDraftRequest;
use App\Modules\DailyReports\Requests\FinalizeDepotImportRequest;
use App\Modules\DailyReports\Requests\MapDepotSourceDriverRequest;
use App\Modules\DailyReports\Services\DepotImportDraftService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class DepotImportDraftController extends BaseController
{
    public function index(
        DepotImportDraftService $drafts,
    ): JsonResponse {
        return $this->success(
            $drafts->summaries(),
        );
    }

    public function store(
        CreateDepotImportDraftRequest $request,
        DepotImportDraftService $drafts,
    ): JsonResponse {
        $workbook = $this->workbook($request->file('workbook'));

        try {
            $batch = $drafts->create(
                actor: $this->actor($request),
                workbookPath: $workbook->getPathname(),
                originalFilename: $workbook->getClientOriginalName(),
                confirmedAlias: $request->string(
                    'carrier_alias',
                )->toString(),
            );
        } catch (DepotWorkbookException $exception) {
            throw ValidationException::withMessages([
                'workbook' => [$exception->getMessage()],
            ]);
        }

        return $this->success(
            $drafts->payload($batch),
            'Koncept importní dávky byl vytvořen.',
            201,
        );
    }

    public function show(
        string $batch,
        DepotImportDraftService $drafts,
    ): JsonResponse {
        return $this->success(
            $drafts->payload(
                $drafts->find($batch),
            ),
        );
    }

    public function mapSourceDriver(
        MapDepotSourceDriverRequest $request,
        string $batch,
        DepotImportDraftService $drafts,
    ): JsonResponse {
        return $this->success(
            $drafts->payload(
                $drafts->mapSourceDriver(
                    $this->actor($request),
                    $batch,
                    $request->validated(),
                ),
            ),
            'Zdrojové jméno bylo přiřazeno oprávněnému řidiči.',
        );
    }

    public function finalize(
        FinalizeDepotImportRequest $request,
        string $batch,
        DepotImportDraftService $drafts,
    ): JsonResponse {
        return $this->success(
            $drafts->payload(
                $drafts->finalize(
                    $this->actor($request),
                    $batch,
                    $request->validated(),
                ),
            ),
            'Zápis depa byl importován přesně v uložené podobě.',
        );
    }

    public function cancel(
        CancelDepotImportRequest $request,
        string $batch,
        DepotImportDraftService $drafts,
    ): JsonResponse {
        return $this->success(
            $drafts->payload(
                $drafts->cancel(
                    $this->actor($request),
                    $batch,
                    $request->validated(),
                ),
            ),
            'Import zápisu depa byl auditně stornován; zdrojové hodnoty zůstaly zachovány.',
        );
    }

    private function workbook(mixed $value): UploadedFile
    {
        if (! $value instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'workbook' => ['Vyberte zdrojový sešit XLSX.'],
            ]);
        }

        return $value;
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $actor;
    }
}
