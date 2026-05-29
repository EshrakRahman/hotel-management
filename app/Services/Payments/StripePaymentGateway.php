<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Services\Payments\Exceptions\PaymentGatewayException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripePaymentGateway implements PaymentGatewayInterface
{
    private StripeClient $stripe;

    public function __construct(string $secretKey)
    {
        $this->stripe = new StripeClient($secretKey);
    }

    /**
     * Create a Stripe Checkout Session.
     *
     * @throws PaymentGatewayException
     */
    public function createCheckoutSession(Booking $booking, string $successUrl, string $cancelUrl): CheckoutSession
    {
        try {
            // Aggregate total to charge the customer the exact final calculated amount in cents
            $amountInCents = (int) round($booking->total_amount * 100);

            $session = $this->stripe->checkout->sessions::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $amountInCents,
                        'product_data' => [
                            'name' => 'Reservation Payment - Ref: '.$booking->booking_ref,
                            'description' => 'Payment for booking at '.$booking->hotel->name,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'booking_ref' => $booking->booking_ref,
                ],
            ]);

            return new CheckoutSession($session->id, $session->url);
        } catch (ApiErrorException $e) {
            throw new PaymentGatewayException('Stripe Checkout Session creation failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Process Stripe refund.
     *
     * @throws PaymentGatewayException
     */
    public function refund(string $transactionId, float $amount): bool
    {
        try {
            $amountInCents = (int) round($amount * 100);

            $this->stripe->refunds->create([
                'charge' => $transactionId,
                'amount' => $amountInCents,
            ]);

            return true;
        } catch (ApiErrorException $e) {
            throw new PaymentGatewayException('Stripe refund failed: '.$e->getMessage(), 0, $e);
        }
    }
}
