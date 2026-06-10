<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('material', ['upvc', 'aluminium', 'facade'])->index();
            $table->boolean('is_active')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('category_id', 'fk_services_category_id')
                  ->references('id')->on('service_categories');
            $table->index('category_id', 'idx_services_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
