<?php

namespace App\Interfaces;

interface PaymentGatewayInterface
{
    public function charge(array $transactionData): array;

    public function refund(string $transactionId): array;
}
