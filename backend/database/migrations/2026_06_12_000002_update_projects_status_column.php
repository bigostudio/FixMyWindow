<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the ENUM constraint and change to VARCHAR so labels can be
        // managed in project_statuses table without ALTER TABLE each time.
        // MySQL preserves the existing index through MODIFY COLUMN, so no need to re-add it.
        DB::statement("ALTER TABLE projects MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('on_track','at_risk','delayed','due_to_dependency','completed','cancelled') NOT NULL DEFAULT 'on_track'");
    }
};
