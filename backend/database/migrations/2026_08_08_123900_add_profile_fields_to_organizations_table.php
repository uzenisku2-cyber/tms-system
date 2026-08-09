<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'organizations',
            static function (Blueprint $table): void {
                $table->string('registration_number', 32)
                    ->nullable()
                    ->after('status');

                $table->string('vat_number', 32)
                    ->nullable()
                    ->after('registration_number');

                $table->string('street')
                    ->nullable()
                    ->after('vat_number');

                $table->string('city', 100)
                    ->nullable()
                    ->after('street');

                $table->string('postal_code', 32)
                    ->nullable()
                    ->after('city');

                $table->char('country_code', 2)
                    ->nullable()
                    ->after('postal_code');

                $table->string('contact_email')
                    ->nullable()
                    ->after('country_code');

                $table->string('contact_phone', 64)
                    ->nullable()
                    ->after('contact_email');
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'organizations',
            static function (Blueprint $table): void {
                $table->dropColumn([
                    'registration_number',
                    'vat_number',
                    'street',
                    'city',
                    'postal_code',
                    'country_code',
                    'contact_email',
                    'contact_phone',
                ]);
            },
        );
    }
};