<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();

            // Booking ID — generated on confirm, hence nullable at column level
            $table->string('enquiry_number', 20)->nullable()->unique();

            // Core references — explicit FK names to avoid MySQL auto-naming collisions
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('service_id');

            // Booking dimensions
            $table->enum('type', ['b2c', 'b2b'])->default('b2c')->index();
            $table->string('city', 100)->index();
            $table->enum('property_type', ['home', 'commercial'])->index();
            $table->enum('material_type', ['upvc', 'aluminium', 'facade'])->index();
            $table->enum('inspection_type', ['expert', 'self'])->default('expert')->index();
            $table->enum('status', ['new', 'assigned', 'in_progress', 'completed', 'cancelled'])
                  ->default('new')->index();

            // Service location
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('address');

            // Financials — Phase 1 fixed fee; payment gateway Cycle 2
            $table->integer('inspection_fee')->default(1000);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending')->index();
            $table->integer('payment_amount')->nullable();
            $table->string('payment_ref', 100)->nullable();
            $table->string('receipt_url', 255)->nullable();

            // Billing info captured at booking time
            $table->string('billing_name', 150);
            $table->string('billing_poc', 150)->nullable();
            $table->string('billing_gst', 20)->nullable();
            $table->string('billing_phone', 15);
            $table->string('billing_email', 190);
            $table->text('billing_address');

            // B2B placeholder — null for B2C bookings
            $table->unsignedBigInteger('blueprint_id')->nullable()->index();

            // Cycle 2 placeholder
            $table->unsignedBigInteger('quotation_id')->nullable()->index();

            $table->date('booking_date')->index();
            $table->timestamps();

            $table->index('created_at');

            // FK constraints with explicit names
            $table->foreign('customer_id', 'fk_enquiries_customer_id')
                  ->references('id')->on('customers');
            $table->foreign('service_id', 'fk_enquiries_service_id')
                  ->references('id')->on('services');

            $table->index('customer_id', 'idx_enquiries_customer_id');
            $table->index('service_id', 'idx_enquiries_service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
