<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportFormConfiguration;
use DomainException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class DailyReportEffectiveFormService
{
    public function __construct(
        private readonly DailyReportFormConfigurationResolver $resolver,
        private readonly DailyReportFormFieldDefinitionService $fieldDefinitions,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $baseAttributes
     * @return array<string, mixed>
     */
    public function prepareAttributesForCreate(
        int $organizationId,
        string $serviceDate,
        array $input,
        array $baseAttributes,
    ): array {
        $configuration = $this->resolver->resolve(
            $organizationId,
            $serviceDate,
        );

        if (! $configuration instanceof DailyReportFormConfiguration) {
            $ownerOrganizationId =
                $this->resolver->ownerOrganizationId(
                    $organizationId,
                    $serviceDate,
                );

            $ownerHasConfigurationHistory =
                DailyReportFormConfiguration::query()
                    ->where(
                        'organization_id',
                        $ownerOrganizationId,
                    )
                    ->exists();

            if ($ownerHasConfigurationHistory) {
                throw ValidationException::withMessages([
                    'service_date' => [
                        'Pro zadané datum není platná konfigurace denního výkazu.',
                    ],
                ]);
            }

            $customValues =
                $input['custom_field_values']
                    ?? [];

            if (
                is_array($customValues)
                && $customValues !== []
            ) {
                throw ValidationException::withMessages([
                    'custom_field_values' => [
                        'Vlastní položky nelze uložit bez platné konfigurace denního výkazu.',
                    ],
                ]);
            }

            $baseAttributes[
                'daily_report_form_configuration_id'
            ] = null;

            $baseAttributes['custom_field_values'] = [];

            return $baseAttributes;
        }

        $rawFields = $configuration->getAttribute(
            'fields',
        );

        if (! is_array($rawFields)) {
            throw new DomainException(
                'Daily-report form configuration fields are invalid.',
            );
        }

        $fields = $this->fieldDefinitions->normalize(
            $rawFields,
        );

        $customInput =
            $input['custom_field_values']
                ?? [];

        if (! is_array($customInput)) {
            throw ValidationException::withMessages([
                'custom_field_values' => [
                    'Vlastní hodnoty denního výkazu musí být objekt.',
                ],
            ]);
        }

        $knownCustomKeys = [];

        foreach ($fields as $field) {
            if ($field['custom']) {
                $knownCustomKeys[] = $field['key'];
            }
        }

        foreach (array_keys($customInput) as $key) {
            if (
                ! is_string($key)
                || ! in_array(
                    $key,
                    $knownCustomKeys,
                    true,
                )
            ) {
                throw ValidationException::withMessages([
                    'custom_field_values' => [
                        'Denní výkaz obsahuje neznámou vlastní položku.',
                    ],
                ]);
            }
        }

        $normalizedCustomValues = [];

        foreach ($fields as $field) {
            $key = $field['key'];

            if ($field['custom']) {
                $provided = array_key_exists(
                    $key,
                    $customInput,
                );

                $value = $provided
                    ? $customInput[$key]
                    : null;

                if (
                    ! $field['visible']
                    && $provided
                    && $this->hasValue($value)
                ) {
                    $this->hiddenFieldError(
                        $field['label'],
                    );
                }

                if (
                    $field['required']
                    && ! $this->hasValue($value)
                ) {
                    $this->requiredFieldError(
                        $field['label'],
                    );
                }

                if (
                    $field['visible']
                    && $provided
                    && $this->hasValue($value)
                ) {
                    $normalizedCustomValues[$key] =
                        $this->normalizeCustomValue(
                            $field['type'],
                            $value,
                            $field['label'],
                        );
                }

                continue;
            }

            $provided = array_key_exists(
                $key,
                $input,
            );

            $value = $provided
                ? $input[$key]
                : null;

            if (
                ! $field['visible']
                && $provided
                && $this->hasValue($value)
            ) {
                $this->hiddenFieldError(
                    $field['label'],
                );
            }

            if (
                $field['required']
                && ! $this->hasValue($value)
            ) {
                $this->requiredFieldError(
                    $field['label'],
                );
            }

            if (
                ! $field['visible']
                && array_key_exists(
                    $key,
                    $baseAttributes,
                )
            ) {
                unset($baseAttributes[$key]);

                if ($key === 'actual_km') {
                    unset(
                        $baseAttributes[
                            'actual_km_source'
                        ],
                    );
                }
            }
        }

        $baseAttributes[
            'daily_report_form_configuration_id'
        ] = (int) $configuration->getKey();

        $baseAttributes['custom_field_values'] =
            $normalizedCustomValues;

        return $baseAttributes;
    }

    public function hasBoundConfiguration(
        DailyReport $dailyReport,
    ): bool {
        return $dailyReport->getAttribute(
            'daily_report_form_configuration_id',
        ) !== null;
    }

    public function assertCompleteForSubmission(
        DailyReport $dailyReport,
    ): void {
        $configurationId = $dailyReport->getAttribute(
            'daily_report_form_configuration_id',
        );

        if (
            ! is_int($configurationId)
            && ! (
                is_string($configurationId)
                && ctype_digit($configurationId)
            )
        ) {
            throw new DomainException(
                'Daily report has an invalid form configuration binding.',
            );
        }

        $configuration =
            DailyReportFormConfiguration::query()
                ->find((int) $configurationId);

        if (! $configuration instanceof DailyReportFormConfiguration) {
            throw new DomainException(
                'The bound daily-report form configuration does not exist.',
            );
        }

        $rawFields = $configuration->getAttribute(
            'fields',
        );

        if (! is_array($rawFields)) {
            throw new DomainException(
                'The bound daily-report form configuration is invalid.',
            );
        }

        $fields = $this->fieldDefinitions->normalize(
            $rawFields,
        );

        $customValues = $dailyReport->getAttribute(
            'custom_field_values',
        );

        if ($customValues === null) {
            $customValues = [];
        }

        if (! is_array($customValues)) {
            throw new DomainException(
                'Daily report custom values are invalid.',
            );
        }

        $missingLabels = [];

        foreach ($fields as $field) {
            if (! $field['required']) {
                continue;
            }

            $value = $field['custom']
                ? ($customValues[$field['key']] ?? null)
                : $dailyReport->getAttribute(
                    $field['key'],
                );

            if (! $this->hasValue($value)) {
                $missingLabels[] = $field['label'];
            }
        }

        if ($missingLabels !== []) {
            throw new DomainException(
                sprintf(
                    'Daily report cannot be submitted because configured mandatory values are missing: %s.',
                    implode(', ', $missingLabels),
                ),
            );
        }
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }

    private function requiredFieldError(
        string $label,
    ): never {
        throw ValidationException::withMessages([
            'daily_report_form' => [
                sprintf(
                    'Položka "%s" je pro zadané datum povinná.',
                    $label,
                ),
            ],
        ]);
    }

    private function hiddenFieldError(
        string $label,
    ): never {
        throw ValidationException::withMessages([
            'daily_report_form' => [
                sprintf(
                    'Položka "%s" není pro zadané datum aktivní.',
                    $label,
                ),
            ],
        ]);
    }

    private function normalizeCustomValue(
        string $type,
        mixed $value,
        string $label,
    ): mixed {
        return match ($type) {
            'text' => $this->normalizeCustomText(
                $value,
                $label,
            ),
            'number' => $this->normalizeCustomNumber(
                $value,
                $label,
            ),
            'time' => $this->normalizeCustomTime(
                $value,
                $label,
            ),
            'money' => $this->normalizeCustomMoney(
                $value,
                $label,
            ),
            'boolean' => $this->normalizeCustomBoolean(
                $value,
                $label,
            ),
            default => throw new InvalidArgumentException(
                sprintf(
                    'Unsupported custom field type "%s".',
                    $type,
                ),
            ),
        };
    }

    private function normalizeCustomText(
        mixed $value,
        string $label,
    ): string {
        if (! is_string($value)) {
            $this->invalidCustomValue($label);
        }

        $normalized = trim($value);

        if (
            $normalized === ''
            || mb_strlen($normalized, 'UTF-8') > 5000
        ) {
            $this->invalidCustomValue($label);
        }

        return $normalized;
    }

    private function normalizeCustomNumber(
        mixed $value,
        string $label,
    ): string {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            $this->invalidCustomValue($label);
        }

        $normalized = str_replace(
            ',',
            '.',
            trim((string) $value),
        );

        if (
            preg_match(
                '/^-?\d+(?:\.\d+)?$/',
                $normalized,
            ) !== 1
        ) {
            $this->invalidCustomValue($label);
        }

        return $normalized;
    }

    private function normalizeCustomTime(
        mixed $value,
        string $label,
    ): string {
        if (! is_string($value)) {
            $this->invalidCustomValue($label);
        }

        $normalized = trim($value);

        if (
            preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $normalized,
            ) !== 1
        ) {
            $this->invalidCustomValue($label);
        }

        return $normalized;
    }

    private function normalizeCustomMoney(
        mixed $value,
        string $label,
    ): string {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            $this->invalidCustomValue($label);
        }

        $normalized = str_replace(
            ',',
            '.',
            trim((string) $value),
        );

        if (
            preg_match(
                '/^-?\d+(?:\.\d{1,2})?$/',
                $normalized,
            ) !== 1
        ) {
            $this->invalidCustomValue($label);
        }

        $negative = str_starts_with(
            $normalized,
            '-',
        );

        $unsigned = $negative
            ? substr($normalized, 1)
            : $normalized;

        [$whole, $fraction] = array_pad(
            explode('.', $unsigned, 2),
            2,
            '',
        );

        $whole = ltrim($whole, '0');

        if ($whole === '') {
            $whole = '0';
        }

        $fraction = str_pad(
            $fraction,
            2,
            '0',
        );

        return ($negative ? '-' : '')
            .$whole
            .'.'
            .$fraction;
    }

    private function normalizeCustomBoolean(
        mixed $value,
        string $label,
    ): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (
            $value === 1
            || $value === '1'
            || $value === 'true'
        ) {
            return true;
        }

        if (
            $value === 0
            || $value === '0'
            || $value === 'false'
        ) {
            return false;
        }

        $this->invalidCustomValue($label);
    }

    private function invalidCustomValue(
        string $label,
    ): never {
        throw ValidationException::withMessages([
            'custom_field_values' => [
                sprintf(
                    'Položka "%s" obsahuje neplatnou hodnotu.',
                    $label,
                ),
            ],
        ]);
    }
}
