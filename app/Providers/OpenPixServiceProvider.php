<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenPix\PhpSdk\Client;

class OpenPixServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            $appId = config('services.openpix.app_id');
            $baseUri = config('services.openpix.base_uri');

            return Client::create($appId, $baseUri);
        });
    }
}