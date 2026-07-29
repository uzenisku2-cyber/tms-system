<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Resources;

use App\Modules\DailyReports\Models\DailyReportVersion;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonException;
use LogicException;

final class DailyReportVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DailyReportVersion) {
            throw new LogicException(
                'DailyReportVersionResource requires a DailyReportVersion model.',
            );
        }

        $version = $this->resource;

        return [
            'version_number' => $this->positiveInteger(
                $version->getAttribute('version_number'),
                'Daily report version number is unavailable.',
            ),
            'snapshot' => $this->sanitizeSnapshot(
                $this->arrayValue(
                    $version->getAttribute('snapshot'),
                    'Daily report version snapshot is unavailable.',
                ),
            ),
            'changed_fields' => $this->stringList(
                $version->getAttribute('changed_fields'),
                'Daily report changed fields are unavailable.',
            ),
            'created_by_user_id' => $this->positiveInteger(
                $version->getAttribute('created_by_user_id'),
                'Daily report version actor is unavailable.',
            ),
            'change_reason' => $this->nullableString(
                $version->getAttribute('change_reason'),
            ),
            'created_at' => $this->dateTime(
                $version->getAttribute('created_at'),
            ),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $snapshot
     * @return array<array-key, mixed>
     */
    private function sanitizeSnapshot(array $snapshot): array
    {
        unset(
            $snapshot['id'],
            $snapshot['daily_report_id'],
            $snapshot['organization_id'],
            $snapshot['route_number_normalized'],
        );

        return $snapshot;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function arrayValue(
        mixed $value,
        string $message,
    ): array {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            try {
                $decoded = json_decode(
                    $value,
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException) {
                throw new LogicException($message);
            }

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new LogicException($message);
    }

    /**
     * @return list<string>
     */
    private function stringList(
        mixed $value,
        string $message,
    ): array {
        $values = $this->arrayValue(
            $value,
            $message,
        );

        $strings = [];

        foreach ($values as $item) {
            if (! is_string($item)) {
                throw new LogicException($message);
            }

            $strings[] = $item;
        }

        return $strings;
    }

    private function positiveInteger(
        mixed $value,
        string $message,
    ): int {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (
            is_string($value)
            && preg_match('/^[1-9][0-9]*$/', $value) === 1
        ) {
            return (int) $value;
        }

        throw new LogicException($message);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException(
                'Daily report version reason is invalid.',
            );
        }

        return $value;
    }

    private function dateTime(mixed $value): string
    {
        if (! $value instanceof DateTimeInterface) {
            throw new LogicException(
                'Daily report version timestamp is unavailable.',
            );
        }

        return $value->format(DATE_ATOM);
    }
}
