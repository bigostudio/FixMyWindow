<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15)->unique();
            $table->string('name', 150)->nullable();
            $table->string('email', 190)->nullable()->unique();
            $table->enum('type', ['b2c', 'b2b'])->default('b2c')->index();
            $table->string('gst_number', 20)->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
