# :Laravel Pesepay

<!-- [![Latest Version on Packagist](https://img.shields.io/packagist/v/:vendor_slug/:package_slug.svg?style=flat-square)](https://packagist.org/packages/:vendor_slug/:package_slug)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/:vendor_slug/:package_slug/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/:vendor_slug/:package_slug/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/:vendor_slug/:package_slug/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/:vendor_slug/:package_slug/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/:vendor_slug/:package_slug.svg?style=flat-square)](https://packagist.org/packages/:vendor_slug/:package_slug) -->
<!--delete-->
---
Pesepay Payment Gateway for Laravel
Latest Version on Packagist
GitHub Tests Action Status
Total Downloads

A comprehensive Laravel package for integrating Pesepay payment gateway into your application. This package provides seamless Ecocash and card payment processing with robust error handling and status checking.

# Features
Ecocash payments integration

Card payments processing

Payment status verification

Comprehensive exception handling

Configurable through environment variables


---
<!--/delete-->
<!-- This is where your description should go. Limit it to a paragraph or two. Consider adding a small example. -->

## Installation

You can install the package via composer:

```bash
composer require chitanga/pesepay
```

You can publish with:

```bash
php artisan vendor:publish --tag="pesepay-config"
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag=":package_slug-config"
```

This is the contents of the published config file:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    */
    'integration_key' => env('PESEPAY_INTEGRATION_KEY'),
    'encryption_key' => env('PESEPAY_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Payment URLs
    |--------------------------------------------------------------------------
    */
    'return_url' => env('PESEPAY_RETURN_URL'),
    'result_url' => env('PESEPAY_RESULT_URL'),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'default_currency' => 'USD',
    'brand_name' => env('PESEPAY_BRAND_NAME', env('APP_NAME', 'Laravel')),

];
```

Configuration
Add these environment variables to your .env file:

```bash
PESEPAY_INTEGRATION_KEY=your_integration_key
PESEPAY_ENCRYPTION_KEY=your_encryption_key
PESEPAY_RETURN_URL=https://your-app.com/return
PESEPAY_RESULT_URL=https://your-app.com/webhook
PESEPAY_BRAND_NAME="Your Business Name"
```

## Usage

```php
use Chitanga\Pesepay\PesepayService;

$pesepay = new PesepayService(
    config('pesepay.integration_key'),
    config('pesepay.encryption_key'),
    config('pesepay.return_url'),
    config('pesepay.result_url')
);
```
## Ecocash Payment

```php
try {
    $payment = $pesepay->ecocash([
        'amount'        => 10.50,
        'phone'         => '263771234567',
        'email'         => 'customer@example.com',
        'reference'     => 'PZW211',                // Dont change the reference code 
        'description'   => 'Product purchase'
    ]);
    
    // Store $payment['reference_number'] and $payment['poll_url'] in your database
} catch (\Chitanga\Pesepay\Exceptions\PesepayException $e) {
    // Handle payment error
}
```

## Card Payment

```php
try {
    $payment = $pesepay->card([
        'amount'        => 25.00,
        'email'         => 'customer@example.com',
        'card_number'   => '4111111111111111',
        'card_expiry'   => '12/25',
        'card_cvv'      => '123',
        'reference'     => 'PWZ204'                 // Dont change the reference code 
    ]);
    
    // Process payment response
} catch (\Chitanga\Pesepay\Exceptions\PesepayException $e) {
    // Handle payment error
}
```

## Check Payment Status

```php
// Using the poll_url from the payment response
$status = $pesepay->checkPaymentStatus($pollUrl);

if ($status['success']) {
    // Payment was successful
} else {
    // Payment failed or is pending
}

// Quick check
if ($pesepay->isPaymentSuccessful($pollUrl)) {
    // Payment successful
}
```
## Error Handling

The package throws PesepayException for all payment-related errors. You can catch and handle these exceptions:

```bash
try {
    // Payment operations
} catch (\Chitanga\Pesepay\Exceptions\PesepayException $e) {
    // Get error details
    $errorMessage = $e->getMessage();
    $errorCode = $e->getCode();
    $errorData = $e->getData();
    
    // Handle error appropriately
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [:author_name](https://github.com/TafadzwaLawrence)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
