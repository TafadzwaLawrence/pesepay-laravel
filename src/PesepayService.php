<?php

namespace Chitanga\Pesepay;

use Chitanga\Pesepay\Exceptions\PesepayException;
use Codevirtus\Payments\Pesepay;

class PesepayService
{
    protected Pesepay $pesepay;

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
            $params['reference'],
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

    public function bank(array $params)
    {
        $this->validatePaymentParams($params, ['amount', 'account_number', 'bank_code', 'email', 'reference']);

        $payment = $this->pesepay->createPayment(
            $params['currency'] ?? config('pesepay.default_currency', 'USD'),
            $params['reference'],
            $params['email'],
            $params['phone'] ?? '',
            $params['description'] ?? 'Bank Payment'
        );

        $response = $this->pesepay->makeSeamlessPayment(
            $payment,
            $params['purpose'] ?? 'Payment',
            $params['amount'],
            [
                'accountNumber' => $params['account_number'],
                'bankCode' => $params['bank_code'],
                'accountName' => $params['account_name'] ?? '',
            ],
            $params['brand_name'] ?? config('pesepay.brand_name', 'Pesepay')
        );

        if (! $response->success()) {
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
