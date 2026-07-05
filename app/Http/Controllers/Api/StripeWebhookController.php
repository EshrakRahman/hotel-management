<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('payment.gateways.stripe.webhook_secret');

        $event = null;

        // Verify webhook signature (bypassed in testing/dev environments if secret is missing)
        if (app()->environment('testing') || ! $endpointSecret) {
            $eventData = json_decode($payload, true);
            if (! $eventData) {
                return response()->json(['error' => 'Invalid payload'], 400);
            }
            // Structure a mock event object for simple code consistency
            $event = (object) [
                'type' => $eventData['type'] ?? null,
                'data' => (object) [
                    'object' => (object) ($eventData['data']['object'] ?? []),
                ],
            ];
        } else {
            try {
                $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } catch (\UnexpectedValueException $e) {
                return response()->json(['error' => 'Invalid payload: '.$e->getMessage()], 400);
            } catch (SignatureVerificationException $e) {
                return response()->json(['error' => 'Invalid signature: '.$e->getMessage()], 400);
            }
        }

        // Process webhook event types
        return match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object),
            'charge.refunded' => $this->handleChargeRefunded($event->data->object),
            default => response()->json(['message' => 'Event type not handled: '.$event->type]),
        };
    }

    /**
     * Handle checkout.session.completed event.
     */
    protected function handleCheckoutSessionCompleted(object $session): JsonResponse
    {
        $bookingRef = $session->metadata->booking_ref ?? ($session->metadata['booking_ref'] ?? null);
        $sessionId = $session->id ?? null;

        if (! $bookingRef || ! $sessionId) {
            return response()->json(['error' => 'Missing booking reference or session ID'], 400);
        }

        // Idempotency: Avoid double processing if Stripe sends the event multiple times
        $existingPayment = Payment::where('transaction_id', $sessionId)->first();
        if ($existingPayment) {
            return response()->json(['message' => 'Payment already processed']);
        }

        $booking = Booking::where('booking_ref', $bookingRef)->first();
        if (! $booking) {
            return response()->json(['error' => 'Booking not found: '.$bookingRef], 404);
        }

        DB::transaction(function () use ($booking, $sessionId, $session) {
            // Confirm the booking
            $booking->update([
                'status' => BookingStatus::CONFIRMED,
                'payment_status' => PaymentStatus::PAID,
            ]);

            // Save the payment transaction details
            Payment::create([
                'booking_id' => $booking->id,
                'transaction_id' => $sessionId,
                'gateway' => 'stripe',
                'amount' => $booking->total_amount,
                'status' => 'paid',
                'payload' => json_encode($session),
            ]);
        });

        // Event placeholder: event(new BookingConfirmed($booking));

        return response()->json(['message' => 'Booking confirmed and payment processed']);
    }

    /**
     * Handle charge.refunded event.
     */
    protected function handleChargeRefunded(object $charge): JsonResponse
    {
        // Stripe webhook charges has details mapping back to checkout sessions or payment intents
        $paymentIntent = $charge->payment_intent ?? null;
        $chargeId = $charge->id ?? null;

        // Search payment transaction using charge ID or payment intent
        $payment = Payment::where('transaction_id', $paymentIntent)
            ->orWhere('transaction_id', $chargeId)
            ->first();

        if (! $payment) {
            Log::warning('Refund received for unknown payment transaction: '.$chargeId);

            return response()->json(['message' => 'Payment transaction not found for refund'], 200);
        }

        $booking = $payment->booking;

        DB::transaction(function () use ($booking, $payment, $charge) {
            $booking->update([
                'status' => BookingStatus::CANCELLED,
                'payment_status' => PaymentStatus::REFUNDED,
            ]);

            $payment->update([
                'status' => 'refunded',
                'payload' => json_encode(array_merge(
                    json_decode($payment->payload, true) ?? [],
                    ['refund_payload' => $charge]
                )),
            ]);
        });

        return response()->json(['message' => 'Refund processed successfully']);
    }
}
