<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ExternalDriverIdentifierFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_has_nullable_unique_external_driver_identifier(): void
    {
        self::assertTrue(
            Schema::hasColumn(
                'drivers',
                'external_driver_id',
            ),
        );

        $firstUser = User::factory()->create();

        $first = Driver::query()->create([
            'user_id' => (int) $firstUser->getKey(),
            'first_name' => 'První',
            'last_name' => 'Řidič',
            'external_driver_id' => '33102',
            'phone' => null,
            'email' => null,
            'license_number' => null,
            'license_category' => null,
            'active' => true,
        ]);

        self::assertSame(
            '33102',
            $first->getAttribute(
                'external_driver_id',
            ),
        );

        $secondUser = User::factory()->create();

        try {
            Driver::query()->create([
                'user_id' => (int) $secondUser->getKey(),
                'first_name' => 'Druhý',
                'last_name' => 'Řidič',
                'external_driver_id' => '33102',
                'phone' => null,
                'email' => null,
                'license_number' => null,
                'license_category' => null,
                'active' => true,
            ]);

            self::fail(
                'Duplicate external driver identifier was accepted.',
            );
        } catch (QueryException) {
            // Expected: the unique constraint rejects the duplicate identifier.
        }
    }

    public function test_driver_admin_ui_exposes_external_identifier_for_create_edit_list_and_search(): void
    {
        $this->get('/carriers')
            ->assertOk()
            ->assertSee(
                'ID řidiče (Zásilkovna)',
            )
            ->assertSee(
                'name="external_driver_id"',
                false,
            )
            ->assertSee(
                'edit-driver-external-id',
                false,
            )
            ->assertSee(
                'ID Zásilkovna',
            )
            ->assertSee(
                'driver.external_driver_id',
                false,
            );
    }
}
