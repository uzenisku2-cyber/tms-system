<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_import_row_corrections', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('fuel_import_row_id')->constrained('fuel_import_rows')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->jsonb('original_payload');
            $table->jsonb('corrected_payload');
            $table->text('reason');
            $table->foreignId('corrected_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['fuel_import_row_id', 'revision'], 'fuel_import_row_corrections_revision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_import_row_corrections');
    }
};
