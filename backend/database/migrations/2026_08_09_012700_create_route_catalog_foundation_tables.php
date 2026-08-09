<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('route_uid')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('route_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->string('route_number', 64);
            $table->string('route_name');
            $table->string('area')->nullable();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('change_type', 32)->default('created');
            $table->text('change_note')->nullable();
            $table->timestamps();

            $table->unique(['route_id', 'valid_from']);
            $table->index('route_number');
            $table->index(['route_id', 'valid_to']);
            $table->index(['valid_from', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_versions');
        Schema::dropIfExists('routes');
    }
};
