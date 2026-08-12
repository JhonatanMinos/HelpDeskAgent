<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->json('enabled_modules')->nullable()->after('is_personal');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('team_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('enabled_modules');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
    }
};
