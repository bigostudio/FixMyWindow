<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('enquiry_id');
            $table->unsignedBigInteger('surveyor_id')->nullable();

            // null = not yet decided
            $table->enum('outcome', ['go', 'hold', 'no_go'])->nullable()->index();

            // Section A — project & client details (future)
            $table->json('client_details')->nullable();

            // Section B — Go/No-Go 10-item matrix
            $table->json('gonogo_matrix')->nullable();

            // Section C — installation feasibility (8 items)
            $table->json('feasibility')->nullable();

            // Section D — per-aperture opening survey (future)
            $table->json('opening_data')->nullable();

            // Section E — risk register
            $table->json('risk_register')->nullable();

            $table->timestamps();

            $table->foreign('enquiry_id', 'fk_surveys_enquiry_id')
                  ->references('id')->on('enquiries')->cascadeOnDelete();

            $table->foreign('surveyor_id', 'fk_surveys_surveyor_id')
                  ->references('id')->on('users')->nullOnDelete();

            $table->index('enquiry_id');
            $table->index('surveyor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
