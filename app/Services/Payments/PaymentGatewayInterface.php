<?php

namespace App\Services\Payments;

use App\Models\Booking;
use App\Services\Payments\Exceptions\PaymentGatewayException;

interface PaymentGatewayInterface
{
    /**
     * Create a new checkout session redirecting the customer to payment.
     *
     * @throws PaymentGatewayException
     */
    public function createCheckoutSession(Booking $booking, string $successUrl, string $cancelUrl): CheckoutSession;

    /**
     * Process refund for the given transaction.
     *
     * @throws PaymentGatewayException
     */
    public function refund(string $transactionId, float $amount): bool;
}
