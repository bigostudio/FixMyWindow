<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_timeline', function (Blueprint $table) {
            // FK deferred from original migration — projects table now exists
            $table->foreign('project_id', 'fk_project_timeline_project_id')
                  ->references('id')->on('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_timeline', function (Blueprint $table) {
            $table->dropForeign('fk_project_timeline_project_id');
        });
    }
};
