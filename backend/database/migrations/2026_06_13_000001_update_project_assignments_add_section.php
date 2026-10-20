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

        // ── 1. Drop FK on project_id if it exists (any name) ──────────────
        if (Schema::hasColumn($table, 'project_id')) {
            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME   = ?
                  AND COLUMN_NAME  = 'project_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database, $table]);

            Schema::table($table, function (Blueprint $t) use ($fks) {
                foreach ($fks as $fk) {
                    $t->dropForeign($fk->CONSTRAINT_NAME);
                }
            });

            // Drop any unique/regular index that includes project_id
            $indexes = DB::select("
                SELECT DISTINCT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME   = ?
                  AND COLUMN_NAME  = 'project_id'
            ", [$database, $table]);

            Schema::table($table, function (Blueprint $t) use ($indexes) {
                foreach ($indexes as $idx) {
                    if ($idx->INDEX_NAME === 'PRIMARY') {
                        continue;
                    }
                    try {
                        $t->dropIndex($idx->INDEX_NAME);
                    } catch (\Throwable) {
                        // already gone
                    }
                }
            });

            // ── 2. Rename project_id → enquiry_id ─────────────────────────
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('project_id', 'enquiry_id');
            });
        }

        // ── 3. Re-add correct FK on enquiry_id (idempotent) ───────────────
        $enquiryFkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA         = ?
              AND TABLE_NAME           = ?
              AND COLUMN_NAME          = 'enquiry_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);

        if (! $enquiryFkExists) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('enquiry_id', 'fk_project_assignments_enquiry_id')
                  ->references('id')->on('enquiries')->cascadeOnDelete();
                $t->index('enquiry_id', 'project_assignments_enquiry_id_index');
            });
        }

        // ── 4. Add assignment_section column if not already there ──────────
        if (! Schema::hasColumn($table, 'assignment_section')) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('assignment_section', 30)->after('role');
            });
        }

        // ── 5. Drop old unique, add new one ────────────────────────────────
        $oldUnique = DB::selectOne("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = ?
              AND INDEX_NAME   = 'uq_project_assignments'
            LIMIT 1
        ", [$database, $table]);

        $newUnique = DB::selectOne("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = ?
              AND INDEX_NAME   = 'uq_project_assignments_section'
            LIMIT 1
        ", [$database, $table]);

        Schema::table($table, function (Blueprint $t) use ($oldUnique, $newUnique) {
            if ($oldUnique) {
                $t->dropUnique('uq_project_assignments');
            }
            if (! $newUnique) {
                $t->unique(
                    ['enquiry_id', 'user_id', 'assignment_section'],
                    'uq_project_assignments_section'
                );
            }
        });
    }

    public function down(): void
    {
        $table    = 'project_assignments';
        $database = DB::getDatabaseName();

        Schema::table($table, function (Blueprint $t) {
            $t->dropForeign('fk_project_assignments_enquiry_id');
            $t->dropUnique('uq_project_assignments_section');
            $t->dropIndex('project_assignments_enquiry_id_index');
            $t->dropColumn('assignment_section');
            $t->renameColumn('enquiry_id', 'project_id');
        });

        Schema::table($table, function (Blueprint $t) {
            $t->foreign('project_id', 'fk_project_assignments_project_id')
              ->references('id')->on('projects')->cascadeOnDelete();
            $t->index('project_id');
            $t->unique(['project_id', 'user_id'], 'uq_project_assignments');
        });
    }
};
