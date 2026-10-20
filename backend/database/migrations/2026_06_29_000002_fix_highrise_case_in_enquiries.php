<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM is case-insensitive so we can't hold both 'highrise' and 'Highrise'.
        // Drop to VARCHAR first, fix the data, then restore ENUM with correct case.
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN property_type VARCHAR(50) NOT NULL");
        DB::statement("UPDATE enquiries SET property_type = 'Highrise' WHERE property_type = 'highrise'");
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN property_type ENUM('home', 'Highrise') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN property_type VARCHAR(50) NOT NULL");
        DB::statement("UPDATE enquiries SET property_type = 'highrise' WHERE property_type = 'Highrise'");
        DB::statement("ALTER TABLE enquiries MODIFY COLUMN property_type ENUM('home', 'highrise') NOT NULL");
    }
};
