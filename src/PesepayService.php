<?php

namespace Chitanga\Pesepay;

use Chitanga\Pesepay\Exceptions\PesepayException;
use Codevirtus\Payments\Pesepay;

class PesepayService
{
    protected Pesepay $pesepay;

    // Constants for reference prefixes
    const ECOCASH_REF_PREFIX = 'PZW211';
    const BANK_REF_PREFIX = 'PWZ204';

    public function __construct(
        string $integrationKey,
        string $encryptionKey,
        ?string $returnUrl = null,
        ?string $resultUrl = null
    ) {
        $this->validateCredentials($integrationKey, $encryptionKey);

        $this->pesepay = new Pesepay($integrationKey, $encryptionKey);

        if ($returnUrl) {
            $this->pesepay->returnUrl = $returnUrl;
        }

        if ($resultUrl) {
            $this->pesepay->resultUrl = $resultUrl;
        }
    }

    protected function validateCredentials(string $integrationKey, string $encryptionKey): void
    {
        if (empty($integrationKey)) {
            throw new PesepayException('Pesepay integration key is not configured');
        }

        if (empty($encryptionKey)) {
            throw new PesepayException('Pesepay encryption key is not configured');
        }
    }

    public function ecocash(array $params)
    {
        $this->validatePaymentParams($params, ['amount', 'phone', 'email', 'reference']);

        $payment = $this->pesepay->createPayment(
            $params['currency'] ?? config('pesepay.default_currency', 'USD'),
            self::ECOCASH_REF_PREFIX,
            $params['email'],
            $params['phone'],
            $params['description'] ?? 'Ecocash Payment'
        );

        $response = $this->pesepay->makeSeamlessPayment(
            $payment,
            $params['purpose'] ?? 'Payment',
            $params['amount'],
            ['customerPhoneNumber' => $params['phone']],
            $params['brand_name'] ?? config('pesepay.brand_name', 'Pesepay')
        );

        if (! $response->success()) {
            throw new PesepayException($response->message());
        }

        return $response;
    }

    public function card(array $params)
    {
        $this->validatePaymentParams($params, ['amount', 'email', 'card_number', 'card_expiry', 'card_cvv']);

        $reference = $params['reference'] ?? self::CARD_REF_PREFIX . uniqid();

        $payment = $this->pesepay->createPayment(
            $params['currency'] ?? config('pesepay.default_currency', 'USD'),
            $reference,
            $params['email'],
            $params['phone'] ?? '',
            $params['description'] ?? 'Card Payment'
        );

        $paymentMethodFields = [
            'creditCardNumber' => $params['card_number'],
            'creditCardExpiryDate' => $params['card_expiry'],
            'creditCardSecurityNumber' => $params['card_cvv'],
        ];

        $response = $this->pesepay->makeSeamlessPayment(
            $payment,
            $params['purpose'] ?? 'Payment',
            $params['amount'],
            $paymentMethodFields,
            $params['brand_name'] ?? config('pesepay.brand_name', 'Pesepay')
        );

        if (!$response->success()) {
            throw new PesepayException($response->message());
        }

        return $response;
    }


    protected function validatePaymentParams(array $params, array $required): void
    {
        foreach ($required as $field) {
            if (empty($params[$field] ?? null)) {
                throw new PesepayException("Missing required parameter: {$field}");
            }
        }
    }
}
