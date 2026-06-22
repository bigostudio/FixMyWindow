<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'new',                     'label' => 'New',                     'sort_order' => 0],
            ['code' => 'survey_initiated',         'label' => 'Survey Initiated',        'sort_order' => 1],
            ['code' => 'survey_completed',         'label' => 'Survey Completed',        'sort_order' => 2],
            ['code' => 'measurement_initiated',    'label' => 'Measurement Initiated',   'sort_order' => 3],
            ['code' => 'measurement_completed',    'label' => 'Measurement Completed',   'sort_order' => 4],
            ['code' => 'quotation_generated',      'label' => 'Quotation Generated',     'sort_order' => 5],
            ['code' => 'quotation_approved',       'label' => 'Quotation Approved',      'sort_order' => 6],
            ['code' => 'installation_initiated',   'label' => 'Installation Initiated',  'sort_order' => 7],
            ['code' => 'installation_completed',   'label' => 'Installation Completed',  'sort_order' => 8],
            ['code' => 'quality_check_initiated',  'label' => 'Quality Check Initiated', 'sort_order' => 9],
            ['code' => 'quality_check_completed',  'label' => 'Quality Check Completed', 'sort_order' => 10],
            ['code' => 'handovered',               'label' => 'Handovered',              'sort_order' => 11],
            ['code' => 'on_hold',                  'label' => 'On Hold',                 'sort_order' => 12],
            ['code' => 'cancelled',                'label' => 'Cancelled',               'sort_order' => 13],
        ];

        foreach ($statuses as $status) {
            DB::table('project_statuses')->updateOrInsert(
                ['code' => $status['code']],
                array_merge($status, [
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
