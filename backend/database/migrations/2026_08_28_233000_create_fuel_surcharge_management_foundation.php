<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_surcharges', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('owner_organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();
            $table->foreignId('customer_relationship_id')
                ->constrained('organization_relationships')
                ->restrictOnDelete();
            $table->decimal('billing_rate_per_actual_km', 12, 4);
            $table->char('currency', 3)->default('CZK');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('lock_version')->default(1);
            $table->text('note')->nullable();
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(
                ['owner_organization_id', 'customer_relationship_id', 'valid_from'],
                'fuel_surcharges_owner_customer_period_index',
            );
            $table->index(
                ['owner_organization_id', 'status', 'valid_from'],
                'fuel_surcharges_owner_status_period_index',
            );
        });

        Schema::create('fuel_surcharge_recipient_rates', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('fuel_surcharge_id')
                ->constrained('fuel_surcharges')
                ->restrictOnDelete();
            $table->string('recipient_type', 32);
            $table->foreignId('driver_organization_assignment_id')
                ->nullable()
                ->constrained('driver_organization_assignments')
                ->restrictOnDelete();
            $table->foreignId('carrier_relationship_id')
                ->nullable()
                ->constrained('organization_relationships')
                ->restrictOnDelete();
            $table->decimal('payout_rate_per_actual_km', 12, 4);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->string('status', 32)->default('active');
            $table->text('note')->nullable();
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(
                ['fuel_surcharge_id', 'status', 'valid_from'],
                'fuel_surcharge_recipient_rates_surcharge_period_index',
            );
            $table->index(
                ['driver_organization_assignment_id', 'valid_from'],
                'fuel_surcharge_recipient_rates_driver_period_index',
            );
            $table->index(
                ['carrier_relationship_id', 'valid_from'],
                'fuel_surcharge_recipient_rates_carrier_period_index',
            );
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE fuel_surcharges
            ADD CONSTRAINT fuel_surcharges_values_check
            CHECK (
                billing_rate_per_actual_km >= 0
                AND currency = 'CZK'
                AND status IN ('draft', 'active', 'ended', 'cancelled')
                AND lock_version >= 1
                AND (valid_until IS NULL OR valid_until >= valid_from)
            )
            SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE fuel_surcharge_recipient_rates
            ADD CONSTRAINT fuel_surcharge_recipient_rates_values_check
            CHECK (
                payout_rate_per_actual_km >= 0
                AND status IN ('active', 'ended', 'cancelled')
                AND (valid_until IS NULL OR valid_until >= valid_from)
                AND (
                    (
                        recipient_type = 'own_driver'
                        AND driver_organization_assignment_id IS NOT NULL
                        AND carrier_relationship_id IS NULL
                    )
                    OR
                    (
                        recipient_type = 'external_carrier'
                        AND driver_organization_assignment_id IS NULL
                        AND carrier_relationship_id IS NOT NULL
                    )
                )
            )
            SQL);

        DB::unprepared(
            'DROP FUNCTION IF EXISTS guard_fuel_surcharge_overlap() CASCADE',
        );

        DB::unprepared(<<<'SQL'
            CREATE FUNCTION guard_fuel_surcharge_overlap()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $function$
            BEGIN
                IF NEW.status = 'active' AND EXISTS (
                    SELECT 1
                    FROM fuel_surcharges existing
                    WHERE existing.id IS DISTINCT FROM NEW.id
                      AND existing.owner_organization_id = NEW.owner_organization_id
                      AND existing.customer_relationship_id = NEW.customer_relationship_id
                      AND existing.status = 'active'
                      AND daterange(
                            existing.valid_from,
                            COALESCE(existing.valid_until, 'infinity'::date),
                            '[]'
                          ) && daterange(
                            NEW.valid_from,
                            COALESCE(NEW.valid_until, 'infinity'::date),
                            '[]'
                          )
                ) THEN
                    RAISE EXCEPTION
                        'Active fuel surcharge periods overlap for the customer relationship.'
                        USING ERRCODE = '23514';
                END IF;

                RETURN NEW;
            END;
            $function$;

            CREATE TRIGGER fuel_surcharges_overlap_guard
            BEFORE INSERT OR UPDATE ON fuel_surcharges
            FOR EACH ROW
            EXECUTE FUNCTION guard_fuel_surcharge_overlap();
            SQL);

        DB::unprepared(
            'DROP FUNCTION IF EXISTS guard_fuel_surcharge_recipient_overlap() CASCADE',
        );

        DB::unprepared(<<<'SQL'
            CREATE FUNCTION guard_fuel_surcharge_recipient_overlap()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $function$
            BEGIN
                IF NEW.status = 'active' AND EXISTS (
                    SELECT 1
                    FROM fuel_surcharge_recipient_rates existing
                    JOIN fuel_surcharges existing_surcharge
                      ON existing_surcharge.id = existing.fuel_surcharge_id
                    JOIN fuel_surcharges new_surcharge
                      ON new_surcharge.id = NEW.fuel_surcharge_id
                    WHERE existing.id IS DISTINCT FROM NEW.id
                      AND existing.status = 'active'
                      AND existing_surcharge.owner_organization_id =
                          new_surcharge.owner_organization_id
                      AND existing.recipient_type = NEW.recipient_type
                      AND (
                          (
                              NEW.recipient_type = 'own_driver'
                              AND existing.driver_organization_assignment_id =
                                  NEW.driver_organization_assignment_id
                          )
                          OR
                          (
                              NEW.recipient_type = 'external_carrier'
                              AND existing.carrier_relationship_id =
                                  NEW.carrier_relationship_id
                          )
                      )
                      AND daterange(
                            existing.valid_from,
                            COALESCE(existing.valid_until, 'infinity'::date),
                            '[]'
                          ) && daterange(
                            NEW.valid_from,
                            COALESCE(NEW.valid_until, 'infinity'::date),
                            '[]'
                          )
                ) THEN
                    RAISE EXCEPTION
                        'Active fuel surcharge recipient periods overlap.'
                        USING ERRCODE = '23514';
                END IF;

                RETURN NEW;
            END;
            $function$;

            CREATE TRIGGER fuel_surcharge_recipient_rates_overlap_guard
            BEFORE INSERT OR UPDATE ON fuel_surcharge_recipient_rates
            FOR EACH ROW
            EXECUTE FUNCTION guard_fuel_surcharge_recipient_overlap();
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(
                'DROP TRIGGER IF EXISTS fuel_surcharge_recipient_rates_overlap_guard '
                .'ON fuel_surcharge_recipient_rates',
            );
            DB::unprepared(
                'DROP FUNCTION IF EXISTS guard_fuel_surcharge_recipient_overlap()',
            );
            DB::unprepared(
                'DROP TRIGGER IF EXISTS fuel_surcharges_overlap_guard '
                .'ON fuel_surcharges',
            );
            DB::unprepared(
                'DROP FUNCTION IF EXISTS guard_fuel_surcharge_overlap()',
            );
        }

        Schema::dropIfExists('fuel_surcharge_recipient_rates');
        Schema::dropIfExists('fuel_surcharges');
    }
};
