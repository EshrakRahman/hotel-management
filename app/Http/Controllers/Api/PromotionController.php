<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerifyPromotionRequest;
use App\Http\Resources\Api\PromotionResource;
use App\Models\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PromotionController extends Controller
{
    /**
     * List all currently active promotions.
     */
    public function index(): AnonymousResourceCollection
    {
        $promotions = Promotion::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->latest()
            ->get();

        return PromotionResource::collection($promotions);
    }

    /**
     * Verify a promotion code and calculate the potential discount.
     */
    public function verify(VerifyPromotionRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
        $discountAmount = $promotion->calculateDiscount($roomSubtotal);

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
