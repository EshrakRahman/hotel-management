<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class PaymentManager
{
    protected array $drivers = [];

    public function __construct(protected $app) {}

    /**
     * Resolve the gateway driver instance.
     */
    public function driver(?string $driver = null): PaymentGatewayInterface
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return config('payment.default', 'stripe');
    }

    /**
     * Create the concrete driver instance.
     */
    protected function createDriver(string $driver): PaymentGatewayInterface
    {
        return match ($driver) {
            'stripe' => $this->createStripeDriver(),
            'mock' => $this->createMockDriver(),
            default => throw new InvalidArgumentException("Payment driver [{$driver}] is not supported."),
        };
    }

    protected function createStripeDriver(): StripePaymentGateway
    {
        $config = config('payment.gateways.stripe');

        if (empty($config['secret_key'])) {
            throw new InvalidArgumentException('Stripe secret key is not configured.');
        }

        return new StripePaymentGateway($config['secret_key']);
    }

    protected function createMockDriver(): MockPaymentGateway
    {
        return new MockPaymentGateway;
    }
}
