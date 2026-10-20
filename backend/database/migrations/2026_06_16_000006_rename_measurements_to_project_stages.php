<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('measurements', 'project_stages');

        Schema::table('project_stages', function (Blueprint $table) {
            $table->string('type', 20)->default('measurement')->after('enquiry_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('project_stages', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });

        Schema::rename('project_stages', 'measurements');
    }
};
