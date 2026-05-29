<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\paymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Create a checkout session URL for the booking.
     */
    public function checkoutSession(Request $request, string $bookingRef, PaymentGatewayInterface $gateway): JsonResponse
    {
        $request->validate([
            'success_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
        ]);

        $booking = Booking::where('booking_ref', $bookingRef)
            ->with(['hotel'])
            ->firstOrFail();

        // Authorize: Only the booking owner or admin can initiate payment
        $user = auth()->user();
        if (! $user->hasRole('admin') && $booking->user_id !== $user->id) {
            abort(403, 'This action is unauthorized.');
        }

        // Validate booking status is pending checkout
        if ($booking->status !== BookingStatus::PENDING || $booking->payment_status !== paymentStatus::PENDING) {
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
