<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropForeign('fk_enquiries_customer_id');

            $table->foreign('customer_id', 'fk_enquiries_customer_id')
                  ->references('id')->on('customers')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropForeign('fk_enquiries_customer_id');

            $table->foreign('customer_id', 'fk_enquiries_customer_id')
                  ->references('id')->on('customers');
        });
    }
};
