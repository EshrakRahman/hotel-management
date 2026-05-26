<?php

use App\Enums\PromotionsDiscountType;
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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('promo_code');
            $table->string('discount_type')->default(PromotionsDiscountType::PERCENTAGE);
            $table->decimal('discount_value', 5, 2);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
