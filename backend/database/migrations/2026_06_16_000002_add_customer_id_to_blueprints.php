<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blueprints', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('id');
            $table->foreign('customer_id', 'fk_blueprints_customer_id')
                  ->references('id')->on('customers')->nullOnDelete();
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropForeign('fk_blueprints_customer_id');
            $table->dropIndex('customer_id');
            $table->dropColumn('customer_id');
        });
    }
};
