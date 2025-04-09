<?php

namespace Chitanga\Pesepay;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use YourVendor\Pesepay\PesepayService;

class PesepayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('pesepay')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_pesepay_table');
        // ->hasRoute('web')
        // ->hasCommand(PesepayCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton('pesepay', function ($app) {
            $config = $app['config']['pesepay'];

            return new PesepayService([
                'integration_key' => $config['integration_key'],
                'encryption_key' => $config['encryption_key'],
                'return_url' => $config['return_url'],
                'result_url' => $config['result_url'],
            ]);
        });
    }
}
