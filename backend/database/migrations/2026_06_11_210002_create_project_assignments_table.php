<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role', 30);           // snapshot of role at assignment time
            $table->unsignedBigInteger('assigned_by');

            $table->timestamps();

            $table->unique(['project_id', 'user_id'], 'uq_project_assignments');

            $table->foreign('project_id', 'fk_project_assignments_project_id')
                  ->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('user_id', 'fk_project_assignments_user_id')
                  ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by', 'fk_project_assignments_assigned_by')
                  ->references('id')->on('users');

            $table->index('project_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assignments');
    }
};
