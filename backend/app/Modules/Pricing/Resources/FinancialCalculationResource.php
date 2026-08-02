<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Resources;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

final class FinancialCalculationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof FinancialCalculation) {
            throw new LogicException(
                'FinancialCalculationResource requires a FinancialCalculation model.',
            );
        }

        $calculation = $this->resource;

        $priceListPublicId = null;

        if ($calculation->relationLoaded('priceList')) {
            $priceList = $calculation->getRelation('priceList');

            if ($priceList instanceof PriceList) {
                $priceListPublicId = (string) $priceList->getAttribute(
                    'public_id',
                );
            }
        }

        $priceListVersionNumber = null;

        if ($calculation->relationLoaded('priceListVersion')) {
            $priceListVersion =
                $calculation->getRelation('priceListVersion');

            if ($priceListVersion instanceof PriceListVersion) {
                $priceListVersionNumber =
                    (int) $priceListVersion->getAttribute(
                        'version_number',
                    );
            }
        }

        $dailyReportPublicId = null;

        if ($calculation->relationLoaded('dailyReport')) {
            $dailyReport = $calculation->getRelation('dailyReport');

            if ($dailyReport instanceof DailyReport) {
                $dailyReportPublicId =
                    (string) $dailyReport->getAttribute(
                        'public_id',
                    );
            }
        }

        $supersedesPublicId = null;

        if (
            $calculation->relationLoaded(
                'supersedesCalculation',
            )
        ) {
            $supersedes =
                $calculation->getRelation(
                    'supersedesCalculation',
                );

            if ($supersedes instanceof FinancialCalculation) {
                $supersedesPublicId =
                    (string) $supersedes->getAttribute(
                        'public_id',
                    );
            }
        }

        return [
            'public_id' => (string) $calculation->getAttribute(
                'public_id',
            ),
            'status' => (string) $calculation->getAttribute(
                'status',
            ),
            'currency' => (string) $calculation->getAttribute(
                'currency',
            ),
            'daily_report_public_id' => $dailyReportPublicId,
            'daily_report_version' => (int) $calculation->getAttribute(
                'daily_report_version',
            ),
            'price_list_public_id' => $priceListPublicId,
            'price_list_version' => $priceListVersionNumber,
            'calculation_version' => (int) $calculation->getAttribute(
                'calculation_version',
            ),
            'subtotal_amount' => (string) $calculation->getAttribute(
                'subtotal_amount',
            ),
            'total_amount' => (string) $calculation->getAttribute(
                'total_amount',
            ),
            'supersedes_public_id' => $supersedesPublicId,
            'calculated_at' => $this->formatDateTime(
                $calculation->getAttribute('calculated_at'),
            ),
            'approved_at' => $this->formatDateTime(
                $calculation->getAttribute('approved_at'),
            ),
            'closed_at' => $this->formatDateTime(
                $calculation->getAttribute('closed_at'),
            ),
            'created_at' => $this->formatDateTime(
                $calculation->getAttribute('created_at'),
            ),
            'updated_at' => $this->formatDateTime(
                $calculation->getAttribute('updated_at'),
            ),
            'lines' => FinancialCalculationLineResource::collection(
                $this->whenLoaded('lines'),
            ),
        ];
    }

    private function formatDateTime(
        mixed $value,
    ): ?string {
        return $value instanceof DateTimeInterface
            ? $value->format(DateTimeInterface::ATOM)
            : null;
    }
}
