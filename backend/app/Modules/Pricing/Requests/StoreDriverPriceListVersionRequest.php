<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Requests;

use App\Modules\Pricing\Models\DriverPriceListItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDriverPriceListVersionRequest extends FormRequest
{
    use InteractsWithConditionalPriceListRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(
            $this->normalizedPayload(),
        );
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'expected_current_version' => [
                'required',
                'integer',
                'min:1',
            ],
            'valid_from' => [
                'nullable',
                'required_with:valid_until',
                'date_format:Y-m-d',
            ],
            'valid_until' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:valid_from',
            ],
            'change_reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'items' => [
                'required',
                'array',
                'size:'.count(DriverPriceListItem::CODES),
            ],
            'items.*' => [
                'required',
                'array:code,description,unit_rate',
            ],
            'items.*.code' => [
                'required',
                'string',
                Rule::in(DriverPriceListItem::CODES),
                'distinct:strict',
            ],
            'items.*.description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.unit_rate' => [
                'required',
                'numeric',
                'decimal:0,4',
                'between:0,9999999999.9999',
            ],
        ] + $this->conditionalPriceListRuleRules();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedPayload(): array
    {
        $normalized = [];

        $reason = $this->input('change_reason');

        if (is_string($reason)) {
            $reason = trim($reason);
            $normalized['change_reason'] =
                $reason === '' ? null : $reason;
        }

        $items = $this->input('items');

        if (is_array($items)) {
            $normalized['items'] = array_map(
                static function (mixed $item): mixed {
                    if (! is_array($item)) {
                        return $item;
                    }

                    $copy = $item;

                    if (is_string($copy['code'] ?? null)) {
                        $copy['code'] = trim($copy['code']);
                    }

                    if (is_string($copy['description'] ?? null)) {
                        $description = trim($copy['description']);
                        $copy['description'] =
                            $description === '' ? null : $description;
                    }

                    if (is_string($copy['unit_rate'] ?? null)) {
                        $copy['unit_rate'] = trim($copy['unit_rate']);
                    }

                    return $copy;
                },
                $items,
            );
        }

        $conditionalRules = $this->input(
            'conditional_rules',
        );

        if (is_array($conditionalRules)) {
            $normalized['conditional_rules'] =
                $this->normalizeConditionalPriceListRules(
                    $conditionalRules,
                );
        }

        return $normalized;
    }
}
