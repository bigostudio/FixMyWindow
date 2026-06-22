<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_requests', function (Blueprint $table) {
            $table->string('req_id')->nullable()->after('phone');
            $table->string('otp_hash')->nullable()->change();
            $table->dateTime('expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('otp_requests', function (Blueprint $table) {
            $table->dropColumn('req_id');
            $table->string('otp_hash')->nullable(false)->change();
            $table->dateTime('expires_at')->nullable(false)->change();
        });
    }
};
