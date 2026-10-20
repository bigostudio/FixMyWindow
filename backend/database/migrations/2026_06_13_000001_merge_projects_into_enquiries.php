<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Add project columns to enquiries ──────────────────────
        // Change status from ENUM to VARCHAR so it uses the project_statuses table
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'new'");

        Schema::table('enquiries', function (Blueprint $table) {
            $table->string('delay_reason', 50)->nullable()->after('status');
            $table->unsignedInteger('total_units')->default(0)->after('delay_reason');
            $table->unsignedInteger('units_completed')->default(0)->after('total_units');
            $table->decimal('progress_percent', 5, 2)->default(0.00)->after('units_completed');
            $table->json('project_details')->nullable()->after('progress_percent');
            $table->json('material_status')->nullable()->after('project_details');
            $table->json('quality_checks')->nullable()->after('material_status');
            $table->date('final_completion_date')->nullable()->after('quality_checks');
        });

        // ── Step 2: Copy project data into enquiries ───────────────────────
        // status is intentionally excluded — old project execution statuses
        // (on_track, at_risk, etc.) are not valid ProjectStatus enum values.
        // Enquiries retain their existing lifecycle status.
        DB::statement("
            UPDATE enquiries e
            INNER JOIN projects p ON p.enquiry_id = e.id
            SET
                e.delay_reason          = p.delay_reason,
                e.total_units           = p.total_units,
                e.units_completed       = p.units_completed,
                e.progress_percent      = p.progress_percent,
                e.project_details       = p.project_details,
                e.material_status       = p.material_status,
                e.quality_checks        = p.quality_checks,
                e.final_completion_date = p.final_completion_date
        ");

        // ── Step 3: Update project_assignments — project_id → enquiry_id ──
        Schema::table('project_assignments', function (Blueprint $table) {
            $table->dropForeign('fk_project_assignments_project_id');
            $table->dropUnique('uq_project_assignments');
            $table->dropIndex('project_assignments_project_id_index');
        });

        Schema::table('project_assignments', function (Blueprint $table) {
            $table->renameColumn('project_id', 'enquiry_id');
        });

        Schema::table('project_assignments', function (Blueprint $table) {
            $table->unique(['enquiry_id', 'user_id'], 'uq_enquiry_assignments');
            $table->index('enquiry_id', 'idx_project_assignments_enquiry_id');
            $table->foreign('enquiry_id', 'fk_project_assignments_enquiry_id')
                  ->references('id')->on('enquiries')->cascadeOnDelete();
        });

        // ── Step 4: Update project_timeline — drop project_id ─────────────
        Schema::table('project_timeline', function (Blueprint $table) {
            $table->dropForeign('fk_project_timeline_project_id');
            $table->dropIndex('idx_project_timeline_project_id');
            $table->dropIndex('idx_project_timeline_project_created');
            $table->dropColumn('project_id');
        });

        // ── Step 5: Drop projects table ────────────────────────────────────
        Schema::dropIfExists('projects');
    }

    public function down(): void
    {
        // Recreate projects table
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enquiry_id')->unique();
            $table->string('status', 50)->default('new')->index();
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

        // Restore project_timeline.project_id
        Schema::table('project_timeline', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('id');
            $table->index('project_id', 'idx_project_timeline_project_id');
            $table->index(['project_id', 'created_at'], 'idx_project_timeline_project_created');
            $table->foreign('project_id', 'fk_project_timeline_project_id')
                  ->references('id')->on('projects')->nullOnDelete();
        });

        // Restore project_assignments
        Schema::table('project_assignments', function (Blueprint $table) {
            $table->dropForeign('fk_project_assignments_enquiry_id');
            $table->dropUnique('uq_enquiry_assignments');
            $table->dropIndex('idx_project_assignments_enquiry_id');
        });

        Schema::table('project_assignments', function (Blueprint $table) {
            $table->renameColumn('enquiry_id', 'project_id');
        });

        Schema::table('project_assignments', function (Blueprint $table) {
            $table->unique(['project_id', 'user_id'], 'uq_project_assignments');
            $table->index('project_id');
            $table->foreign('project_id', 'fk_project_assignments_project_id')
                  ->references('id')->on('projects')->cascadeOnDelete();
        });

        // Remove project columns from enquiries
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropColumn([
                'delay_reason', 'total_units', 'units_completed',
                'progress_percent', 'project_details', 'material_status',
                'quality_checks', 'final_completion_date',
            ]);
        });

        DB::statement("ALTER TABLE enquiries MODIFY COLUMN status ENUM('new','assigned','in_progress','completed','cancelled') NOT NULL DEFAULT 'new'");
    }
};
