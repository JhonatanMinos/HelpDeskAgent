<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**-
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('store_code')->unique(); // Ej: T0025
            $table->string('name');
            $table->enum('company', [16, 24]); // Segmento de empresa
            $table->string('base_ip'); // IP base como string para calculo dinamico
            // Informacion de Tienda
            $table->string('address');           // direccion
            $table->string('neighborhood');      // colonia
            $table->string('city');              // municipio
            $table->string('postal_code', 10);   // codigo_postal
            $table->string('state');
            // Pintar en Map
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
