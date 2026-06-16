<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_stages', function (Blueprint $table) {
            $table->dropColumn(['type', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::table('project_stages', function (Blueprint $table) {
            $table->string('type', 20)->default('measurement')->after('enquiry_id');
            $table->string('approval_status', 20)->default('pending')->after('towers');
            $table->index('type');
            $table->index('approval_status');
        });
    }
};
