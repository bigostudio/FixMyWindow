<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprints', function (Blueprint $table) {
            $table->id();
            $table->string('society_name', 190)->nullable();
            $table->unsignedInteger('tower_count')->default(0);
            $table->unsignedInteger('floors_per_tower')->default(0);
            $table->unsignedInteger('flats_per_floor')->default(0);
            $table->boolean('include_ground_floor')->default(false);
            $table->unsignedInteger('parking_floors')->default(0);
            $table->unsignedInteger('office_floors')->default(0);
            $table->json('towers')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprints');
    }
};
