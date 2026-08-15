<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DailyReportVersion;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\FinancialCalculationEvent;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class FinancialCalculationWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_URL =
        '/api/v1/financial-calculations';

    protected function setUp(): void
    {
        parent::setUp();

        app(OrganizationContext::class)->clear();

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        app(OrganizationContext::class)->clear();

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();

        parent::tearDown();
    }

    public function test_guest_cannot_create_financial_calculation(): void
    {
        $this->postJson(
            self::STORE_URL,
            [],
        )->assertUnauthorized();

        $this->assertNoFinancialRecords();
    }

    public function test_organization_context_is_required(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $this->postJson(
            self::STORE_URL,
            $this->payload($foundation),
        )->assertStatus(400);

        $this->assertNoFinancialRecords();
    }

    public function test_compensation_manage_permission_is_required(): void
    {
        $foundation = $this->createFoundation(false);

        Sanctum::actingAs($foundation['user']);

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $this->payload($foundation),
            )
            ->assertForbidden();

        $this->assertNoFinancialRecords();
    }

    public function test_creation_payload_is_validated(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                [
                    'daily_report_public_id' => 'invalid',
                    'daily_report_version' => 0,
                    'price_list_public_id' => 'invalid',
                    'price_list_version' => 0,
                    'reason' => str_repeat('x', 2001),
                ],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'daily_report_public_id',
                'daily_report_version',
                'price_list_public_id',
                'price_list_version',
                'reason',
            ]);

        $this->assertNoFinancialRecords();
    }

    public function test_it_creates_initial_calculation_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $response = $this
            ->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $this->payload(
                    $foundation,
                    '  Initial API calculation.  ',
                ),
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Financial calculation created.',
            )
            ->assertJsonPath(
                'data.status',
                FinancialCalculation::STATUS_CALCULATED,
            )
            ->assertJsonPath(
                'data.currency',
                'CZK',
            )
            ->assertJsonPath(
                'data.daily_report_public_id',
                (string) $foundation['dailyReport']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.daily_report_version',
                4,
            )
            ->assertJsonPath(
                'data.price_list_public_id',
                (string) $foundation['priceList']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.price_list_version',
                1,
            )
            ->assertJsonPath(
                'data.calculation_version',
                1,
            )
            ->assertJsonPath(
                'data.subtotal_amount',
                '194.56',
            )
            ->assertJsonPath(
                'data.total_amount',
                '194.56',
            )
            ->assertJsonCount(
                4,
                'data.lines',
            );

        $publicId =
            $response->json('data.public_id');

        self::assertIsString($publicId);
        self::assertTrue(Str::isUuid($publicId));

        $payload =
            $response->json('data');

        self::assertIsArray($payload);

        $this->assertNoInternalDatabaseIdentifiers(
            $payload,
        );

        $calculation = FinancialCalculation::query()
            ->where(
                'public_id',
                $publicId,
            )
            ->sole();

        self::assertSame(
            $foundation['provider']->getKey(),
            $calculation->getAttribute('organization_id'),
        );

        self::assertSame(
            $foundation['relationship']->getKey(),
            $calculation->getAttribute(
                'organization_relationship_id',
            ),
        );

        self::assertSame(
            $foundation['dailyReport']->getKey(),
            $calculation->getAttribute('daily_report_id'),
        );

        self::assertSame(
            $foundation['priceListVersion']->getKey(),
            $calculation->getAttribute(
                'price_list_version_id',
            ),
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $calculation->getAttribute(
                'calculated_by_user_id',
            ),
        );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            4,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );

        $this->assertDatabaseHas(
            'financial_calculation_lines',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'pricing_code' => PriceListItem::CODE_DELIVERED_PARCELS,
                'quantity' => '20.000',
                'unit_rate' => '4.2500',
                'line_amount' => '85.00',
                'position' => 1,
            ],
        );

        $this->assertDatabaseHas(
            'financial_calculation_lines',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'pricing_code' => PriceListItem::CODE_REDIRECTED_PARCELS,
                'quantity' => '2.000',
                'unit_rate' => '1.0050',
                'line_amount' => '2.01',
                'position' => 2,
            ],
        );

        $this->assertDatabaseHas(
            'financial_calculation_lines',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'pricing_code' => PriceListItem::CODE_UNDELIVERED_PARCELS,
                'quantity' => '1.000',
                'unit_rate' => '7.0000',
                'line_amount' => '7.00',
                'position' => 3,
            ],
        );

        $this->assertDatabaseHas(
            'financial_calculation_lines',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'pricing_code' => PriceListItem::CODE_ACTUAL_KM,
                'quantity' => '8.145',
                'unit_rate' => '12.3456',
                'line_amount' => '100.55',
                'position' => 4,
            ],
        );

        $this->assertDatabaseHas(
            'financial_calculation_events',
            [
                'financial_calculation_id' => $calculation->getKey(),
                'organization_id' => $foundation['provider']->getKey(),
                'event_type' => FinancialCalculationEvent::TYPE_CALCULATED,
                'from_status' => null,
                'to_status' => FinancialCalculation::STATUS_CALCULATED,
                'acted_by_user_id' => $foundation['user']->getKey(),
                'reason' => 'Initial API calculation.',
            ],
        );
    }

    public function test_unavailable_price_list_is_not_found_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $payload = $this->payload($foundation);
        $payload['price_list_public_id'] = (string) Str::uuid();

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $payload,
            )
            ->assertNotFound();

        $this->assertNoFinancialRecords();
    }

    public function test_duplicate_initial_calculation_is_rejected_as_conflict_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $payload = $this->payload($foundation);

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $payload,
            )
            ->assertCreated();

        $this->withOrganization($foundation['provider'])
            ->postJson(
                self::STORE_URL,
                $payload,
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The daily-report version has already been calculated.',
            );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            4,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_guest_cannot_start_financial_calculation_review(): void
    {
        $this->postJson(
            self::STORE_URL.'/'.
            Str::uuid().
            '/review',
            [
                'reason' => 'Unauthorized review.',
            ],
        )->assertUnauthorized();

        $this->assertDatabaseCount(
            'financial_calculation_events',
            0,
        );
    }

    public function test_organization_context_is_required_for_financial_calculation_review(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->flushHeaders();

        Sanctum::actingAs(
            $foundation['user'],
        );

        $this->postJson(
            $this->reviewUrl($calculation),
            [],
        )->assertStatus(400);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_compensation_manage_permission_is_required_for_financial_calculation_review(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $unprivilegedUser = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $foundation['provider']->getKey(),
            'user_id' => $unprivilegedUser->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        Sanctum::actingAs(
            $unprivilegedUser,
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->reviewUrl($calculation),
                [],
            )
            ->assertForbidden();

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_financial_calculation_review_payload_is_validated(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        Sanctum::actingAs(
            $foundation['user'],
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->reviewUrl($calculation),
                [
                    'reason' => str_repeat(
                        'x',
                        2001,
                    ),
                ],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason',
            ]);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_it_starts_financial_calculation_review_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $inputSnapshotBefore =
            $calculation->getAttribute(
                'input_snapshot',
            );

        self::assertIsArray(
            $inputSnapshotBefore,
        );

        $subtotalBefore =
            $calculation->getAttribute(
                'subtotal_amount',
            );

        $totalBefore =
            $calculation->getAttribute(
                'total_amount',
            );

        Sanctum::actingAs(
            $foundation['user'],
        );

        $response =
            $this->withOrganization(
                $foundation['provider'],
            )
                ->postJson(
                    $this->reviewUrl(
                        $calculation,
                    ),
                    [
                        'reason' => (
                            '  Manual financial '.
                            'review started.  '
                        ),
                    ],
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Financial calculation review started.',
            )
            ->assertJsonPath(
                'data.public_id',
                (string) $calculation->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.status',
                FinancialCalculation::STATUS_UNDER_REVIEW,
            )
            ->assertJsonPath(
                'data.daily_report_public_id',
                (string) $foundation['dailyReport']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.daily_report_version',
                4,
            )
            ->assertJsonPath(
                'data.price_list_public_id',
                (string) $foundation['priceList']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.price_list_version',
                1,
            )
            ->assertJsonCount(
                4,
                'data.lines',
            )
            ->assertJsonPath(
                'data.lines.0.pricing_code',
                PriceListItem::CODE_DELIVERED_PARCELS,
            )
            ->assertJsonPath(
                'data.lines.3.pricing_code',
                PriceListItem::CODE_ACTUAL_KM,
            )
            ->assertJsonPath(
                'data.calculation_version',
                1,
            )
            ->assertJsonPath(
                'data.subtotal_amount',
                '194.56',
            )
            ->assertJsonPath(
                'data.total_amount',
                '194.56',
            )
            ->assertJsonPath(
                'data.approved_at',
                null,
            )
            ->assertJsonPath(
                'data.closed_at',
                null,
            );

        $payload =
            $response->json('data');

        self::assertIsArray(
            $payload,
        );

        $this->assertNoInternalDatabaseIdentifiers(
            $payload,
        );

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $calculation->getAttribute('status'),
        );

        self::assertSame(
            $inputSnapshotBefore,
            $calculation->getAttribute(
                'input_snapshot',
            ),
        );

        self::assertSame(
            $subtotalBefore,
            $calculation->getAttribute(
                'subtotal_amount',
            ),
        );

        self::assertSame(
            $totalBefore,
            $calculation->getAttribute(
                'total_amount',
            ),
        );

        self::assertNull(
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNull(
            $calculation->getAttribute(
                'approved_at',
            ),
        );

        self::assertNull(
            $calculation->getAttribute(
                'closed_at',
            ),
        );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            4,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );

        $reviewEvent =
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_REVIEW_STARTED,
                )
                ->sole();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $reviewEvent->getAttribute(
                'from_status',
            ),
        );

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $reviewEvent->getAttribute(
                'to_status',
            ),
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $reviewEvent->getAttribute(
                'acted_by_user_id',
            ),
        );

        self::assertSame(
            'Manual financial review started.',
            $reviewEvent->getAttribute(
                'reason',
            ),
        );

        $metadata =
            $reviewEvent->getAttribute(
                'metadata',
            );

        self::assertIsArray(
            $metadata,
        );

        self::assertSame(
            1,
            $metadata['calculation_version']
                ?? null,
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $metadata['reviewed_by_user_id']
                ?? null,
        );
    }

    public function test_financial_calculation_review_is_organization_scoped(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $otherOrganization =
            $this->createOrganization(
                Organization::TYPE_SUBCONTRACTOR,
            );

        OrganizationMembership::query()->create([
            'organization_id' => $otherOrganization->getKey(),
            'user_id' => $foundation['user']->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        $this->grantManagePermission(
            $foundation['user'],
            $otherOrganization,
        );

        Sanctum::actingAs(
            $foundation['user'],
        );

        $this->withOrganization(
            $otherOrganization,
        )
            ->postJson(
                $this->reviewUrl($calculation),
                [
                    'reason' => 'Cross-organization review.',
                ],
            )
            ->assertNotFound();

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_repeated_financial_calculation_review_is_rejected_as_conflict_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        Sanctum::actingAs(
            $foundation['user'],
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->reviewUrl($calculation),
                [
                    'reason' => 'Initial review.',
                ],
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->reviewUrl($calculation),
                [
                    'reason' => 'Repeated review.',
                ],
            )
            ->assertStatus(409);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $calculation->getAttribute('status'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );

        self::assertSame(
            1,
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_REVIEW_STARTED,
                )
                ->count(),
        );
    }

    public function test_financial_calculation_approval_requires_authentication(): void
    {
        $this->postJson(
            self::STORE_URL.'/'.
            (string) Str::uuid().
            '/approve',
            [],
        )->assertUnauthorized();

        $this->assertNoFinancialRecords();
    }

    public function test_financial_calculation_approval_requires_organization_context(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->startReviewForApproval(
            $foundation,
            $calculation,
            'Review before organization-context validation.',
        );

        app(OrganizationContext::class)->clear();

        $this->withHeader(
            'X-Organization-ID',
            '',
        )
            ->postJson(
                $this->approvalUrl($calculation),
                [],
            )
            ->assertStatus(400);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute('approved_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );
    }

    public function test_financial_calculation_approval_requires_compensation_manage_permission(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->startReviewForApproval(
            $foundation,
            $calculation,
            'Review before permission validation.',
        );

        $unprivilegedUser = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $foundation['provider']->getKey(),
            'user_id' => $unprivilegedUser->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        Sanctum::actingAs($unprivilegedUser);

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->approvalUrl($calculation),
                [
                    'reason' => 'Unauthorized approval.',
                ],
            )
            ->assertForbidden();

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNull(
            $calculation->getAttribute('approved_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );
    }

    public function test_financial_calculation_approval_payload_is_validated(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->startReviewForApproval(
            $foundation,
            $calculation,
            'Review before payload validation.',
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->approvalUrl($calculation),
                [
                    'reason' => str_repeat(
                        'x',
                        2001,
                    ),
                ],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason',
            ]);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute('approved_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );
    }

    public function test_financial_calculation_approval_is_atomic(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->startReviewForApproval(
            $foundation,
            $calculation,
            'Ready for approval.',
        );

        $calculation->refresh();

        $inputSnapshotBefore =
            $calculation->getAttribute(
                'input_snapshot',
            );

        $subtotalBefore =
            $calculation->getAttribute(
                'subtotal_amount',
            );

        $totalBefore =
            $calculation->getAttribute(
                'total_amount',
            );

        $lineCountBefore =
            $calculation->lines()->count();

        $response =
            $this->withOrganization(
                $foundation['provider'],
            )
                ->postJson(
                    $this->approvalUrl($calculation),
                    [
                        'reason' => (
                            '  Financial calculation '.
                            'approved manually.  '
                        ),
                    ],
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Financial calculation approved.',
            )
            ->assertJsonPath(
                'data.public_id',
                (string) $calculation->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.status',
                FinancialCalculation::STATUS_APPROVED,
            )
            ->assertJsonPath(
                'data.daily_report_public_id',
                (string) $foundation['dailyReport']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.daily_report_version',
                4,
            )
            ->assertJsonPath(
                'data.price_list_public_id',
                (string) $foundation['priceList']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.price_list_version',
                1,
            )
            ->assertJsonPath(
                'data.subtotal_amount',
                '194.56',
            )
            ->assertJsonPath(
                'data.total_amount',
                '194.56',
            )
            ->assertJsonCount(
                4,
                'data.lines',
            )
            ->assertJsonPath(
                'data.closed_at',
                null,
            );

        $approvedAt =
            $response->json(
                'data.approved_at',
            );

        self::assertIsString($approvedAt);
        self::assertNotSame('', $approvedAt);

        $payload =
            $response->json('data');

        self::assertIsArray($payload);

        $this->assertNoInternalDatabaseIdentifiers(
            $payload,
        );

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $calculation->getAttribute('status'),
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNotNull(
            $calculation->getAttribute('approved_at'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        self::assertSame(
            $inputSnapshotBefore,
            $calculation->getAttribute(
                'input_snapshot',
            ),
        );

        self::assertSame(
            $subtotalBefore,
            $calculation->getAttribute(
                'subtotal_amount',
            ),
        );

        self::assertSame(
            $totalBefore,
            $calculation->getAttribute(
                'total_amount',
            ),
        );

        self::assertSame(
            $lineCountBefore,
            $calculation->lines()->count(),
        );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            4,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            3,
        );

        $approvalEvent =
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_APPROVED,
                )
                ->sole();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $approvalEvent->getAttribute(
                'from_status',
            ),
        );

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $approvalEvent->getAttribute(
                'to_status',
            ),
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $approvalEvent->getAttribute(
                'acted_by_user_id',
            ),
        );

        self::assertSame(
            'Financial calculation approved manually.',
            $approvalEvent->getAttribute('reason'),
        );

        $metadata =
            $approvalEvent->getAttribute(
                'metadata',
            );

        self::assertIsArray($metadata);

        self::assertSame(
            1,
            $metadata['calculation_version']
                ?? null,
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $metadata['approved_by_user_id']
                ?? null,
        );
    }

    public function test_financial_calculation_approval_is_organization_scoped(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->startReviewForApproval(
            $foundation,
            $calculation,
            'Review before organization-scope validation.',
        );

        $otherOrganization =
            $this->createOrganization(
                Organization::TYPE_SUBCONTRACTOR,
            );

        OrganizationMembership::query()->create([
            'organization_id' => $otherOrganization->getKey(),
            'user_id' => $foundation['user']->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        $this->grantManagePermission(
            $foundation['user'],
            $otherOrganization,
        );

        Sanctum::actingAs(
            $foundation['user'],
        );

        $this->withOrganization(
            $otherOrganization,
        )
            ->postJson(
                $this->approvalUrl($calculation),
                [
                    'reason' => 'Cross-organization approval.',
                ],
            )
            ->assertNotFound();

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute('approved_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );
    }

    public function test_financial_calculation_approval_rejects_calculated_state(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->approvalUrl($calculation),
                [
                    'reason' => 'Direct approval attempt.',
                ],
            )
            ->assertStatus(409);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNull(
            $calculation->getAttribute('approved_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );

        self::assertSame(
            0,
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_APPROVED,
                )
                ->count(),
        );
    }

    public function test_repeated_financial_calculation_approval_is_rejected(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->startReviewForApproval(
            $foundation,
            $calculation,
            'Review before repeated approval.',
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->approvalUrl($calculation),
                [
                    'reason' => 'Initial approval.',
                ],
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'financial_calculation_events',
            3,
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->approvalUrl($calculation),
                [
                    'reason' => 'Repeated approval.',
                ],
            )
            ->assertStatus(409);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $calculation->getAttribute('status'),
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNotNull(
            $calculation->getAttribute('approved_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            3,
        );

        self::assertSame(
            1,
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_APPROVED,
                )
                ->count(),
        );
    }

    public function test_financial_calculation_closure_requires_authentication(): void
    {
        $this->postJson(
            self::STORE_URL.'/'.
            (string) Str::uuid().
            '/close',
            [],
        )->assertUnauthorized();

        $this->assertNoFinancialRecords();
    }

    public function test_financial_calculation_closure_requires_organization_context(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $calculation,
            'Review before organization-context closure validation.',
            'Approval before organization-context closure validation.',
        );

        app(OrganizationContext::class)->clear();

        $this->withHeader(
            'X-Organization-ID',
            '',
        )
            ->postJson(
                $this->closureUrl($calculation),
                [],
            )
            ->assertStatus(400);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            3,
        );
    }

    public function test_financial_calculation_closure_requires_compensation_manage_permission(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $calculation,
            'Review before closure permission validation.',
            'Approval before closure permission validation.',
        );

        $unprivilegedUser = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $foundation['provider']->getKey(),
            'user_id' => $unprivilegedUser->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        Sanctum::actingAs($unprivilegedUser);

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->closureUrl($calculation),
                [
                    'reason' => 'Unauthorized closure.',
                ],
            )
            ->assertForbidden();

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            3,
        );
    }

    public function test_financial_calculation_closure_payload_is_validated(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $calculation,
            'Review before closure payload validation.',
            'Approval before closure payload validation.',
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->closureUrl($calculation),
                [
                    'reason' => str_repeat(
                        'x',
                        2001,
                    ),
                ],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason',
            ]);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            3,
        );
    }

    public function test_financial_calculation_closure_is_atomic(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $calculation,
            'Review before successful closure.',
            'Approval before successful closure.',
        );

        $calculation->refresh();

        $inputSnapshotBefore =
            $calculation->getAttribute(
                'input_snapshot',
            );

        $subtotalBefore =
            $calculation->getAttribute(
                'subtotal_amount',
            );

        $totalBefore =
            $calculation->getAttribute(
                'total_amount',
            );

        $lineCountBefore =
            $calculation->lines()->count();

        $approvedByUserIdBefore =
            $calculation->getAttribute(
                'approved_by_user_id',
            );

        $approvedAtBefore =
            (string) $calculation->getAttribute(
                'approved_at',
            );

        $response =
            $this->withOrganization(
                $foundation['provider'],
            )
                ->postJson(
                    $this->closureUrl($calculation),
                    [
                        'reason' => (
                            '  Financial calculation '.
                            'closed manually.  '
                        ),
                    ],
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Financial calculation closed.',
            )
            ->assertJsonPath(
                'data.public_id',
                (string) $calculation->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.status',
                FinancialCalculation::STATUS_CLOSED,
            )
            ->assertJsonPath(
                'data.daily_report_public_id',
                (string) $foundation['dailyReport']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.daily_report_version',
                4,
            )
            ->assertJsonPath(
                'data.price_list_public_id',
                (string) $foundation['priceList']->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.price_list_version',
                1,
            )
            ->assertJsonPath(
                'data.subtotal_amount',
                '194.56',
            )
            ->assertJsonPath(
                'data.total_amount',
                '194.56',
            )
            ->assertJsonCount(
                4,
                'data.lines',
            );

        $responseApprovedAt =
            $response->json(
                'data.approved_at',
            );

        $responseClosedAt =
            $response->json(
                'data.closed_at',
            );

        self::assertIsString($responseApprovedAt);
        self::assertNotSame('', $responseApprovedAt);
        self::assertIsString($responseClosedAt);
        self::assertNotSame('', $responseClosedAt);

        $payload =
            $response->json('data');

        self::assertIsArray($payload);

        $this->assertNoInternalDatabaseIdentifiers(
            $payload,
        );

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CLOSED,
            $calculation->getAttribute('status'),
        );

        self::assertNotNull(
            $calculation->getAttribute('closed_at'),
        );

        self::assertSame(
            $approvedByUserIdBefore,
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertSame(
            $approvedAtBefore,
            (string) $calculation->getAttribute(
                'approved_at',
            ),
        );

        self::assertSame(
            $inputSnapshotBefore,
            $calculation->getAttribute(
                'input_snapshot',
            ),
        );

        self::assertSame(
            $subtotalBefore,
            $calculation->getAttribute(
                'subtotal_amount',
            ),
        );

        self::assertSame(
            $totalBefore,
            $calculation->getAttribute(
                'total_amount',
            ),
        );

        self::assertSame(
            $lineCountBefore,
            $calculation->lines()->count(),
        );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            4,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            4,
        );

        $closureEvent =
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_CLOSED,
                )
                ->sole();

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $closureEvent->getAttribute(
                'from_status',
            ),
        );

        self::assertSame(
            FinancialCalculation::STATUS_CLOSED,
            $closureEvent->getAttribute(
                'to_status',
            ),
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $closureEvent->getAttribute(
                'acted_by_user_id',
            ),
        );

        self::assertSame(
            'Financial calculation closed manually.',
            $closureEvent->getAttribute('reason'),
        );

        $metadata =
            $closureEvent->getAttribute(
                'metadata',
            );

        self::assertIsArray($metadata);

        self::assertSame(
            1,
            $metadata['calculation_version']
                ?? null,
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $metadata['closed_by_user_id']
                ?? null,
        );
    }

    public function test_financial_calculation_closure_is_organization_scoped(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $calculation,
            'Review before closure organization-scope validation.',
            'Approval before closure organization-scope validation.',
        );

        $otherOrganization =
            $this->createOrganization(
                Organization::TYPE_SUBCONTRACTOR,
            );

        OrganizationMembership::query()->create([
            'organization_id' => $otherOrganization->getKey(),
            'user_id' => $foundation['user']->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        $this->grantManagePermission(
            $foundation['user'],
            $otherOrganization,
        );

        Sanctum::actingAs(
            $foundation['user'],
        );

        $this->withOrganization(
            $otherOrganization,
        )
            ->postJson(
                $this->closureUrl($calculation),
                [
                    'reason' => 'Cross-organization closure.',
                ],
            )
            ->assertNotFound();

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            3,
        );
    }

    public function test_financial_calculation_closure_rejects_under_review_state(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->startReviewForApproval(
            $foundation,
            $calculation,
            'Review before direct closure attempt.',
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->closureUrl($calculation),
                [
                    'reason' => 'Direct closure attempt.',
                ],
            )
            ->assertStatus(409);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );

        self::assertSame(
            0,
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_CLOSED,
                )
                ->count(),
        );
    }

    public function test_repeated_financial_calculation_closure_is_rejected(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $calculation,
            'Review before repeated closure.',
            'Approval before repeated closure.',
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->closureUrl($calculation),
                [
                    'reason' => 'Initial closure.',
                ],
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'financial_calculation_events',
            4,
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->closureUrl($calculation),
                [
                    'reason' => 'Repeated closure.',
                ],
            )
            ->assertStatus(409);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CLOSED,
            $calculation->getAttribute('status'),
        );

        self::assertNotNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            4,
        );

        self::assertSame(
            1,
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_CLOSED,
                )
                ->count(),
        );
    }

    /**
     * @return array{
     *     customer: Organization,
     *     provider: Organization,
     *     relationship: OrganizationRelationship,
     *     user: User,
     *     dailyReport: DailyReport,
     *     dailyReportVersion: DailyReportVersion,
     *     priceList: PriceList,
     *     priceListVersion: PriceListVersion
     * }
     */
    private function createFoundation(
        bool $grantPermission,
    ): array {
        $customer = $this->createOrganization(
            Organization::TYPE_CARRIER,
        );

        $provider = $this->createOrganization(
            Organization::TYPE_SUBCONTRACTOR,
        );

        $relationship =
            OrganizationRelationship::query()->create([
                'source_organization_id' => $customer->getKey(),
                'target_organization_id' => $provider->getKey(),
                'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
                'status' => OrganizationRelationship::STATUS_ACTIVE,
                'valid_from' => '2026-07-01',
                'valid_until' => null,
            ]);

        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $provider->getKey(),
            'user_id' => $user->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        if ($grantPermission) {
            $this->grantManagePermission(
                $user,
                $provider,
            );
        }

        $driver = Driver::query()->create([
            'user_id' => $user->getKey(),
            'first_name' => 'Creation',
            'last_name' => 'Driver',
            'phone' => null,
            'email' => null,
            'license_number' => 'CREATE-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $routeNumber =
            'CREATE-'.Str::upper(
                Str::random(12),
            );

        $dailyReport = DailyReport::query()->create([
            'organization_id' => $customer->getKey(),
            'trip_id' => null,
            'performed_by_driver_id' => $driver->getKey(),
            'vehicle_id' => null,
            'entered_by_user_id' => $user->getKey(),
            'route_number' => $routeNumber,
            'route_number_normalized' => Str::lower(
                $routeNumber,
            ),
            'service_date' => '2026-07-29',
            'status' => DailyReport::STATUS_APPROVED,
            'entry_method' => DailyReport::ENTRY_METHOD_DRIVER,
            'entered_on_behalf' => false,
            'completion_confirmed_at' => '2026-07-29 09:00:00',
            'loaded_parcels' => 23,
            'delivered_parcels' => 20,
            'redirected_parcels' => 2,
            'undelivered_parcels' => 1,
            'planned_km' => '9.000',
            'actual_km' => '8.145',
            'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
            'operational_notes' => 'Financial creation API test',
            'current_version' => 4,
            'submitted_at' => '2026-07-29 09:05:00',
            'review_started_at' => '2026-07-29 09:10:00',
            'reviewed_by_user_id' => $user->getKey(),
            'approved_at' => '2026-07-29 09:15:00',
            'approved_by_user_id' => $user->getKey(),
            'closed_at' => null,
        ]);

        $dailyReportVersion =
            DailyReportVersion::query()->create([
                'daily_report_id' => $dailyReport->getKey(),
                'version_number' => 4,
                'snapshot' => [
                    'public_id' => (string) $dailyReport->getAttribute(
                        'public_id',
                    ),
                    'organization_id' => $customer->getKey(),
                    'trip_id' => null,
                    'performed_by_driver_id' => $driver->getKey(),
                    'vehicle_id' => null,
                    'route_number' => $routeNumber,
                    'route_number_normalized' => Str::lower(
                        $routeNumber,
                    ),
                    'service_date' => '2026-07-29',
                    'status' => DailyReport::STATUS_APPROVED,
                    'loaded_parcels' => 23,
                    'delivered_parcels' => 20,
                    'redirected_parcels' => 2,
                    'undelivered_parcels' => 1,
                    'planned_km' => '9.000',
                    'actual_km' => '8.145',
                    'actual_km_source' => DailyReport::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
                    'current_version' => 4,
                    'approved_at' => '2026-07-29 09:15:00',
                    'approved_by_user_id' => $user->getKey(),
                    'closed_at' => null,
                ],
                'changed_fields' => [],
                'created_by_user_id' => $user->getKey(),
                'change_reason' => 'Approved financial snapshot',
                'created_at' => '2026-07-29 09:15:00',
            ]);

        $priceList = PriceList::query()->create([
            'organization_relationship_id' => $relationship->getKey(),
            'owner_organization_id' => $customer->getKey(),
            'customer_organization_id' => $customer->getKey(),
            'provider_organization_id' => $provider->getKey(),
            'name' => 'Financial creation pricing '.Str::uuid(),
            'description' => 'Financial creation API test pricing',
            'currency' => 'CZK',
            'status' => PriceList::STATUS_ACTIVE,
            'current_version' => 1,
            'created_by_user_id' => $user->getKey(),
        ]);

        $priceListVersion =
            $priceList->versions()->create([
                'version_number' => 1,
                'status' => PriceListVersion::STATUS_ACTIVE,
                'valid_from' => '2026-07-01',
                'valid_until' => null,
                'change_reason' => 'Financial creation active pricing',
                'created_by_user_id' => $user->getKey(),
                'approved_by_user_id' => $user->getKey(),
                'approved_at' => '2026-06-30 10:00:00',
                'activated_at' => '2026-07-01 00:00:00',
            ]);

        $this->createPriceListItem(
            $priceListVersion,
            PriceListItem::CODE_DELIVERED_PARCELS,
            PriceListItem::UNIT_PARCEL,
            '4.2500',
            PriceListItem::QUANTITY_SOURCE_DELIVERED_PARCELS,
            1,
        );

        $this->createPriceListItem(
            $priceListVersion,
            PriceListItem::CODE_REDIRECTED_PARCELS,
            PriceListItem::UNIT_PARCEL,
            '1.0050',
            PriceListItem::QUANTITY_SOURCE_REDIRECTED_PARCELS,
            2,
        );

        $this->createPriceListItem(
            $priceListVersion,
            PriceListItem::CODE_UNDELIVERED_PARCELS,
            PriceListItem::UNIT_PARCEL,
            '7.0000',
            PriceListItem::QUANTITY_SOURCE_UNDELIVERED_PARCELS,
            3,
        );

        $this->createPriceListItem(
            $priceListVersion,
            PriceListItem::CODE_ACTUAL_KM,
            PriceListItem::UNIT_KM,
            '12.3456',
            PriceListItem::QUANTITY_SOURCE_ACTUAL_KM,
            4,
        );

        return [
            'customer' => $customer,
            'provider' => $provider,
            'relationship' => $relationship,
            'user' => $user,
            'dailyReport' => $dailyReport,
            'dailyReportVersion' => $dailyReportVersion,
            'priceList' => $priceList,
            'priceListVersion' => $priceListVersion,
        ];
    }

    private function createPriceListItem(
        PriceListVersion $version,
        string $code,
        string $unit,
        string $unitRate,
        string $quantitySource,
        int $position,
    ): PriceListItem {
        return $version->items()->create([
            'code' => $code,
            'description' => 'Creation API item '.$code,
            'calculation_method' => PriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
            'unit' => $unit,
            'unit_rate' => $unitRate,
            'currency' => 'CZK',
            'quantity_source' => $quantitySource,
            'rounding_scale' => 2,
            'rounding_method' => PriceListItem::ROUNDING_METHOD_HALF_UP,
            'position' => $position,
        ]);
    }

    private function createOrganization(
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => 'Creation API organization '.Str::uuid(),
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function grantManagePermission(
        User $user,
        Organization $organization,
    ): void {
        $registrar = app(PermissionRegistrar::class);

        $previousOrganizationId =
            $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(
                (int) $organization->getKey(),
            );

            $registrar->forgetCachedPermissions();

            $permission = Permission::findOrCreate(
                'compensation.manage',
                'web',
            );

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $user->givePermissionTo($permission);
        } finally {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $registrar->setPermissionsTeamId(
                $previousOrganizationId,
            );

            $registrar->forgetCachedPermissions();
        }
    }

    /**
     * @param array{
     *     customer: Organization,
     *     provider: Organization,
     *     relationship: OrganizationRelationship,
     *     user: User,
     *     dailyReport: DailyReport,
     *     dailyReportVersion: DailyReportVersion,
     *     priceList: PriceList,
     *     priceListVersion: PriceListVersion
     * } $foundation
     */
    private function createCalculationThroughApi(
        array $foundation,
    ): FinancialCalculation {
        Sanctum::actingAs(
            $foundation['user'],
        );

        $response =
            $this->withOrganization(
                $foundation['provider'],
            )
                ->postJson(
                    self::STORE_URL,
                    $this->payload(
                        $foundation,
                        'Review API initial calculation.',
                    ),
                )
                ->assertCreated();

        $publicId =
            $response->json(
                'data.public_id',
            );

        self::assertIsString(
            $publicId,
        );

        return FinancialCalculation::query()
            ->where(
                'public_id',
                $publicId,
            )
            ->sole();
    }

    /**
     * @param array{
     *     customer: Organization,
     *     provider: Organization,
     *     relationship: OrganizationRelationship,
     *     user: User,
     *     dailyReport: DailyReport,
     *     dailyReportVersion: DailyReportVersion,
     *     priceList: PriceList,
     *     priceListVersion: PriceListVersion
     * } $foundation
     */
    private function startReviewForApproval(
        array $foundation,
        FinancialCalculation $calculation,
        string $reason,
    ): void {
        Sanctum::actingAs(
            $foundation['user'],
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->reviewUrl($calculation),
                [
                    'reason' => $reason,
                ],
            )
            ->assertOk();
    }

    public function test_financial_calculation_cancellation_requires_authentication(): void
    {
        $this->postJson(
            self::STORE_URL.'/'.
            (string) Str::uuid().
            '/cancel',
            [],
        )->assertUnauthorized();

        $this->assertNoFinancialRecords();
    }

    public function test_financial_calculation_cancellation_requires_organization_context(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        app(OrganizationContext::class)->clear();

        Sanctum::actingAs($foundation['user']);

        $this->withHeader(
            'X-Organization-ID',
            '',
        )
            ->postJson(
                $this->cancellationUrl($calculation),
                [],
            )
            ->assertStatus(400);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute('approved_at'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_financial_calculation_cancellation_requires_compensation_manage_permission(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $unprivilegedUser = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        OrganizationMembership::query()->create([
            'organization_id' => $foundation['provider']->getKey(),
            'user_id' => $unprivilegedUser->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        Sanctum::actingAs($unprivilegedUser);

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->cancellationUrl($calculation),
                [
                    'reason' => 'Unauthorized cancellation.',
                ],
            )
            ->assertForbidden();

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_financial_calculation_cancellation_payload_is_validated(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->cancellationUrl($calculation),
                [
                    'reason' => str_repeat(
                        'x',
                        2001,
                    ),
                ],
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'reason',
            ]);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_financial_calculation_cancellation_from_calculated_is_atomic(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $inputSnapshotBefore =
            $calculation->getAttribute(
                'input_snapshot',
            );

        $subtotalBefore =
            $calculation->getAttribute(
                'subtotal_amount',
            );

        $totalBefore =
            $calculation->getAttribute(
                'total_amount',
            );

        $response =
            $this->withOrganization(
                $foundation['provider'],
            )
                ->postJson(
                    $this->cancellationUrl($calculation),
                    [
                        'reason' => (
                            '  Cancelled before review.  '
                        ),
                    ],
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Financial calculation cancelled.',
            )
            ->assertJsonPath(
                'data.public_id',
                (string) $calculation->getAttribute(
                    'public_id',
                ),
            )
            ->assertJsonPath(
                'data.status',
                FinancialCalculation::STATUS_CANCELLED,
            )
            ->assertJsonPath(
                'data.approved_at',
                null,
            )
            ->assertJsonPath(
                'data.closed_at',
                null,
            );

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CANCELLED,
            $calculation->getAttribute('status'),
        );

        self::assertSame(
            $inputSnapshotBefore,
            $calculation->getAttribute(
                'input_snapshot',
            ),
        );

        self::assertSame(
            $subtotalBefore,
            $calculation->getAttribute(
                'subtotal_amount',
            ),
        );

        self::assertSame(
            $totalBefore,
            $calculation->getAttribute(
                'total_amount',
            ),
        );

        self::assertNull(
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNull(
            $calculation->getAttribute('approved_at'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );

        $event =
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_CANCELLED,
                )
                ->sole();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $event->getAttribute('from_status'),
        );

        self::assertSame(
            FinancialCalculation::STATUS_CANCELLED,
            $event->getAttribute('to_status'),
        );

        self::assertSame(
            $foundation['user']->getKey(),
            $event->getAttribute(
                'acted_by_user_id',
            ),
        );

        self::assertSame(
            'Cancelled before review.',
            $event->getAttribute('reason'),
        );

        $metadata =
            $event->getAttribute('metadata');

        self::assertIsArray($metadata);

        self::assertSame(
            $foundation['user']->getKey(),
            $metadata['cancelled_by_user_id']
                ?? null,
        );
    }

    public function test_financial_calculation_cancellation_from_under_review_is_atomic(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->startReviewForApproval(
            $foundation,
            $calculation,
            'Review before cancellation.',
        );

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $calculation->getAttribute('status'),
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->cancellationUrl($calculation),
                [
                    'reason' => (
                        '  Cancelled during review.  '
                    ),
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Financial calculation cancelled.',
            )
            ->assertJsonPath(
                'data.status',
                FinancialCalculation::STATUS_CANCELLED,
            );

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CANCELLED,
            $calculation->getAttribute('status'),
        );

        self::assertNull(
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNull(
            $calculation->getAttribute('approved_at'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            3,
        );

        $event =
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_CANCELLED,
                )
                ->sole();

        self::assertSame(
            FinancialCalculation::STATUS_UNDER_REVIEW,
            $event->getAttribute('from_status'),
        );

        self::assertSame(
            FinancialCalculation::STATUS_CANCELLED,
            $event->getAttribute('to_status'),
        );

        self::assertSame(
            'Cancelled during review.',
            $event->getAttribute('reason'),
        );

        $metadata =
            $event->getAttribute('metadata');

        self::assertIsArray($metadata);

        self::assertSame(
            $foundation['user']->getKey(),
            $metadata['cancelled_by_user_id']
                ?? null,
        );
    }

    public function test_financial_calculation_cancellation_is_organization_scoped(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $otherOrganization =
            $this->createOrganization(
                Organization::TYPE_SUBCONTRACTOR,
            );

        OrganizationMembership::query()->create([
            'organization_id' => $otherOrganization->getKey(),
            'user_id' => $foundation['user']->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => now()->subDay(),
            'valid_until' => null,
        ]);

        $this->grantManagePermission(
            $foundation['user'],
            $otherOrganization,
        );

        Sanctum::actingAs(
            $foundation['user'],
        );

        $this->withOrganization(
            $otherOrganization,
        )
            ->postJson(
                $this->cancellationUrl($calculation),
                [
                    'reason' => (
                        'Cross-organization cancellation.'
                    ),
                ],
            )
            ->assertNotFound();

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CALCULATED,
            $calculation->getAttribute('status'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            1,
        );
    }

    public function test_financial_calculation_cancellation_rejects_approved_state(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $calculation,
            'Review before cancellation rejection.',
            'Approval before cancellation rejection.',
        );

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $calculation->getAttribute('status'),
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->cancellationUrl($calculation),
                [
                    'reason' => (
                        'Forbidden approved cancellation.'
                    ),
                ],
            )
            ->assertStatus(409);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_APPROVED,
            $calculation->getAttribute('status'),
        );

        self::assertNotNull(
            $calculation->getAttribute(
                'approved_by_user_id',
            ),
        );

        self::assertNotNull(
            $calculation->getAttribute('approved_at'),
        );

        self::assertNull(
            $calculation->getAttribute('closed_at'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            3,
        );

        self::assertSame(
            0,
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_CANCELLED,
                )
                ->count(),
        );
    }

    public function test_repeated_financial_calculation_cancellation_is_rejected(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->cancellationUrl($calculation),
                [
                    'reason' => 'Initial cancellation.',
                ],
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->cancellationUrl($calculation),
                [
                    'reason' => 'Repeated cancellation.',
                ],
            )
            ->assertStatus(409);

        $calculation->refresh();

        self::assertSame(
            FinancialCalculation::STATUS_CANCELLED,
            $calculation->getAttribute('status'),
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            2,
        );

        self::assertSame(
            1,
            FinancialCalculationEvent::query()
                ->where(
                    'financial_calculation_id',
                    $calculation->getKey(),
                )
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_CANCELLED,
                )
                ->count(),
        );
    }

    /**
     * @param array{
     *     customer: Organization,
     *     provider: Organization,
     *     relationship: OrganizationRelationship,
     *     user: User,
     *     dailyReport: DailyReport,
     *     dailyReportVersion: DailyReportVersion,
     *     priceList: PriceList,
     *     priceListVersion: PriceListVersion
     * } $foundation
     */
    private function approveCalculationForClosure(
        array $foundation,
        FinancialCalculation $calculation,
        string $reviewReason,
        string $approvalReason,
    ): void {
        $this->startReviewForApproval(
            $foundation,
            $calculation,
            $reviewReason,
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->approvalUrl($calculation),
                [
                    'reason' => $approvalReason,
                ],
            )
            ->assertOk();
    }

    private function approvalUrl(
        FinancialCalculation $calculation,
    ): string {
        return
            self::STORE_URL.'/'.
            (string) $calculation->getRouteKey().
            '/approve';
    }

    private function cancellationUrl(
        FinancialCalculation $calculation,
    ): string {
        return
            self::STORE_URL.'/'.
            (string) $calculation->getRouteKey().
            '/cancel';
    }

    private function closureUrl(
        FinancialCalculation $calculation,
    ): string {
        return
            self::STORE_URL.'/'.
            (string) $calculation->getRouteKey().
            '/close';
    }

    private function reviewUrl(
        FinancialCalculation $calculation,
    ): string {
        return
            self::STORE_URL.'/'.
            (string) $calculation->getRouteKey().
            '/review';
    }

    /**
     * @param array{
     *     dailyReport: DailyReport,
     *     priceList: PriceList
     * } $foundation
     * @return array<string, mixed>
     */
    private function payload(
        array $foundation,
        ?string $reason = null,
    ): array {
        return [
            'daily_report_public_id' => (
                (string) $foundation['dailyReport']->getAttribute(
                    'public_id',
                )
            ),
            'daily_report_version' => 4,
            'price_list_public_id' => (
                (string) $foundation['priceList']->getAttribute(
                    'public_id',
                )
            ),
            'price_list_version' => 1,
            'reason' => $reason,
        ];
    }

    private function withOrganization(
        Organization $organization,
    ): static {
        return $this->withHeader(
            'X-Organization-ID',
            (string) $organization->getKey(),
        );
    }

    private function assertNoFinancialRecords(): void
    {
        $this->assertDatabaseCount(
            'financial_calculations',
            0,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            0,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            0,
        );
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function assertNoInternalDatabaseIdentifiers(
        array $payload,
    ): void {
        $forbiddenKeys = [
            'id',
            'organization_id',
            'organization_relationship_id',
            'price_list_id',
            'price_list_version_id',
            'daily_report_id',
            'calculated_by_user_id',
            'approved_by_user_id',
            'supersedes_calculation_id',
            'financial_calculation_id',
            'price_list_item_id',
            'acted_by_user_id',
        ];

        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                self::assertNotContains(
                    $key,
                    $forbiddenKeys,
                    'Internal database identifier was exposed: '.$key,
                );
            }

            if (is_array($value)) {
                $this->assertNoInternalDatabaseIdentifiers(
                    $value,
                );
            }
        }
    }

    public function test_recalculation_payload_is_validated(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        Sanctum::actingAs($foundation['user']);

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->recalculationUrl(
                    $calculation,
                ),
                [
                    'daily_report_version' => 0,
                    'reason' => '   ',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'daily_report_version',
                'reason',
            ]);

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );
    }

    public function test_recalculation_requires_approved_source(): void
    {
        $foundation = $this->createFoundation(true);

        $calculation =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $nextVersion =
            $this->createApprovedAmendedDailyReportVersion(
                $foundation,
            );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->recalculationUrl(
                    $calculation,
                ),
                [
                    'daily_report_version' => $nextVersion,
                    'reason' => 'Premature recalculation.',
                ],
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Only an approved financial calculation can be recalculated.',
            );

        $calculation->refresh();

        self::assertTrue(
            $calculation->isCalculated(),
        );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );
    }

    public function test_it_recalculates_approved_financial_calculation_atomically(): void
    {
        $foundation = $this->createFoundation(true);

        $source =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $source,
            'Review before controlled recalculation.',
            'Approval before controlled recalculation.',
        );

        $source->refresh();

        self::assertTrue($source->isApproved());

        $sourceSnapshotBefore =
            $source->getAttribute(
                'input_snapshot',
            );

        $sourceTotalBefore =
            (string) $source->getAttribute(
                'total_amount',
            );

        $sourceApprovedAtBefore =
            $source->getAttribute(
                'approved_at',
            );

        $nextVersion =
            $this->createApprovedAmendedDailyReportVersion(
                $foundation,
            );

        $response =
            $this->withOrganization(
                $foundation['provider'],
            )
                ->postJson(
                    $this->recalculationUrl(
                        $source,
                    ),
                    [
                        'daily_report_version' => $nextVersion,
                        'reason' => (
                            '  Approved report amendment '.
                            'changed delivered parcels.  '
                        ),
                    ],
                );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Financial calculation recalculated.',
            )
            ->assertJsonPath(
                'data.status',
                FinancialCalculation::STATUS_CALCULATED,
            )
            ->assertJsonPath(
                'data.daily_report_version',
                $nextVersion,
            )
            ->assertJsonPath(
                'data.calculation_version',
                2,
            )
            ->assertJsonPath(
                'data.supersedes_public_id',
                (string) $source->getAttribute(
                    'public_id',
                ),
            );

        $recalculatedPublicId =
            $response->json('data.public_id');

        self::assertIsString(
            $recalculatedPublicId,
        );

        $recalculated =
            FinancialCalculation::query()
                ->where(
                    'public_id',
                    $recalculatedPublicId,
                )
                ->sole();

        $source->refresh();

        self::assertTrue($source->isApproved());

        self::assertSame(
            $sourceSnapshotBefore,
            $source->getAttribute(
                'input_snapshot',
            ),
        );

        self::assertSame(
            $sourceTotalBefore,
            (string) $source->getAttribute(
                'total_amount',
            ),
        );

        self::assertEquals(
            $sourceApprovedAtBefore,
            $source->getAttribute(
                'approved_at',
            ),
        );

        self::assertTrue(
            $recalculated->isCalculated(),
        );

        self::assertSame(
            2,
            $recalculated->getAttribute(
                'calculation_version',
            ),
        );

        self::assertSame(
            $source->getKey(),
            $recalculated->getAttribute(
                'supersedes_calculation_id',
            ),
        );

        self::assertSame(
            $nextVersion,
            $recalculated->getAttribute(
                'daily_report_version',
            ),
        );

        self::assertSame(
            4,
            $recalculated->lines()->count(),
        );

        $event =
            $recalculated->events()
                ->where(
                    'event_type',
                    FinancialCalculationEvent::TYPE_RECALCULATED,
                )
                ->sole();

        self::assertTrue(
            $event->isRecalculationEvent(),
        );

        self::assertSame(
            'Approved report amendment changed delivered parcels.',
            $event->getAttribute('reason'),
        );

        $metadata =
            $event->getAttribute('metadata');

        self::assertIsArray($metadata);

        self::assertSame(
            (string) $source->getAttribute(
                'public_id',
            ),
            $metadata['source_calculation_public_id']
                ?? null,
        );

        self::assertSame(
            1,
            $metadata['source_calculation_version']
                ?? null,
        );

        self::assertSame(
            2,
            $metadata['calculation_version']
                ?? null,
        );

        self::assertSame(
            4,
            $metadata['source_daily_report_version']
                ?? null,
        );

        self::assertSame(
            $nextVersion,
            $metadata['daily_report_version']
                ?? null,
        );

        self::assertSame(
            $sourceTotalBefore,
            $metadata['previous_total_amount']
                ?? null,
        );

        self::assertSame(
            (string) $recalculated->getAttribute(
                'total_amount',
            ),
            $metadata['total_amount']
                ?? null,
        );

        self::assertIsString(
            $metadata['financial_difference']
                ?? null,
        );

        self::assertNotSame(
            '0.00',
            $metadata['financial_difference']
                ?? null,
        );

        $changedInputs =
            $metadata['changed_input_values']
                ?? null;

        self::assertIsArray($changedInputs);

        self::assertArrayHasKey(
            'delivered_parcels',
            $changedInputs,
        );

        $this->assertDatabaseCount(
            'financial_calculations',
            2,
        );

        $this->assertDatabaseCount(
            'financial_calculation_lines',
            8,
        );

        $this->assertDatabaseCount(
            'financial_calculation_events',
            4,
        );
    }

    public function test_source_calculation_cannot_branch_into_multiple_recalculations(): void
    {
        $foundation = $this->createFoundation(true);

        $source =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $source,
            'Review before branch protection.',
            'Approval before branch protection.',
        );

        $nextVersion =
            $this->createApprovedAmendedDailyReportVersion(
                $foundation,
            );

        $payload = [
            'daily_report_version' => $nextVersion,
            'reason' => 'First controlled recalculation.',
        ];

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->recalculationUrl($source),
                $payload,
            )
            ->assertCreated();

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->recalculationUrl($source),
                [
                    'daily_report_version' => $nextVersion,
                    'reason' => 'Competing recalculation.',
                ],
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'The source financial calculation has already been superseded.',
            );

        $this->assertDatabaseCount(
            'financial_calculations',
            2,
        );

        self::assertSame(
            1,
            FinancialCalculation::query()
                ->where(
                    'supersedes_calculation_id',
                    $source->getKey(),
                )
                ->count(),
        );
    }

    public function test_recalculation_rejects_same_daily_report_version(): void
    {
        $foundation = $this->createFoundation(true);

        $source =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $source,
            'Review before same-version recalculation attempt.',
            'Approval before same-version recalculation attempt.',
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->recalculationUrl($source),
                [
                    'daily_report_version' => 4,
                    'reason' => 'Same report version must not be recalculated.',
                ],
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Recalculation requires a newer daily-report version than the source calculation.',
            );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );
    }

    public function test_recalculation_rejects_under_review_source(): void
    {
        $foundation = $this->createFoundation(true);

        $source =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->startReviewForApproval(
            $foundation,
            $source,
            'Review before rejected recalculation.',
        );

        $nextVersion =
            $this->createApprovedAmendedDailyReportVersion(
                $foundation,
            );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->recalculationUrl($source),
                [
                    'daily_report_version' => $nextVersion,
                    'reason' => 'Under-review source must not be recalculated.',
                ],
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Only an approved financial calculation can be recalculated.',
            );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );
    }

    public function test_recalculation_rejects_closed_source(): void
    {
        $foundation = $this->createFoundation(true);

        $source =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $source,
            'Review before closure.',
            'Approval before closure.',
        );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->closureUrl($source),
                [
                    'reason' => 'Close before recalculation attempt.',
                ],
            )
            ->assertOk();

        $nextVersion =
            $this->createApprovedAmendedDailyReportVersion(
                $foundation,
            );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->recalculationUrl($source),
                [
                    'daily_report_version' => $nextVersion,
                    'reason' => 'Closed source must remain final.',
                ],
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Only an approved financial calculation can be recalculated.',
            );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );
    }

    public function test_recalculation_rejects_cancelled_source(): void
    {
        $foundation = $this->createFoundation(true);

        $source =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->cancellationUrl($source),
                [
                    'reason' => 'Cancel before recalculation attempt.',
                ],
            )
            ->assertOk();

        $nextVersion =
            $this->createApprovedAmendedDailyReportVersion(
                $foundation,
            );

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                $this->recalculationUrl($source),
                [
                    'daily_report_version' => $nextVersion,
                    'reason' => 'Cancelled source must remain final.',
                ],
            )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Only an approved financial calculation can be recalculated.',
            );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );
    }

    public function test_recalculation_requires_authentication(): void
    {
        $this->postJson(
            self::STORE_URL.'/'.
            (string) Str::uuid().
            '/recalculate',
            [
                'daily_report_version' => 5,
                'reason' => 'Unauthorized recalculation.',
            ],
        )->assertUnauthorized();

        $this->assertDatabaseCount(
            'financial_calculations',
            0,
        );
    }

    public function test_recalculation_requires_organization_context(): void
    {
        $foundation = $this->createFoundation(true);

        Sanctum::actingAs($foundation['user']);

        $this->postJson(
            self::STORE_URL.'/'.
            (string) Str::uuid().
            '/recalculate',
            [
                'daily_report_version' => 5,
                'reason' => 'Missing organization context.',
            ],
        )->assertStatus(400);

        $this->assertDatabaseCount(
            'financial_calculations',
            0,
        );
    }

    public function test_recalculation_requires_compensation_manage_permission(): void
    {
        $foundation = $this->createFoundation(false);

        Sanctum::actingAs($foundation['user']);

        $this->withOrganization(
            $foundation['provider'],
        )
            ->postJson(
                self::STORE_URL.'/'.
                (string) Str::uuid().
                '/recalculate',
                [
                    'daily_report_version' => 5,
                    'reason' => 'Missing compensation permission.',
                ],
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'financial_calculations',
            0,
        );
    }

    public function test_recalculation_is_organization_scoped(): void
    {
        $foundation = $this->createFoundation(true);

        $source =
            $this->createCalculationThroughApi(
                $foundation,
            );

        $this->approveCalculationForClosure(
            $foundation,
            $source,
            'Review before organization-scope test.',
            'Approval before organization-scope test.',
        );

        $nextVersion =
            $this->createApprovedAmendedDailyReportVersion(
                $foundation,
            );

        $foreignFoundation =
            $this->createFoundation(true);

        Sanctum::actingAs(
            $foreignFoundation['user'],
        );

        $this->withOrganization(
            $foreignFoundation['provider'],
        )
            ->postJson(
                $this->recalculationUrl($source),
                [
                    'daily_report_version' => $nextVersion,
                    'reason' => 'Foreign organization attempt.',
                ],
            )
            ->assertNotFound();

        $source->refresh();

        self::assertTrue(
            $source->isApproved(),
        );

        self::assertSame(
            0,
            FinancialCalculation::query()
                ->where(
                    'supersedes_calculation_id',
                    $source->getKey(),
                )
                ->count(),
        );

        $this->assertDatabaseCount(
            'financial_calculations',
            1,
        );
    }

    private function recalculationUrl(
        FinancialCalculation $calculation,
    ): string {
        return
            self::STORE_URL.'/'.
            (string) $calculation->getRouteKey().
            '/recalculate';
    }

    /**
     * @param array{
     *     user: User,
     *     dailyReport: DailyReport,
     *     dailyReportVersion: DailyReportVersion
     * } $foundation
     */
    private function createApprovedAmendedDailyReportVersion(
        array $foundation,
    ): int {
        $sourceVersion =
            $foundation['dailyReportVersion'];

        $snapshot =
            $sourceVersion->getAttribute(
                'snapshot',
            );

        self::assertIsArray($snapshot);

        $sourceVersionNumber =
            (int) $sourceVersion->getAttribute(
                'version_number',
            );

        $nextVersion =
            $sourceVersionNumber + 1;

        $snapshot['current_version'] =
            $nextVersion;

        $snapshot['loaded_parcels'] =
            ((int) (
                $snapshot['loaded_parcels']
                ?? 0
            )) + 1;

        $snapshot['delivered_parcels'] =
            ((int) (
                $snapshot['delivered_parcels']
                ?? 0
            )) + 1;

        $amended =
            $sourceVersion->replicate();

        $amended->forceFill([
            'version_number' => $nextVersion,
            'snapshot' => $snapshot,
        ]);

        $amended->save();

        $foundation['dailyReport']
            ->forceFill([
                'current_version' => $nextVersion,
            ])
            ->save();

        return $nextVersion;
    }
}
