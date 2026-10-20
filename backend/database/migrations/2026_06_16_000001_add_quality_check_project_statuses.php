<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_statuses')
            ->where('sort_order', '>=', 9)
            ->increment('sort_order', 2);

        DB::table('project_statuses')->insert([
            [
                'code'       => 'quality_check_initiated',
                'label'      => 'Quality Check Initiated',
                'sort_order' => 9,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code'       => 'quality_check_completed',
                'label'      => 'Quality Check Completed',
                'sort_order' => 10,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_statuses')
            ->whereIn('code', ['quality_check_initiated', 'quality_check_completed'])
            ->delete();

        DB::table('project_statuses')
            ->where('sort_order', '>=', 9)
            ->decrement('sort_order', 2);
    }
};
