<?php

namespace Chitanga\Pesepay;

use Chitanga\Pesepay\Exceptions\PesepayException;
use Codevirtus\Payments\Pesepay;
use Illuminate\Support\Facades\Http;

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

        return [
            'reference_number' => $response->referenceNumber(),
            'poll_url' => $response->pollUrl(),
        ];
    }

    public function card(array $params)
    {
        $this->validatePaymentParams($params, ['amount', 'email', 'card_number', 'card_expiry', 'card_cvv']);

        $reference = $params['reference'] ?? self::CARD_REF_PREFIX.uniqid();

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

        if (! $response->success()) {
            throw new PesepayException($response->message());
        }

        return [
            'status' => true,
            'reference_number' => $response->referenceNumber(),
            'poll_url' => $response->pollUrl(),
        ];
    }

    protected function validatePaymentParams(array $params, array $required): void
    {
        foreach ($required as $field) {
            if (empty($params[$field] ?? null)) {
                throw new PesepayException("Missing required parameter: {$field}");
            }
        }
    }

    /**
     * Check payment status
     *
     * @param  string  $pollUrl  The URL to check for payment status
     * @return array Contains status and decoded response data
     *
     * @throws PesepayException
     */
    public function checkPaymentStatus(string $pollUrl): array
    {
        try {
            $response = Http::withHeaders([
                'authorization' => $this->pesepay->integrationKey,
                'content-type' => 'application/json',
            ])->get($pollUrl);

            $decodedResponse = $this->decodePesepayResponse($response);

            return [
                'success' => ($decodedResponse['transactionStatus'] ?? null) === 'SUCCESS',
                'status' => $decodedResponse['transactionStatus'] ?? null,
                'data' => $decodedResponse,
            ];

        } catch (\Exception $e) {
            throw new PesepayException(
                'Error checking payment status: '.$e->getMessage(),
                0,
                $e,
                ['poll_url' => $pollUrl]
            );
        }
    }

    /**
     * Quick check if payment was successful
     */
    public function isPaymentSuccessful(string $pollUrl): bool
    {
        try {
            $status = $this->checkPaymentStatus($pollUrl);

            return $status['success'];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Decodes Pesepay's encrypted response
     *
     * @param  \Illuminate\Http\Client\Response  $response
     *
     * @throws PesepayException
     */
    private function decodePesepayResponse($response): array
    {
        if ($response->failed()) {
            throw new PesepayException(
                "Failed to get payment status: HTTP {$response->status()}",
                $response->status()
            );
        }

        try {
            $payload = $response->json()['payload'] ?? '';

            if (empty($payload)) {
                throw new PesepayException('Empty payload in Pesepay response');
            }

            $encoded = base64_decode($payload);
            $ALGORITHM = 'AES-256-CBC';
            $encryptionKey = $this->pesepay->encryptionKey; // Use the instance's key
            $INIT_VECTOR_LENGTH = 16;
            $initVector = substr($encryptionKey, 0, $INIT_VECTOR_LENGTH);

            $decoded = openssl_decrypt(
                $encoded,
                $ALGORITHM,
                $encryptionKey,
                OPENSSL_RAW_DATA,
                $initVector
            );

            if ($decoded === false) {
                throw new PesepayException('Failed to decrypt Pesepay response');
            }

            $result = json_decode($decoded, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new PesepayException('Invalid JSON in decrypted response');
            }

            return $result;

        } catch (\Exception $e) {
            throw new PesepayException(
                'Error decoding Pesepay response: '.$e->getMessage(),
                0,
                $e
            );
        }
    }
}
