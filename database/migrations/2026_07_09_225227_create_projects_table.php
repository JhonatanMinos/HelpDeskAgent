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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->text('Initial_requirements')->nullable();
            $table->enum('status', ['propuesto', 'activo', 'pausado', 'completado'])->default('propuesto');
            $table->date('estimated_date_delivery')->nullable();
            $table->timestamps();
        });

        Schema::create('boards_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Backlog, En progreso, Revisión, Hecho
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boards_column_id')->constrained('boards_columns')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['baja', 'media', 'alta'])->default('media');
            $table->unsignedSmallInteger('order')->default(0); // posición dentro de la columna
            $table->date('limit_date')->nullable();
            $table->timestamps();

            $table->index(['boards_column_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('boards_columns');
        Schema::dropIfExists('projects');
    }
};
