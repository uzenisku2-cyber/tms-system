<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Core\Http\BaseController;
use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Exceptions\DepotWorkbookException;
use App\Modules\DailyReports\Requests\InspectDepotImportRequest;
use App\Modules\DailyReports\Requests\PreviewDepotImportRequest;
use App\Modules\DailyReports\Services\DepotImportPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class DepotImportPreviewController extends BaseController
{
    public function inspect(
        InspectDepotImportRequest $request,
        OrganizationContext $context,
        DepotImportPreviewService $previews,
    ): JsonResponse {
        $workbook = $this->workbook($request->file('workbook'));

        try {
            $payload = $previews->inspect(
                $workbook->getPathname(),
                $context->requireId(),
            );
        } catch (DepotWorkbookException $exception) {
            throw ValidationException::withMessages([
                'workbook' => [$exception->getMessage()],
            ]);
        }

        $payload['source']['original_filename'] =
            $workbook->getClientOriginalName();

        return $this->success($payload);
    }

    public function preview(
        PreviewDepotImportRequest $request,
        OrganizationContext $context,
        DepotImportPreviewService $previews,
    ): JsonResponse {
        $workbook = $this->workbook($request->file('workbook'));

        try {
            $payload = $previews->preview(
                $workbook->getPathname(),
                $context->requireId(),
                $request->string('carrier_alias')->toString(),
            );
        } catch (DepotWorkbookException $exception) {
            throw ValidationException::withMessages([
                'workbook' => [$exception->getMessage()],
            ]);
        }

        $payload['source']['original_filename'] =
            $workbook->getClientOriginalName();

        return $this->success($payload);
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
}
