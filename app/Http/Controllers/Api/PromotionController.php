<?php

namespace App\Http\Controllers\Api;

use App\Enums\PromotionsDiscountType;
use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    /**
     * Verify a promotion code and calculate the potential discount.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'promo_code' => ['required', 'string'],
            'room_subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $promoCode = $validated['promo_code'];
        $roomSubtotal = (float) $validated['room_subtotal'];

        $promotion = Promotion::where('promo_code', $promoCode)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (! $promotion) {
            return response()->json([
                'valid' => false,
                'message' => 'The promotion code is invalid or has expired.',
            ]);
        }

        // Calculate discount amount
        $discountAmount = 0.00;
        if ($promotion->discount_type === PromotionsDiscountType::PERCENTAGE) {
            $discountAmount = $roomSubtotal * ($promotion->discount_value / 100);
        } else {
            $discountAmount = min($promotion->discount_value, $roomSubtotal);
        }

        // Format to 2 decimal places
        $discountAmount = round($discountAmount, 2);

        return response()->json([
            'valid' => true,
            'promotion' => [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'promo_code' => $promotion->promo_code,
                'discount_type' => $promotion->discount_type->value,
                'discount_value' => $promotion->discount_value,
                'discount_amount' => number_format($discountAmount, 2, '.', ''),
            ],
        ]);
    }
}
