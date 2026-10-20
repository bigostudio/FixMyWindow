<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained('enquiries')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('uploaded_by_type', 20); // 'customer' or 'admin'
            $table->unsignedBigInteger('uploaded_by_id')->nullable();
            $table->timestamps();

            $table->index('enquiry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_photos');
    }
};
