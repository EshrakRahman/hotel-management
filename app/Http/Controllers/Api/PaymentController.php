<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateCheckoutSessionRequest;
use App\Models\Booking;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    /**
     * Create a checkout session URL for the booking.
     */
    public function checkoutSession(CreateCheckoutSessionRequest $request, Booking $booking, PaymentGatewayInterface $gateway): JsonResponse
    {
        Gate::authorize('pay', $booking);

        $booking->loadMissing('hotel');

        // Validate booking status is pending checkout
        if ($booking->status !== BookingStatus::PENDING || $booking->payment_status !== PaymentStatus::PENDING) {
            return response()->json([
                'message' => 'This booking cannot be processed for payment.',
            ], 422);
        }

        $session = $gateway->createCheckoutSession(
            $booking,
            $request->input('success_url'),
            $request->input('cancel_url')
        );

        return response()->json([
            'session_id' => $session->sessionId,
            'checkout_url' => $session->checkoutUrl,
        ]);
    }
}
