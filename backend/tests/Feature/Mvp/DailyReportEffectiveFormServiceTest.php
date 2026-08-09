<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportFormConfiguration;
use App\Modules\DailyReports\Services\DailyReportEffectiveFormService;
use App\Modules\Organizations\Models\Organization;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class DailyReportEffectiveFormServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_effective_configuration_is_bound_and_custom_value_is_normalized(): void
    {
        [$organization, $user] = $this->ownerFixture();

        $fields = $this->canonicalFields();

        $fields[] = [
            'key' => 'custom_0123456789abcdef0123456789abcdef',
            'label' => 'Svozová kontrola',
            'type' => 'money',
            'order' => 13,
            'visible' => true,
            'required' => true,
            'system' => false,
            'custom' => true,
        ];

        $configuration = $this->configuration(
            $organization,
            $user,
            '2025-06-01',
            $fields,
        );

        $input = $this->completeInput();
        $input['custom_field_values'] = [
            'custom_0123456789abcdef0123456789abcdef' => '15,5',
        ];

        $attributes = app(
            DailyReportEffectiveFormService::class,
        )->prepareAttributesForCreate(
            (int) $organization->getKey(),
            '2025-06-15',
            $input,
            $this->baseAttributes($input),
        );

        self::assertSame(
            (int) $configuration->getKey(),
            $attributes[
                'daily_report_form_configuration_id'
            ],
        );

        self::assertSame(
            [
                'custom_0123456789abcdef0123456789abcdef' => '15.50',
            ],
            $attributes['custom_field_values'],
        );
    }

    public function test_date_without_effective_configuration_is_rejected_after_configuration_history_exists(): void
    {
        [$organization, $user] = $this->ownerFixture();

        $this->configuration(
            $organization,
            $user,
            '2025-06-01',
            $this->canonicalFields(),
        );

        $input = $this->completeInput();
        $input['service_date'] = '2025-05-31';

        $this->expectException(
            ValidationException::class,
        );

        app(
            DailyReportEffectiveFormService::class,
        )->prepareAttributesForCreate(
            (int) $organization->getKey(),
            '2025-05-31',
            $input,
            $this->baseAttributes($input),
        );
    }

    public function test_bound_configuration_replaces_legacy_completion_timestamp_requirement(): void
    {
        [$organization, $user] = $this->ownerFixture();

        $configuration = $this->configuration(
            $organization,
            $user,
            '2025-06-01',
            $this->canonicalFields(),
        );

        $report = new DailyReport;

        $report->forceFill([
            'daily_report_form_configuration_id' => (int) $configuration->getKey(),
            'custom_field_values' => [],
            'service_date' => '2025-06-15',
            'route_number' => 'R-100',
            'departure_time' => '08:00',
            'arrival_time' => '16:00',
            'actual_km' => '105.00',
            'planned_km' => '100.00',
            'loaded_parcels' => 120,
            'delivered_parcels' => 90,
            'redirected_parcels' => 20,
            'undelivered_parcels' => 10,
            'completion_confirmed_at' => null,
        ]);

        app(
            DailyReportEffectiveFormService::class,
        )->assertCompleteForSubmission($report);

        self::assertNull(
            $report->getAttribute(
                'completion_confirmed_at',
            ),
        );
    }

    public function test_bound_configuration_reports_missing_required_label(): void
    {
        [$organization, $user] = $this->ownerFixture();

        $configuration = $this->configuration(
            $organization,
            $user,
            '2025-06-01',
            $this->canonicalFields(),
        );

        $report = new DailyReport;

        $report->forceFill([
            'daily_report_form_configuration_id' => (int) $configuration->getKey(),
            'custom_field_values' => [],
            'service_date' => '2025-06-15',
            'route_number' => 'R-101',
            'departure_time' => '08:00',
            'arrival_time' => null,
            'actual_km' => '105.00',
            'planned_km' => '100.00',
            'loaded_parcels' => 120,
            'delivered_parcels' => 90,
            'redirected_parcels' => 20,
            'undelivered_parcels' => 10,
        ]);

        try {
            app(
                DailyReportEffectiveFormService::class,
            )->assertCompleteForSubmission($report);

            self::fail(
                'Missing configured required value was accepted.',
            );
        } catch (DomainException $exception) {
            self::assertStringContainsString(
                'Čas příjezdu',
                $exception->getMessage(),
            );
        }
    }

    /**
     * @return array{0:Organization,1:User}
     */
    private function ownerFixture(): array
    {
        $organization = Organization::query()->create([
            'name' => 'Effective Form Master',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        $user = User::factory()->create();

        return [$organization, $user];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    private function configuration(
        Organization $organization,
        User $user,
        string $validFrom,
        array $fields,
    ): DailyReportFormConfiguration {
        return DailyReportFormConfiguration::query()
            ->create([
                'organization_id' => (int) $organization->getKey(),
                'version' => 1,
                'valid_from' => $validFrom,
                'valid_until' => null,
                'fields' => $fields,
                'created_by_user_id' => (int) $user->getKey(),
                'ended_by_user_id' => null,
            ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function canonicalFields(): array
    {
        $definitions = [
            ['service_date', 'Datum', 'date'],
            ['route_number', 'Trasa č.', 'text'],
            ['departure_time', 'Čas odjezdu', 'time'],
            ['arrival_time', 'Čas příjezdu', 'time'],
            ['actual_km', 'Trasa naměřená', 'number'],
            ['planned_km', 'Trasa plánovaná', 'number'],
            ['loaded_parcels', 'Naloženo ks', 'number'],
            ['delivered_parcels', 'Doručeno na adresu', 'number'],
            ['redirected_parcels', 'Doručeno na výdejní místo', 'number'],
            ['undelivered_parcels', 'Odmítnuté ks', 'number'],
            ['surcharge_amount', 'Příplatek', 'money'],
            ['operational_notes', 'Poznámka', 'text'],
        ];

        return array_map(
            static function (
                array $definition,
                int $index,
            ): array {
                [$key, $label, $type] = $definition;

                return [
                    'key' => $key,
                    'label' => $label,
                    'type' => $type,
                    'order' => $index + 1,
                    'visible' => true,
                    'required' => $index < 10,
                    'system' => in_array(
                        $key,
                        [
                            'service_date',
                            'route_number',
                        ],
                        true,
                    ),
                    'custom' => false,
                ];
            },
            $definitions,
            array_keys($definitions),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function completeInput(): array
    {
        return [
            'performed_by_driver_id' => 1,
            'service_date' => '2025-06-15',
            'route_number' => 'R-100',
            'departure_time' => '08:00',
            'arrival_time' => '16:00',
            'actual_km' => '105.00',
            'planned_km' => '100.00',
            'loaded_parcels' => 120,
            'delivered_parcels' => 90,
            'redirected_parcels' => 20,
            'undelivered_parcels' => 10,
            'actual_km_source' => 'manual',
            'surcharge_amount' => null,
            'operational_notes' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function baseAttributes(
        array $input,
    ): array {
        $attributes = $input;

        unset(
            $attributes['performed_by_driver_id'],
            $attributes['service_date'],
            $attributes['route_number'],
        );

        return $attributes;
    }
}
