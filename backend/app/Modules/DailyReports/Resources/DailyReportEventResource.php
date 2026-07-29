<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Resources;

use App\Modules\DailyReports\Models\DailyReportEvent;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonException;
use LogicException;

final class DailyReportEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DailyReportEvent) {
            throw new LogicException(
                'DailyReportEventResource requires a DailyReportEvent model.',
            );
        }

        $event = $this->resource;

        return [
            'event_type' => $this->requiredString(
                $event->getAttribute('event_type'),
                'Daily report event type is unavailable.',
            ),
            'from_status' => $this->nullableString(
                $event->getAttribute('from_status'),
                'Daily report event source status is invalid.',
            ),
            'to_status' => $this->nullableString(
                $event->getAttribute('to_status'),
                'Daily report event target status is invalid.',
            ),
            'acted_by_user_id' => $this->positiveInteger(
                $event->getAttribute('acted_by_user_id'),
                'Daily report event actor is unavailable.',
            ),
            'reason' => $this->nullableString(
                $event->getAttribute('reason'),
                'Daily report event reason is invalid.',
            ),
            'affected_fields' => $this->stringList(
                $event->getAttribute('affected_fields'),
                'Daily report event affected fields are unavailable.',
            ),
            'metadata' => $this->sanitizeMetadata(
                $this->arrayValue(
                    $event->getAttribute('metadata'),
                    'Daily report event metadata is unavailable.',
                ),
            ),
            'created_at' => $this->dateTime(
                $event->getAttribute('created_at'),
            ),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $metadata
     * @return array<array-key, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        unset(
            $metadata['daily_report_id'],
            $metadata['organization_id'],
        );

        return $metadata;
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

    private function requiredString(
        mixed $value,
        string $message,
    ): string {
        if (! is_string($value) || $value === '') {
            throw new LogicException($message);
        }

        return $value;
    }

    private function nullableString(
        mixed $value,
        string $message,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException($message);
        }

        return $value;
    }

    private function dateTime(mixed $value): string
    {
        if (! $value instanceof DateTimeInterface) {
            throw new LogicException(
                'Daily report event timestamp is unavailable.',
            );
        }

        return $value->format(DATE_ATOM);
    }
}
