<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_requests', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15);
            $table->string('otp_hash');
            $table->dateTime('expires_at');
            $table->dateTime('consumed_at')->nullable();
            $table->tinyInteger('failed_attempts')->unsigned()->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_requests');
    }
};
