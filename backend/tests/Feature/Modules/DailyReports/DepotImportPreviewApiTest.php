<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\DepotWorkbookFactory;
use Tests\TestCase;

final class DepotImportPreviewApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/daily-reports/depot-imports';

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        parent::tearDown();
    }

    public function test_preview_requires_authentication_context_permission_and_explicit_alias_confirmation(): void
    {
        $path = $this->workbook();

        try {
            $this->post(
                self::URL.'/inspect',
                ['workbook' => $this->upload($path)],
                ['Accept' => 'application/json'],
            )->assertUnauthorized();

            [$actor, $organization] = $this->context('Kökörčený');
            Sanctum::actingAs($actor);

            $this->post(
                self::URL.'/inspect',
                ['workbook' => $this->upload($path)],
                ['Accept' => 'application/json'],
            )->assertStatus(400);

            $this->organizationRequest($organization)
                ->post(
                    self::URL.'/inspect',
                    ['workbook' => $this->upload($path)],
                    ['Accept' => 'application/json'],
                )->assertForbidden();

            $this->grantPermissions(
                $actor,
                $organization,
                ['daily-reports.view'],
            );

            $this->organizationRequest($organization)
                ->post(
                    self::URL.'/preview',
                    [
                        'workbook' => $this->upload($path),
                        'carrier_alias' => 'Kökörčený',
                    ],
                    ['Accept' => 'application/json'],
                )->assertUnprocessable()
                ->assertJsonValidationErrors(
                    'carrier_alias_confirmed',
                );
        } finally {
            @unlink($path);
        }
    }

    public function test_inspection_and_preview_filter_exact_normalized_carrier_and_preserve_control_totals(): void
    {
        $path = $this->workbook();

        try {
            [$actor, $organization] = $this->context('Kökörčený');
            $this->grantPermissions(
                $actor,
                $organization,
                ['daily-reports.view'],
            );
            $this->eligibleDriver($actor, $organization);
            Sanctum::actingAs($actor);

            $this->organizationRequest($organization)
                ->post(
                    self::URL.'/inspect',
                    ['workbook' => $this->upload($path)],
                    ['Accept' => 'application/json'],
                )
                ->assertOk()
                ->assertJsonPath('data.suggested_alias', 'Kökörčený')
                ->assertJsonPath('data.normalized_suggested_alias', 'kokorceny')
                ->assertJsonPath('data.suggested_matching_row_count', 3)
                ->assertJsonPath('data.source.read_only', true)
                ->assertJsonPath('data.source.stored', false)
                ->assertJsonPath(
                    'data.source.formula_values_used_for_import',
                    false,
                )
                ->assertJsonPath('data.detected.header_start_row', 1)
                ->assertJsonPath('data.detected.header_end_row', 2)
                ->assertJsonMissingPath(
                    'data.detected.columns.reported_not_delivered_parcels',
                )
                ->assertJsonCount(3, 'data.carrier_values');

            $response = $this->organizationRequest($organization)
                ->post(
                    self::URL.'/preview',
                    [
                        'workbook' => $this->upload($path),
                        'carrier_alias' => '  Kökörčený ',
                        'carrier_alias_confirmed' => '1',
                    ],
                    ['Accept' => 'application/json'],
                );

            $response
                ->assertOk()
                ->assertJsonPath('data.confirmed_alias', 'Kökörčený')
                ->assertJsonPath('data.normalized_confirmed_alias', 'kokorceny')
                ->assertJsonPath('data.excluded_carrier_row_count', 1)
                ->assertJsonPath('data.totals.matched_rows', 3)
                ->assertJsonPath('data.totals.ready_rows', 2)
                ->assertJsonPath('data.totals.no_run_rows', 1)
                ->assertJsonPath('data.totals.invalid_rows', 0)
                ->assertJsonPath('data.totals.loaded_parcels', 150)
                ->assertJsonPath('data.totals.delivered_parcels', 125)
                ->assertJsonPath('data.totals.redirected_parcels', 12)
                ->assertJsonPath('data.totals.customer_rejected_parcels', 6)
                ->assertJsonPath('data.totals.computed_not_delivered_parcels', 7)
                ->assertJsonPath('data.row_count', 3)
                ->assertJsonPath('data.rows.2.status', 'no_run')
                ->assertJsonPath(
                    'data.rows.0.computed_not_delivered_parcels',
                    5,
                )
                ->assertJsonMissingPath(
                    'data.rows.0.reported_not_delivered_parcels',
                )
                ->assertJsonPath('data.import_enabled', false)
                ->assertJsonPath('data.write_performed', false)
                ->assertJsonCount(2, 'data.matched_carrier_values')
                ->assertJsonCount(2, 'data.source_driver_values')
                ->assertJsonCount(1, 'data.eligible_drivers');

            self::assertDatabaseCount('daily_reports', 0);
        } finally {
            @unlink($path);
        }
    }

    public function test_preview_ignores_reported_not_delivered_and_financial_totals_but_requires_note_for_positive_surcharge(): void
    {
        $path = DepotWorkbookFactory::create([
            [
                'Datum',
                'Trasa',
                'Dopravce',
                'Jméno řidiče',
                'Poznámka',
                'Naloženo',
                'Doručeno na adresu',
                'Doručeno na VM',
                'Odmítnuté',
                'Příplatek',
                'Nerozvezeno',
                'Náklady celkem Kč',
            ],
            [
                '02.06.2025',
                35,
                'Kökörčený',
                'Hrůza Vít',
                null,
                100,
                80,
                10,
                5,
                25,
                999,
                5000,
            ],
            [
                '03.06.2025',
                36,
                'Kökörčený',
                'Hrůza Vít',
                'Mimořádný svoz potvrzený depem.',
                50,
                45,
                2,
                1,
                25,
                777,
                7000,
            ],
        ]);

        try {
            [$actor, $organization] = $this->context('Kökörčený');
            $this->grantPermissions(
                $actor,
                $organization,
                ['daily-reports.view'],
            );
            Sanctum::actingAs($actor);

            $response = $this->organizationRequest($organization)
                ->post(
                    self::URL.'/preview',
                    [
                        'workbook' => $this->upload($path),
                        'carrier_alias' => 'Kökörčený',
                        'carrier_alias_confirmed' => '1',
                    ],
                    ['Accept' => 'application/json'],
                );

            $response
                ->assertOk()
                ->assertJsonPath('data.totals.ready_rows', 1)
                ->assertJsonPath('data.totals.invalid_rows', 1)
                ->assertJsonPath('data.rows.0.status', 'invalid')
                ->assertJsonPath(
                    'data.rows.0.errors.0',
                    'Poznámka je povinná, pokud je příplatek vyšší než nula.',
                )
                ->assertJsonPath(
                    'data.rows.0.computed_not_delivered_parcels',
                    5,
                )
                ->assertJsonPath('data.rows.1.status', 'ready')
                ->assertJsonPath('data.rows.1.surcharge_amount', '25.00')
                ->assertJsonPath(
                    'data.rows.1.operational_notes',
                    'Mimořádný svoz potvrzený depem.',
                )
                ->assertJsonMissingPath(
                    'data.detected.columns.reported_not_delivered_parcels',
                )
                ->assertJsonMissingPath(
                    'data.rows.0.reported_not_delivered_parcels',
                );
        } finally {
            @unlink($path);
        }
    }

    private function workbook(): string
    {
        return DepotWorkbookFactory::create(
            [
                [
                    'Rok',
                    'Měsíc',
                    'Datum',
                    'Trasa',
                    'Dopravce',
                    'Jméno řidiče',
                    'Poznámka',
                    'Čas odjezdu',
                    'Čas příjezdu',
                    'Trasa km Naměřená Position',
                    'Trasa km Plánovaná Position',
                    'Naloženo',
                    'Doručeno na adresu ks',
                    'Doručeno na VM ks',
                    'Odmítnuté',
                    'Příplatky',
                    'Nerozvezeno',
                    'Součet',
                ],
                [
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    '(důvod)',
                    null,
                    null,
                    null,
                    null,
                    'ks',
                    null,
                    null,
                    'ks',
                    null,
                    null,
                    null,
                ],
                [
                    2025,
                    6,
                    '02.06.2025',
                    35,
                    'Kökörčený',
                    'Hrůza Vít',
                    null,
                    '08:00',
                    '16:00',
                    164,
                    136,
                    100,
                    80,
                    10,
                    5,
                    0,
                    999,
                    95,
                ],
                [
                    2025,
                    6,
                    '03.06.2025',
                    36,
                    'Kökörčeny',
                    'Hrůza Vít',
                    null,
                    '08:10',
                    '15:30',
                    120,
                    115,
                    50,
                    45,
                    2,
                    1,
                    0,
                    777,
                    48,
                ],
                [
                    2025,
                    6,
                    '03.06.2025',
                    10,
                    'Jiný dopravce',
                    'Cizí řidič',
                    null,
                    '08:00',
                    '16:00',
                    50,
                    50,
                    10,
                    10,
                    0,
                    0,
                    0,
                    0,
                    10,
                ],
                [
                    2025,
                    6,
                    '04.06.2025',
                    37,
                    'Kökörčený',
                    'Kökörčený D.',
                    'Neodjela kvůli poruše vozidla.',
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                ],
            ],
            [
                'R3' => [
                    'formula' => 'SUM(M3:O3)',
                    'value' => 95,
                ],
                'R4' => [
                    'formula' => 'SUM(M4:O4)',
                    'value' => 48,
                ],
            ],
        );
    }

    private function upload(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            '06-2025.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    /** @return array{User, Organization} */
    private function context(string $organizationName): array
    {
        $actor = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => $organizationName,
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $actor->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => '2025-01-01',
            'valid_until' => null,
        ]);

        return [$actor, $organization];
    }

    private function eligibleDriver(
        User $actor,
        Organization $organization,
    ): void {
        $driverUser = User::factory()->create();
        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Vít',
            'last_name' => 'Hrůza',
            'external_driver_id' => 'DEPOT-001',
            'license_number' => 'TEST-DEPOT-001',
            'active' => true,
        ]);

        DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $organization->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => '2025-01-01',
            'valid_until' => null,
            'created_by_user_id' => $actor->getKey(),
        ]);
    }

    /** @param  list<string>  $permissions */
    private function grantPermissions(
        User $actor,
        Organization $organization,
        array $permissions,
    ): void {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(
                (int) $organization->getKey(),
            );
            $registrar->forgetCachedPermissions();

            foreach ($permissions as $permission) {
                $actor->givePermissionTo(
                    Permission::findOrCreate($permission, 'web'),
                );
            }
        } finally {
            $actor->unsetRelation('roles');
            $actor->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($previous);
            $registrar->forgetCachedPermissions();
        }
    }

    private function organizationRequest(
        Organization $organization,
    ): static {
        return $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }
}
