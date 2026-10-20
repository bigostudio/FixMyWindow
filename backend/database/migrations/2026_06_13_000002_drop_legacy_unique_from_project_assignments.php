<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table    = 'project_assignments';
        $database = DB::getDatabaseName();

        // Drop every unique index on (enquiry_id, user_id) — the old 2-column
        // constraint that blocks the same user appearing in multiple sections.
        $indexes = DB::select("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA  = ?
              AND TABLE_NAME    = ?
              AND NON_UNIQUE    = 0
              AND INDEX_NAME   != 'PRIMARY'
              AND INDEX_NAME   != 'uq_project_assignments_section'
        ", [$database, $table]);

        $dropped = [];
        Schema::table($table, function (Blueprint $t) use ($indexes, &$dropped) {
            foreach ($indexes as $idx) {
                if (in_array($idx->INDEX_NAME, $dropped, true)) {
                    continue;
                }
                try {
                    $t->dropIndex($idx->INDEX_NAME);
                    $dropped[] = $idx->INDEX_NAME;
                } catch (\Throwable) {
                    // already gone
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_assignments', function (Blueprint $table) {
            $table->unique(['enquiry_id', 'user_id'], 'uq_enquiry_assignments');
        });
    }
};
