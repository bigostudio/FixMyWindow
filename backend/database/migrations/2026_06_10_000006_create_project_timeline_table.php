<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_timeline', function (Blueprint $table) {
            $table->id();

            // project_id FK deferred — projects table not yet built; constraint added in a later migration
            $table->unsignedBigInteger('project_id')->nullable();

            // enquiry_id — explicit FK name to avoid MySQL auto-naming collisions
            $table->unsignedBigInteger('enquiry_id')->nullable();

            // String, not enum — too many values; new statuses can be added without ALTER TABLE
            $table->string('status', 80)->index();
            $table->text('description')->nullable();

            $table->enum('actor_type', [
                'system', 'admin', 'ops_admin', 'project_manager',
                'surveyor', 'installer', 'qc_engineer', 'customer',
            ])->index();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 150);

            // Append-only: created_at only; no updated_at
            $table->timestamp('created_at');

            // FK with explicit name
            $table->foreign('enquiry_id', 'fk_project_timeline_enquiry_id')
                  ->references('id')->on('enquiries');

            $table->index('project_id', 'idx_project_timeline_project_id');
            $table->index('enquiry_id', 'idx_project_timeline_enquiry_id');
            $table->index(['project_id', 'created_at'], 'idx_project_timeline_project_created');
            $table->index(['enquiry_id', 'created_at'], 'idx_project_timeline_enquiry_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_timeline');
    }
};
