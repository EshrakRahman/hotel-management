<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Services\Payments\Exceptions\PaymentGatewayException;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    /**
     * Create a new mock checkout session.
     *
     * @throws PaymentGatewayException
     */
    public function createCheckoutSession(Booking $booking, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $sessionId = 'sess_mock_'.Str::random(20);
        $checkoutUrl = 'https://mock-payment-gateway.test/checkout/'.$booking->booking_ref;

        return new CheckoutSession($sessionId, $checkoutUrl);
    }

    /**
     * Process refund for the given mock transaction.
     *
     * @throws PaymentGatewayException
     */
    public function refund(string $transactionId, float $amount): bool
    {
        return true;
    }
}
