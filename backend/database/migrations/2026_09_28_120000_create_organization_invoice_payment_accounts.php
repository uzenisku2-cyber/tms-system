<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_invoice_payment_accounts', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('iban', 34);
            $table->string('account_holder', 255);
            $table->string('source_reference', 255);
            $table->text('reason');
            $table->timestamp('confirmed_at');
            $table->foreignId('confirmed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'revision'], 'organization_invoice_payment_revision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invoice_payment_accounts');
    }
};
