<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnquiryCounterSeeder extends Seeder
{
    public function run(): void
    {
        // Single-row counter — insert only if the row doesn't exist
        DB::table('enquiry_counter')->insertOrIgnore([
            'id'         => 1,
            'last_value' => 0,
            'updated_at' => now(),
        ]);
    }
}
