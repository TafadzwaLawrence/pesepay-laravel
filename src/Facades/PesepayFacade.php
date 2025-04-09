<?php

namespace Chitanga\Pesepay;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed initializePayment(string $currency, string $reference, string $email, string $phoneNumber, string $description, float $amount, array $paymentMethodFields = [], string $paymentMethod = 'ecocash', string $merchantReference = null)
 *
 * @see \Chitanga\Pesepay\PesepayService
 */
class PesepayFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pesepay';
    }
}
