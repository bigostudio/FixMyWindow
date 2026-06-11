<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_timeline', function (Blueprint $table) {
            $table->dropForeign('fk_project_timeline_enquiry_id');

            $table->foreign('enquiry_id', 'fk_project_timeline_enquiry_id')
                  ->references('id')->on('enquiries')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_timeline', function (Blueprint $table) {
            $table->dropForeign('fk_project_timeline_enquiry_id');

            $table->foreign('enquiry_id', 'fk_project_timeline_enquiry_id')
                  ->references('id')->on('enquiries');
        });
    }
};
