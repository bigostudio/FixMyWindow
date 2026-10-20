<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_cities', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id');
            $table->string('city', 100);

            $table->unique(['service_id', 'city']);

            // Explicit constraint name avoids MySQL FK naming collisions on tables without a PK
            $table->foreign('service_id', 'fk_service_cities_service_id')
                  ->references('id')
                  ->on('services');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_cities');
    }
};
