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
        Schema::create('ping_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('pingable');
            $table->string('ip', 45);
            $table->string('device_label', 60)->nullable();
            $table->boolean('is_online');
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['pingable_type', 'pingable_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ping_logs');
    }
};
