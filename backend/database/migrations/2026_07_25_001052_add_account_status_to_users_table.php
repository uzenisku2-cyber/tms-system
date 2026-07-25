<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('status', 32)->default('active');
            $table->timestamp('status_changed_at')->nullable();

            $table->index('status', 'users_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropIndex('users_status_index');
            $table->dropColumn([
                'status',
                'status_changed_at',
            ]);
        });
    }
};
