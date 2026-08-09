<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReportPerformancePolicy;
use InvalidArgumentException;

final class DailyReportPerformancePolicyService
{
    /** @var array<string, string|null> */
    public const SYSTEM_DEFAULTS = [
        'redirected_max_percent' => '15.00',
        'kilometre_deviation_max_percent' => '10.00',
        'delivered_address_min_percent' => null,
        'rejected_max_percent' => null,
        'not_delivered_max_percent' => null,
    ];

    /** @var array<string, array{comparison: string, label: string}> */
    public const METRIC_DEFINITIONS = [
        'redirected_max_percent' => [
            'comparison' => 'max',
            'label' => 'Výdejní místo',
        ],
        'kilometre_deviation_max_percent' => [
            'comparison' => 'max',
            'label' => 'Odchylka kilometrů',
        ],
        'delivered_address_min_percent' => [
            'comparison' => 'min',
            'label' => 'Doručeno na adresu',
        ],
        'rejected_max_percent' => [
            'comparison' => 'max',
            'label' => 'Odmítnuto zákazníkem',
        ],
        'not_delivered_max_percent' => [
            'comparison' => 'max',
            'label' => 'Nedoručeno',
        ],
    ];

    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly RouteNumberNormalizer $routeNumberNormalizer,
    ) {}

    /**
     * @return array{
     *     system_defaults: array<string, string|null>,
     *     organization_defaults: array<string, mixed>|null,
     *     effective_organization_defaults: array<string, string|null>,
     *     route_overrides: list<array<string, mixed>>,
     *     metric_definitions: array<string, array{comparison: string, label: string}>
     * }
     */
    public function configuration(): array
    {
        $organizationId =
            $this->organizationContext->requireId();

        $organizationPolicy =
            DailyReportPerformancePolicy::query()
                ->where(
                    'organization_id',
                    $organizationId,
                )
                ->where(
                    'scope_key',
                    DailyReportPerformancePolicy::ORGANIZATION_SCOPE,
                )
                ->first();

        $routePolicies =
            DailyReportPerformancePolicy::query()
                ->where(
                    'organization_id',
                    $organizationId,
                )
                ->where(
                    'scope_key',
                    '<>',
                    DailyReportPerformancePolicy::ORGANIZATION_SCOPE,
                )
                ->orderBy('route_number_normalized')
                ->get();

        return [
            'system_defaults' => self::SYSTEM_DEFAULTS,
            'organization_defaults' =>
                $organizationPolicy === null
                    ? null
                    : $this->serializePolicy(
                        $organizationPolicy,
                    ),
            'effective_organization_defaults' =>
                $this->effectiveOrganizationThresholds(
                    $organizationPolicy,
                ),
            'route_overrides' =>
                $routePolicies
                    ->map(
                        fn (
                            DailyReportPerformancePolicy $policy,
                        ): array => $this->serializePolicy(
                            $policy,
                        ),
                    )
                    ->values()
                    ->all(),
            'metric_definitions' =>
                self::METRIC_DEFINITIONS,
        ];
    }

    /**
     * @return array{
     *     route_number: string,
     *     route_number_normalized: string,
     *     thresholds: array<string, string|null>,
     *     sources: array<string, string>
     * }
     */
    public function effective(
        string $routeNumber,
    ): array {
        $organizationId =
            $this->organizationContext->requireId();

        $normalized =
            $this->routeNumberNormalizer->normalize(
                $routeNumber,
            );

        $organizationPolicy =
            DailyReportPerformancePolicy::query()
                ->where(
                    'organization_id',
                    $organizationId,
                )
                ->where(
                    'scope_key',
                    DailyReportPerformancePolicy::ORGANIZATION_SCOPE,
                )
                ->first();

        $routePolicy =
            DailyReportPerformancePolicy::query()
                ->where(
                    'organization_id',
                    $organizationId,
                )
                ->where(
                    'scope_key',
                    $this->routeScopeKey(
                        $normalized[
                            'route_number_normalized'
                        ],
                    ),
                )
                ->first();

        $organizationThresholds =
            $this->effectiveOrganizationThresholds(
                $organizationPolicy,
            );

        $thresholds = [];
        $sources = [];

        foreach (
            array_keys(self::METRIC_DEFINITIONS)
            as $field
        ) {
            $routeValue =
                $routePolicy?->getAttribute($field);

            if ($routeValue !== null) {
                $thresholds[$field] =
                    $this->formatStoredThreshold(
                        $routeValue,
                    );

                $sources[$field] = 'route';

                continue;
            }

            $thresholds[$field] =
                $organizationThresholds[$field];

            $sources[$field] =
                $organizationPolicy === null
                    ? 'system'
                    : 'organization';
        }

        return [
            'route_number' =>
                $normalized['route_number'],
            'route_number_normalized' =>
                $normalized[
                    'route_number_normalized'
                ],
            'thresholds' => $thresholds,
            'sources' => $sources,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function updateOrganizationDefaults(
        User $actor,
        array $input,
    ): array {
        $organizationId =
            $this->organizationContext->requireId();

        $thresholds =
            $this->normalizeThresholdInput($input);

        $policy =
            DailyReportPerformancePolicy::query()
                ->updateOrCreate(
                    [
                        'organization_id' =>
                            $organizationId,
                        'scope_key' =>
                            DailyReportPerformancePolicy::ORGANIZATION_SCOPE,
                    ],
                    array_merge(
                        [
                            'route_number' => null,
                            'route_number_normalized' =>
                                null,
                            'updated_by_user_id' =>
                                (int) $actor->getKey(),
                        ],
                        $thresholds,
                    ),
                );

        return $this->serializePolicy($policy);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     route_number: string,
     *     route_number_normalized: string,
     *     thresholds: array<string, string|null>,
     *     sources: array<string, string>
     * }
     */
    public function updateRouteOverride(
        User $actor,
        string $routeNumber,
        array $input,
    ): array {
        $organizationId =
            $this->organizationContext->requireId();

        $normalized =
            $this->routeNumberNormalizer->normalize(
                $routeNumber,
            );

        $thresholds =
            $this->normalizeThresholdInput($input);

        $hasOverride = false;

        foreach ($thresholds as $value) {
            if ($value !== null) {
                $hasOverride = true;

                break;
            }
        }

        $scopeKey = $this->routeScopeKey(
            $normalized['route_number_normalized'],
        );

        if (! $hasOverride) {
            DailyReportPerformancePolicy::query()
                ->where(
                    'organization_id',
                    $organizationId,
                )
                ->where('scope_key', $scopeKey)
                ->delete();

            return $this->effective(
                $normalized['route_number'],
            );
        }

        DailyReportPerformancePolicy::query()
            ->updateOrCreate(
                [
                    'organization_id' =>
                        $organizationId,
                    'scope_key' => $scopeKey,
                ],
                array_merge(
                    [
                        'route_number' =>
                            $normalized[
                                'route_number'
                            ],
                        'route_number_normalized' =>
                            $normalized[
                                'route_number_normalized'
                            ],
                        'updated_by_user_id' =>
                            (int) $actor->getKey(),
                    ],
                    $thresholds,
                ),
            );

        return $this->effective(
            $normalized['route_number'],
        );
    }

    /**
     * @return array{
     *     route_number: string,
     *     route_number_normalized: string,
     *     thresholds: array<string, string|null>,
     *     sources: array<string, string>
     * }
     */
    public function deleteRouteOverride(
        string $routeNumber,
    ): array {
        $organizationId =
            $this->organizationContext->requireId();

        $normalized =
            $this->routeNumberNormalizer->normalize(
                $routeNumber,
            );

        DailyReportPerformancePolicy::query()
            ->where(
                'organization_id',
                $organizationId,
            )
            ->where(
                'scope_key',
                $this->routeScopeKey(
                    $normalized[
                        'route_number_normalized'
                    ],
                ),
            )
            ->delete();

        return $this->effective(
            $normalized['route_number'],
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    private function normalizeThresholdInput(
        array $input,
    ): array {
        $normalized = [];

        foreach (
            array_keys(self::METRIC_DEFINITIONS)
            as $field
        ) {
            if (! array_key_exists($field, $input)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Performance policy field [%s] is missing.',
                        $field,
                    ),
                );
            }

            $value = $input[$field];

            if ($value === null || $value === '') {
                $normalized[$field] = null;

                continue;
            }

            if (! is_numeric($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Performance policy field [%s] must be numeric or null.',
                        $field,
                    ),
                );
            }

            $numeric = (float) $value;

            if ($numeric < 0 || $numeric > 100) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Performance policy field [%s] must be between 0 and 100.',
                        $field,
                    ),
                );
            }

            $normalized[$field] = number_format(
                $numeric,
                2,
                '.',
                '',
            );
        }

        return $normalized;
    }

    /**
     * @return array<string, string|null>
     */
    private function effectiveOrganizationThresholds(
        ?DailyReportPerformancePolicy $policy,
    ): array {
        if ($policy === null) {
            return self::SYSTEM_DEFAULTS;
        }

        $thresholds = [];

        foreach (
            array_keys(self::METRIC_DEFINITIONS)
            as $field
        ) {
            $thresholds[$field] =
                $this->formatStoredThreshold(
                    $policy->getAttribute($field),
                );
        }

        return $thresholds;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePolicy(
        DailyReportPerformancePolicy $policy,
    ): array {
        $thresholds = [];

        foreach (
            array_keys(self::METRIC_DEFINITIONS)
            as $field
        ) {
            $thresholds[$field] =
                $this->formatStoredThreshold(
                    $policy->getAttribute($field),
                );
        }

        return [
            'scope' =>
                $policy->getAttribute(
                    'scope_key',
                ) ===
                DailyReportPerformancePolicy::ORGANIZATION_SCOPE
                    ? 'organization'
                    : 'route',
            'route_number' =>
                $policy->getAttribute(
                    'route_number',
                ),
            'route_number_normalized' =>
                $policy->getAttribute(
                    'route_number_normalized',
                ),
            'thresholds' => $thresholds,
            'updated_by_user_id' =>
                $policy->getAttribute(
                    'updated_by_user_id',
                ),
            'updated_at' =>
                $policy->updated_at?->toAtomString(),
        ];
    }

    private function routeScopeKey(
        string $normalizedRouteNumber,
    ): string {
        return 'route:'.$normalizedRouteNumber;
    }

    private function formatStoredThreshold(
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        return number_format(
            (float) $value,
            2,
            '.',
            '',
        );
    }
}