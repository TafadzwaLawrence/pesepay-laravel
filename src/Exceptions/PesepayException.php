<?php

namespace Chitanga\Pesepay\Exceptions;

use Exception;
use Throwable;

class PesepayException extends Exception
{
    /**
     * Default exception messages
     */
    public const INTEGRATION_ERROR = 'Pesepay integration error';

    public const PAYMENT_FAILED = 'Payment processing failed';

    public const INVALID_REQUEST = 'Invalid payment request';

    public const CONFIGURATION_ERROR = 'Pesepay configuration error';

    public const INVALID_RESPONSE = 'Invalid response from Pesepay';

    /**
     * Additional error data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Create a new Pesepay exception instance
     */
    public function __construct(
        string $message = self::PAYMENT_FAILED,
        int $code = 0,
        ?Throwable $previous = null,
        array $data = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    /**
     * Create a configuration exception
     *
     * @return static
     */
    public static function configurationError(string $message): self
    {
        return new static(
            $message ?: self::CONFIGURATION_ERROR,
            500
        );
    }

    /**
     * Create an invalid request exception
     *
     * @return static
     */
    public static function invalidRequest(string $message, array $data = []): self
    {
        return new static(
            $message ?: self::INVALID_REQUEST,
            400,
            null,
            $data
        );
    }

    /**
     * Create a payment failed exception
     *
     * @return static
     */
    public static function paymentFailed(string $message, array $data = []): self
    {
        return new static(
            $message ?: self::PAYMENT_FAILED,
            402, // Payment Required
            null,
            $data
        );
    }

    /**
     * Create an invalid response exception
     *
     * @return static
     */
    public static function invalidResponse(string $message, array $data = []): self
    {
        return new static(
            $message ?: self::INVALID_RESPONSE,
            502, // Bad Gateway
            null,
            $data
        );
    }

    /**
     * Get additional error data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Convert exception to array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'data' => $this->getData(),
        ];
    }
}
