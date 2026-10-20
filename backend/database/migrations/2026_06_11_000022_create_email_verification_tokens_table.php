<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');          // App\Models\User | App\Models\Customer
            $table->unsignedBigInteger('model_id');
            $table->string('email', 190)->index();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_tokens');
    }
};
