<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('projects');

        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('enquiry_id')->unique();
            $table->enum('status', ['on_track', 'at_risk', 'delayed', 'due_to_dependency', 'completed', 'cancelled'])
                  ->default('on_track')->index();
            $table->string('delay_reason', 50)->nullable();

            $table->unsignedInteger('total_units')->default(0);
            $table->unsignedInteger('units_completed')->default(0);
            $table->decimal('progress_percent', 5, 2)->default(0.00);

            $table->json('project_details')->nullable();
            $table->json('material_status')->nullable();
            $table->json('quality_checks')->nullable();

            $table->date('final_completion_date')->nullable();
            $table->timestamps();

            $table->foreign('enquiry_id', 'fk_projects_enquiry_id')
                  ->references('id')->on('enquiries')->cascadeOnDelete();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
