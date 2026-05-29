<?php

namespace App\Services\Payments;

class CheckoutSession
{
    public function __construct(
        public string $sessionId,
        public string $checkoutUrl
    ) {}
}
