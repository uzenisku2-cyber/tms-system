<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReportFormConfiguration;
use App\Modules\DailyReports\Services\DailyReportFormConfigurationResolver;
use App\Modules\DailyReports\Services\DailyReportFormFieldDefinitionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DailyReportFormConfigurationController
{
    public function index(
        OrganizationContext $context,
        DailyReportFormConfigurationResolver $resolver,
    ): JsonResponse {
        $organizationId = $context->requireId();

        $this->assertManagementOwner(
            $organizationId,
            CarbonImmutable::today()->toDateString(),
            $resolver,
        );

        $items = DailyReportFormConfiguration::query()
            ->where(
                'organization_id',
                $organizationId,
            )
            ->orderByDesc('version')
            ->get()
            ->map(
                fn (
                    DailyReportFormConfiguration $configuration,
                ): array => $this->resource(
                    $configuration,
                ),
            )
            ->values();

        return response()->json([
            'data' => [
                'organization_id' => $organizationId,
                'items' => $items,
            ],
        ]);
    }

    public function effective(
        Request $request,
        OrganizationContext $context,
        DailyReportFormConfigurationResolver $resolver,
    ): JsonResponse {
        $validated = $request->validate([
            'service_date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $serviceDate = (string) $validated['service_date'];
        $organizationId = $context->requireId();

        $ownerOrganizationId = $resolver->ownerOrganizationId(
            $organizationId,
            $serviceDate,
        );

        $configuration = $resolver->resolve(
            $organizationId,
            $serviceDate,
        );

        return response()->json([
            'data' => [
                'organization_id' => $organizationId,
                'owner_organization_id' => $ownerOrganizationId,
                'configuration' => $configuration === null
                    ? null
                    : $this->resource($configuration),
            ],
        ]);
    }

    public function store(
        Request $request,
        OrganizationContext $context,
        DailyReportFormConfigurationResolver $resolver,
        DailyReportFormFieldDefinitionService $fieldDefinitions,
    ): JsonResponse {
        $validated = $request->validate([
            'valid_from' => [
                'required',
                'date_format:Y-m-d',
            ],
            'valid_until' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:valid_from',
            ],
            'fields' => [
                'required',
                'array',
                'min:12',
                'max:40',
            ],
            'fields.*.key' => [
                'required',
                'string',
                'max:64',
            ],
            'fields.*.label' => [
                'nullable',
                'string',
                'max:100',
            ],
            'fields.*.type' => [
                'nullable',
                'string',
                'in:date,text,number,time,money,boolean',
            ],
            'fields.*.order' => [
                'required',
                'integer',
                'between:1,40',
            ],
            'fields.*.visible' => [
                'required',
                'boolean',
            ],
            'fields.*.required' => [
                'required',
                'boolean',
            ],
        ]);

        $organizationId = $context->requireId();
        $validFrom = (string) $validated['valid_from'];
        $validUntil = $this->nullableDate(
            $validated['valid_until'] ?? null,
        );

        $this->assertManagementOwner(
            $organizationId,
            $validFrom,
            $resolver,
        );

        $fields = $fieldDefinitions->normalize(
            $validated['fields'],
        );

        $actor = $this->actor($request);

        $configuration = DB::transaction(
            function () use (
                $organizationId,
                $validFrom,
                $validUntil,
                $fields,
                $actor,
            ): DailyReportFormConfiguration {
                $existing = DailyReportFormConfiguration::query()
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->lockForUpdate()
                    ->get();

                $open = $existing->first(
                    static fn (
                        DailyReportFormConfiguration $item,
                    ): bool => $item->getAttribute('valid_until') === null,
                );

                if ($open !== null) {
                    $openFrom = $open->valid_from->toDateString();

                    if ($openFrom >= $validFrom) {
                        throw ValidationException::withMessages([
                            'valid_from' => [
                                'Nová verze musí začínat po začátku současné neomezené verze.',
                            ],
                        ]);
                    }

                    $open->forceFill([
                        'valid_until' => CarbonImmutable::parse(
                            $validFrom,
                        )->subDay()->toDateString(),
                        'ended_by_user_id' => (int) $actor->getKey(),
                    ]);

                    $open->save();
                }

                $overlapEnd = $validUntil ?? '9999-12-31';

                $overlapExists = DailyReportFormConfiguration::query()
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->where(
                        'valid_from',
                        '<=',
                        $overlapEnd,
                    )
                    ->where(
                        static function (Builder $query) use (
                            $validFrom,
                        ): void {
                            $query
                                ->whereNull('valid_until')
                                ->orWhere(
                                    'valid_until',
                                    '>=',
                                    $validFrom,
                                );
                        },
                    )
                    ->exists();

                if ($overlapExists) {
                    throw ValidationException::withMessages([
                        'valid_from' => [
                            'Platnost nové verze se překrývá s existující verzí.',
                        ],
                    ]);
                }

                $nextVersion = (
                    (int) DailyReportFormConfiguration::query()
                        ->where(
                            'organization_id',
                            $organizationId,
                        )
                        ->max('version')
                ) + 1;

                return DailyReportFormConfiguration::query()->create([
                    'organization_id' => $organizationId,
                    'version' => $nextVersion,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'fields' => $fields,
                    'created_by_user_id' => (int) $actor->getKey(),
                    'ended_by_user_id' => null,
                ]);
            },
        );

        return response()->json([
            'message' => 'Nová verze nastavení denního výkazu byla uložena.',
            'data' => $this->resource(
                $configuration,
            ),
        ], 201);
    }

    public function end(
        Request $request,
        OrganizationContext $context,
        DailyReportFormConfigurationResolver $resolver,
        int $configuration,
    ): JsonResponse {
        $validated = $request->validate([
            'valid_until' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $organizationId = $context->requireId();
        $validUntil = (string) $validated['valid_until'];

        $this->assertManagementOwner(
            $organizationId,
            $validUntil,
            $resolver,
        );

        $target = DailyReportFormConfiguration::query()
            ->whereKey($configuration)
            ->where(
                'organization_id',
                $organizationId,
            )
            ->firstOrFail();

        if ($target->getAttribute('valid_until') !== null) {
            throw ValidationException::withMessages([
                'valid_until' => [
                    'Tato verze už má ukončenou platnost.',
                ],
            ]);
        }

        if (
            $validUntil
            < $target->valid_from->toDateString()
        ) {
            throw ValidationException::withMessages([
                'valid_until' => [
                    'Datum ukončení nesmí být před datem začátku.',
                ],
            ]);
        }

        $actor = $this->actor($request);

        $target->forceFill([
            'valid_until' => $validUntil,
            'ended_by_user_id' => (int) $actor->getKey(),
        ]);

        $target->save();

        return response()->json([
            'message' => 'Platnost verze byla ukončena.',
            'data' => $this->resource(
                $target->refresh(),
            ),
        ]);
    }

    private function assertManagementOwner(
        int $organizationId,
        string $effectiveDate,
        DailyReportFormConfigurationResolver $resolver,
    ): void {
        $ownerOrganizationId = $resolver->ownerOrganizationId(
            $organizationId,
            $effectiveDate,
        );

        if ($ownerOrganizationId !== $organizationId) {
            abort(
                403,
                'Nastavení denního výkazu spravuje nejvýše nadřazená organizace.',
            );
        }
    }

    private function actor(
        Request $request,
    ): User {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        return $actor;
    }

    private function nullableDate(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(
        DailyReportFormConfiguration $configuration,
    ): array {
        $today = CarbonImmutable::today()
            ->toDateString();

        $validFrom = $configuration->valid_from
            ->toDateString();

        $validUntil = $configuration->valid_until?->toDateString();

        $status = 'active';

        if ($validFrom > $today) {
            $status = 'scheduled';
        } elseif (
            $validUntil !== null
            && $validUntil < $today
        ) {
            $status = 'ended';
        }

        $fields = $configuration->getAttribute('fields');

        if (! is_array($fields)) {
            $fields = [];
        }

        usort(
            $fields,
            static fn (array $left, array $right): int => ((int) ($left['order'] ?? 0))
                <=>
                ((int) ($right['order'] ?? 0)),
        );

        return [
            'id' => (int) $configuration->getKey(),
            'organization_id' => (int) $configuration->getAttribute('organization_id'),
            'version' => (int) $configuration->getAttribute('version'),
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'unlimited' => $validUntil === null,
            'status' => $status,
            'fields' => $fields,
        ];
    }
}
