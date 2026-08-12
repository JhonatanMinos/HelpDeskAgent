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
        Schema::table('stores', function (Blueprint $table) {
            $table->enum('status', ['online', 'offline', 'unknown'])
                ->default('unknown')
                ->after('base_ip'); // o el campo que tengas antes
            $table->timestamp('last_seen_at')->after('lng')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('last_seen_at');
        });
    }
};
