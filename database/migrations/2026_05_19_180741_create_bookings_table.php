<?php

use App\Enums\BookingStatus;
use App\Enums\paymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->text('booking_ref');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('hotel_id')->constrained();
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_amount', 10);
            $table->decimal('tax_amount', 10);
            $table->decimal('platform_fee', 10);
            $table->decimal('total_service_amount', 10);
            $table->decimal('cancellation_penalty', 10)->default(0);
            $table->string('status')->default(BookingStatus::PENDING);
            $table->string('payment_status')->default(paymentStatus::PENDING);
            $table->string('special_request')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
