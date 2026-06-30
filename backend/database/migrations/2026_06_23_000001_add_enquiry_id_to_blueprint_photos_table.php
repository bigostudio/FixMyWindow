<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('blueprint_photos', 'enquiry_id')) {
            // Existing rows have no enquiry_id and are invalid — clear them first
            DB::table('blueprint_photos')->truncate();

            Schema::table('blueprint_photos', function (Blueprint $table) {
                $table->foreignId('enquiry_id')
                    ->after('id')
                    ->constrained('enquiries')
                    ->cascadeOnDelete();

                $table->index('enquiry_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('blueprint_photos', function (Blueprint $table) {
            $table->dropForeign(['enquiry_id']);
            $table->dropColumn('enquiry_id');
        });
    }
};
