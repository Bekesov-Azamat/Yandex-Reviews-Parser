<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->index(['organization_id', 'rating']);
            $table->index(['organization_id', 'created_at']);
        });

        Schema::table('parse_attempts', function (Blueprint $table): void {
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('parse_attempts', function (Blueprint $table): void {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'rating']);
            $table->dropIndex(['organization_id', 'created_at']);
        });
    }
};
