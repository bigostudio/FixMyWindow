<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: expand ENUM to include both values so existing rows stay valid
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN property_type ENUM('home', 'commercial', 'Highrise') NOT NULL");
        // Step 2: migrate existing data
        DB::statement("UPDATE enquiries SET property_type = 'Highrise' WHERE property_type = 'commercial'");
        // Step 3: remove old value from ENUM
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN property_type ENUM('home', 'Highrise') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN property_type ENUM('home', 'Highrise', 'commercial') NOT NULL");
        DB::statement("UPDATE enquiries SET property_type = 'commercial' WHERE property_type = 'Highrise'");
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN property_type ENUM('home', 'commercial') NOT NULL");
    }
};
