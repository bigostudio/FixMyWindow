<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Expand ENUM to include both old and new values so UPDATE can write new values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin','ops_admin','project_manager','surveyor','installer','qc_engineer','accounts','builder_fabricator','ops_manager','supervisor','technician') NOT NULL");

        // Step 2: Migrate existing data to new role values
        DB::statement("UPDATE users SET role = 'ops_admin'  WHERE role IN ('super_admin')");
        DB::statement("UPDATE users SET role = 'supervisor' WHERE role IN ('project_manager')");
        DB::statement("UPDATE users SET role = 'technician' WHERE role IN ('surveyor', 'installer', 'qc_engineer', 'accounts', 'builder_fabricator')");

        // Step 3: Narrow ENUM to new values only
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ops_admin', 'ops_manager', 'supervisor', 'technician') NOT NULL");

        // Step 4: Update project_timeline actor_type
        DB::statement("ALTER TABLE project_timeline MODIFY COLUMN actor_type ENUM('system', 'ops_admin', 'ops_manager', 'supervisor', 'technician', 'customer') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE project_timeline MODIFY COLUMN actor_type ENUM('system', 'admin', 'ops_admin', 'project_manager', 'surveyor', 'installer', 'qc_engineer', 'customer') NOT NULL");

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'ops_admin', 'project_manager', 'surveyor', 'installer', 'qc_engineer', 'accounts', 'builder_fabricator') NOT NULL");
    }
};
